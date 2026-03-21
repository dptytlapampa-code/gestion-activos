<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MantenimientoService
{
    public function __construct(private readonly EquipoStatusResolver $equipoStatusResolver) {}

    public function registrar(Equipo $equipo, User $user, array $data): Mantenimiento
    {
        $tipo = (string) ($data['tipo'] ?? '');

        try {
            /** @var Mantenimiento $mantenimiento */
            $mantenimiento = DB::transaction(function () use ($equipo, $user, $data, $tipo): Mantenimiento {
                $equipo = Equipo::query()
                    ->with(['oficina.service.institution', 'equipoStatus'])
                    ->lockForUpdate()
                    ->findOrFail($equipo->id);

                $institutionId = (int) $equipo->oficina?->service?->institution_id;

                if ($institutionId <= 0) {
                    throw ValidationException::withMessages([
                        'mantenimiento' => 'El equipo no tiene una institucion valida para registrar el mantenimiento.',
                    ]);
                }

                $externoAbierto = Mantenimiento::query()
                    ->where('equipo_id', $equipo->id)
                    ->externos()
                    ->abiertos()
                    ->lockForUpdate()
                    ->first();

                if ($equipo->estado === Equipo::ESTADO_BAJA && $externoAbierto === null) {
                    throw ValidationException::withMessages([
                        'mantenimiento' => 'No se pueden registrar nuevos mantenimientos sobre un equipo que ya esta en baja.',
                    ]);
                }

                return match ($tipo) {
                    Mantenimiento::TIPO_EXTERNO => $this->registrarExterno($equipo, $user, $institutionId, $data, $externoAbierto),
                    Mantenimiento::TIPO_ALTA, Mantenimiento::TIPO_BAJA => $this->cerrarExterno($equipo, $user, $institutionId, $data, $externoAbierto),
                    Mantenimiento::TIPO_INTERNO, Mantenimiento::TIPO_OTRO => $this->registrarNotaTecnica($equipo, $user, $institutionId, $data),
                    default => throw ValidationException::withMessages([
                        'tipo' => 'El tipo de mantenimiento seleccionado no es valido.',
                    ]),
                };
            }, 3);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                if ($tipo === Mantenimiento::TIPO_EXTERNO) {
                    throw ValidationException::withMessages([
                        'mantenimiento' => 'No se puede registrar un nuevo mantenimiento externo porque ya existe uno abierto para este equipo.',
                    ]);
                }

                if (in_array($tipo, Mantenimiento::TIPOS_CIERRE_EXTERNO, true)) {
                    throw ValidationException::withMessages([
                        'mantenimiento' => 'Este mantenimiento externo ya fue cerrado. Actualice la ficha del equipo antes de volver a intentarlo.',
                    ]);
                }
            }

            throw $exception;
        }

        return $mantenimiento;
    }

    /**
     * @return array<int, string>
     */
    public function tiposDisponiblesParaEquipo(Equipo $equipo): array
    {
        if ($equipo->tieneMantenimientoExternoAbierto()) {
            return [
                Mantenimiento::TIPO_ALTA,
                Mantenimiento::TIPO_BAJA,
                Mantenimiento::TIPO_INTERNO,
                Mantenimiento::TIPO_OTRO,
            ];
        }

        if ($equipo->estado === Equipo::ESTADO_BAJA) {
            return [];
        }

        return [
            Mantenimiento::TIPO_EXTERNO,
            Mantenimiento::TIPO_INTERNO,
            Mantenimiento::TIPO_OTRO,
        ];
    }

    public function actualizar(Mantenimiento $mantenimiento, array $data): void
    {
        $this->assertRegistroEditable($mantenimiento);

        $mantenimiento->update([
            'fecha' => Carbon::parse($data['fecha'])->toDateString(),
            'titulo' => trim((string) $data['titulo']),
            'detalle' => trim((string) $data['detalle']),
            'proveedor' => $this->nullableTrim($data['proveedor'] ?? null),
        ]);
    }

    public function eliminar(Mantenimiento $mantenimiento): void
    {
        $this->assertRegistroEditable($mantenimiento);
        $mantenimiento->delete();
    }

    public function mensajeRegistroBloqueado(): string
    {
        return 'Los mantenimientos que abren o cierran un ciclo tecnico no se pueden editar ni eliminar para preservar la trazabilidad del equipo.';
    }

    private function registrarExterno(
        Equipo $equipo,
        User $user,
        int $institutionId,
        array $data,
        ?Mantenimiento $externoAbierto
    ): Mantenimiento {
        if ($externoAbierto !== null) {
            throw ValidationException::withMessages([
                'mantenimiento' => 'No se puede registrar un nuevo mantenimiento externo porque ya existe uno abierto para este equipo.',
            ]);
        }

        $fecha = Carbon::parse($data['fecha'])->toDateString();
        $fechaIngreso = Carbon::parse($data['fecha_ingreso_st'])->toDateString();
        $estadoMantenimientoId = $this->equipoStatusResolver->resolveIdByEstado(Equipo::ESTADO_MANTENIMIENTO, 'mantenimientos');

        $mantenimiento = Mantenimiento::query()->create([
            'equipo_id' => $equipo->id,
            'institution_id' => $institutionId,
            'created_by' => $user->id,
            'fecha' => $fecha,
            'tipo' => Mantenimiento::TIPO_EXTERNO,
            'titulo' => trim((string) $data['titulo']),
            'detalle' => trim((string) $data['detalle']),
            'proveedor' => $this->nullableTrim($data['proveedor'] ?? null),
            'fecha_ingreso_st' => $fechaIngreso,
            'fecha_egreso_st' => null,
            'dias_en_servicio' => null,
            'mantenimiento_externo_id' => null,
            'estado_resultante_id' => $estadoMantenimientoId,
        ]);

        $this->actualizarEstadoEquipo($equipo, Equipo::ESTADO_MANTENIMIENTO);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'maintenance_external_opened',
            'auditable_type' => Mantenimiento::class,
            'auditable_id' => $mantenimiento->id,
            'before' => null,
            'after' => [
                'equipo_id' => $equipo->id,
                'fecha_ingreso_st' => $fechaIngreso,
                'estado_equipo' => Equipo::ESTADO_MANTENIMIENTO,
            ],
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $mantenimiento;
    }

    private function cerrarExterno(
        Equipo $equipo,
        User $user,
        int $institutionId,
        array $data,
        ?Mantenimiento $externoAbierto
    ): Mantenimiento {
        if ($externoAbierto === null) {
            $accion = $data['tipo'] === Mantenimiento::TIPO_BAJA
                ? 'registrar la baja'
                : 'dar el alta';

            throw ValidationException::withMessages([
                'mantenimiento' => "No se puede {$accion} porque este equipo no tiene un mantenimiento externo abierto registrado.",
            ]);
        }

        $fecha = Carbon::parse($data['fecha'])->toDateString();
        $fechaIngreso = Carbon::parse($externoAbierto->fecha_ingreso_st ?? $externoAbierto->fecha)->toDateString();
        $fechaEgreso = Carbon::parse($data['fecha_egreso_st'])->toDateString();

        if ($fechaEgreso < $fechaIngreso) {
            throw ValidationException::withMessages([
                'fecha_egreso_st' => 'La fecha de egreso no puede ser anterior al ingreso al servicio tecnico externo.',
            ]);
        }

        $dias = Carbon::parse($fechaIngreso)->diffInDays(Carbon::parse($fechaEgreso));
        $estadoDestino = $data['tipo'] === Mantenimiento::TIPO_BAJA
            ? Equipo::ESTADO_BAJA
            : Equipo::ESTADO_OPERATIVO;
        $estadoDestinoId = $this->equipoStatusResolver->resolveIdByEstado($estadoDestino, 'mantenimientos');

        $externoAbierto->update([
            'fecha_ingreso_st' => $fechaIngreso,
            'fecha_egreso_st' => $fechaEgreso,
            'dias_en_servicio' => $dias,
        ]);

        $cierre = Mantenimiento::query()->create([
            'equipo_id' => $equipo->id,
            'institution_id' => $institutionId,
            'created_by' => $user->id,
            'fecha' => $fecha,
            'tipo' => $data['tipo'],
            'titulo' => trim((string) $data['titulo']),
            'detalle' => trim((string) $data['detalle']),
            'proveedor' => $externoAbierto->proveedor,
            'fecha_ingreso_st' => $fechaIngreso,
            'fecha_egreso_st' => $fechaEgreso,
            'dias_en_servicio' => $dias,
            'mantenimiento_externo_id' => $externoAbierto->id,
            'estado_resultante_id' => $estadoDestinoId,
        ]);

        $this->actualizarEstadoEquipo($equipo, $estadoDestino);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'maintenance_external_closed',
            'auditable_type' => Mantenimiento::class,
            'auditable_id' => $externoAbierto->id,
            'before' => [
                'fecha_egreso_st' => null,
                'dias_en_servicio' => null,
            ],
            'after' => [
                'fecha_egreso_st' => $fechaEgreso,
                'dias_en_servicio' => $dias,
                'closed_by' => $cierre->id,
                'estado_equipo' => $estadoDestino,
            ],
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $cierre;
    }

    private function registrarNotaTecnica(Equipo $equipo, User $user, int $institutionId, array $data): Mantenimiento
    {
        return Mantenimiento::query()->create([
            'equipo_id' => $equipo->id,
            'institution_id' => $institutionId,
            'created_by' => $user->id,
            'fecha' => Carbon::parse($data['fecha'])->toDateString(),
            'tipo' => $data['tipo'],
            'titulo' => trim((string) $data['titulo']),
            'detalle' => trim((string) $data['detalle']),
            'proveedor' => $this->nullableTrim($data['proveedor'] ?? null),
            'fecha_ingreso_st' => null,
            'fecha_egreso_st' => null,
            'dias_en_servicio' => null,
            'mantenimiento_externo_id' => null,
            'estado_resultante_id' => $equipo->equipo_status_id
                ?: $this->equipoStatusResolver->resolveIdByEstado((string) $equipo->estado, 'mantenimientos'),
        ]);
    }

    private function actualizarEstadoEquipo(Equipo $equipo, string $estado): void
    {
        $equipo->equipo_status_id = $this->equipoStatusResolver->resolveIdByEstado($estado, 'mantenimientos');
        $equipo->estado = $estado;

        if ($equipo->offsetExists('_audit_before')) {
            $equipo->offsetUnset('_audit_before');
        }

        $equipo->save();
    }

    private function assertRegistroEditable(Mantenimiento $mantenimiento): void
    {
        if (! $mantenimiento->canBeManuallyChanged()) {
            throw ValidationException::withMessages([
                'mantenimiento' => $this->mensajeRegistroBloqueado(),
            ]);
        }
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505'], true);
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
