<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use App\Services\Auditing\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoService
{
    public function __construct(
        private readonly EquipoStatusResolver $equipoStatusResolver,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function registrar(Equipo $equipo, User $user, array $data): void
    {
        DB::transaction(function () use ($equipo, $user, $data): void {
            $equipo->refresh()->load('oficina.service.institution');
            $tipo = $data['tipo_movimiento'];

            if ($equipo->estado === Equipo::ESTADO_BAJA) {
                throw ValidationException::withMessages([
                    'equipo' => 'El equipo se encuentra en baja y no admite nuevos movimientos.',
                ]);
            }

            $estadoAnterior = $equipo->estado;
            $oficinaOrigen = $equipo->oficina;
            $before = $this->movementAuditSnapshot($estadoAnterior, $oficinaOrigen);
            $ubicacionOrigen = $equipo->ubicacionActual();
            $ubicacionDestino = [
                'institucion_id' => null,
                'servicio_id' => null,
                'oficina_id' => null,
            ];

            $this->validarReglas($equipo, $user, $data, $ubicacionOrigen);

            if (in_array($tipo, [Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA], true)) {
                $this->validarJerarquiaDestino(
                    (int) $data['institucion_destino_id'],
                    (int) $data['servicio_destino_id'],
                    (int) $data['oficina_destino_id'],
                );

                $ubicacionDestino = [
                    'institucion_id' => (int) $data['institucion_destino_id'],
                    'servicio_id' => (int) $data['servicio_destino_id'],
                    'oficina_id' => (int) $data['oficina_destino_id'],
                ];
            }

            $estadoNuevo = $equipo->estado;
            $prestamoData = [
                'receptor_nombre' => null,
                'receptor_dni' => null,
                'receptor_cargo' => null,
                'fecha_inicio_prestamo' => null,
                'fecha_estimada_devolucion' => null,
                'fecha_devolucion_real' => null,
            ];

            if ($tipo === Movimiento::TIPO_MANTENIMIENTO) {
                $estadoNuevo = Equipo::ESTADO_MANTENIMIENTO;
                $equipo->equipo_status_id = $this->equipoStatusResolver->resolveIdByEstado(Equipo::ESTADO_MANTENIMIENTO, 'tipo_movimiento');
            }

            if ($tipo === Movimiento::TIPO_PRESTAMO) {
                $equipo->equipo_status_id = $this->equipoStatusResolver->resolveIdByEstado(Equipo::ESTADO_PRESTADO, 'tipo_movimiento');
                $estadoNuevo = Equipo::ESTADO_PRESTADO;
                $prestamoData = [
                    'receptor_nombre' => $data['receptor_nombre'],
                    'receptor_dni' => $data['receptor_dni'],
                    'receptor_cargo' => $data['receptor_cargo'] ?? null,
                    'fecha_inicio_prestamo' => $data['fecha_inicio_prestamo'],
                    'fecha_estimada_devolucion' => $data['fecha_estimada_devolucion'],
                    'fecha_devolucion_real' => null,
                ];
            }

            if ($tipo === Movimiento::TIPO_DEVOLUCION) {
                $prestamo = $this->prestamoActivo($equipo);

                $ubicacionDestino = [
                    'institucion_id' => $prestamo->institucion_origen_id,
                    'servicio_id' => $prestamo->servicio_origen_id,
                    'oficina_id' => $prestamo->oficina_origen_id,
                ];

                $prestamo->update(['fecha_devolucion_real' => now()]);
                $equipo->equipo_status_id = $this->equipoStatusResolver->resolveIdByEstado(Equipo::ESTADO_OPERATIVO, 'tipo_movimiento');
                $estadoNuevo = Equipo::ESTADO_OPERATIVO;
            }

            if ($tipo === Movimiento::TIPO_BAJA) {
                $equipo->equipo_status_id = $this->equipoStatusResolver->resolveIdByEstado(Equipo::ESTADO_BAJA, 'tipo_movimiento');
                $estadoNuevo = Equipo::ESTADO_BAJA;
            }

            $movimiento = Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => $user->id,
                'tipo_movimiento' => $tipo,
                'fecha' => now(),
                'institucion_origen_id' => $ubicacionOrigen['institucion_id'],
                'servicio_origen_id' => $ubicacionOrigen['servicio_id'],
                'oficina_origen_id' => $ubicacionOrigen['oficina_id'],
                'institucion_destino_id' => $ubicacionDestino['institucion_id'],
                'servicio_destino_id' => $ubicacionDestino['servicio_id'],
                'oficina_destino_id' => $ubicacionDestino['oficina_id'],
                'receptor_nombre' => $prestamoData['receptor_nombre'],
                'receptor_dni' => $prestamoData['receptor_dni'],
                'receptor_cargo' => $prestamoData['receptor_cargo'],
                'fecha_inicio_prestamo' => $prestamoData['fecha_inicio_prestamo'],
                'fecha_estimada_devolucion' => $prestamoData['fecha_estimada_devolucion'],
                'fecha_devolucion_real' => $prestamoData['fecha_devolucion_real'],
                'observacion' => $data['observacion'] ?? null,
            ]);

            if (in_array($tipo, [Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA, Movimiento::TIPO_DEVOLUCION], true)) {
                $equipo->oficina_id = $ubicacionDestino['oficina_id'];
            }

            $equipo->estado = $estadoNuevo;

            if ($equipo->offsetExists('_audit_before')) {
                $equipo->offsetUnset('_audit_before');
            }

            $equipo->save();

            $oficinaDestino = $equipo->oficina_id !== null
                ? Office::query()->with('service.institution')->find($equipo->oficina_id)
                : null;

            $after = $this->movementAuditSnapshot($estadoNuevo, $oficinaDestino);
            $changes = $this->auditLogService->diff($before, $after, [
                'estado' => 'Estado',
                'institucion' => 'Institucion',
                'servicio' => 'Servicio',
                'oficina' => 'Oficina',
            ]);

            $this->auditLogService->record([
                'user' => $user,
                'institution_id' => $oficinaDestino?->service?->institution?->id ?? $oficinaOrigen?->service?->institution?->id,
                'module' => 'movimientos',
                'action' => $this->movementAction($tipo),
                'entity_type' => 'equipo',
                'entity_id' => $equipo->id,
                'summary' => $this->movementSummary($tipo, $equipo),
                'before' => $before,
                'after' => $after,
                'metadata' => [
                    'details' => array_filter([
                        'movimiento_id' => $movimiento->id,
                        'tipo_movimiento' => $this->movementLabel($tipo),
                        'origen' => $this->officeLabel($oficinaOrigen),
                        'destino' => $this->officeLabel($oficinaDestino),
                        'receptor' => $prestamoData['receptor_nombre'],
                        'dni_receptor' => $prestamoData['receptor_dni'],
                        'cargo_receptor' => $prestamoData['receptor_cargo'],
                        'fecha_inicio_prestamo' => $prestamoData['fecha_inicio_prestamo'],
                        'fecha_estimada_devolucion' => $prestamoData['fecha_estimada_devolucion'],
                        'observacion' => $data['observacion'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== ''),
                    'changes' => $changes,
                ],
                'level' => $this->movementLevel($tipo),
                'is_critical' => $this->movementIsCritical($tipo),
            ]);
        });
    }

    private function validarReglas(Equipo $equipo, User $user, array $data, array $ubicacionOrigen): void
    {
        $tipo = $data['tipo_movimiento'];

        if (in_array($tipo, [Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA], true)) {
            if ((int) $data['oficina_destino_id'] === (int) $ubicacionOrigen['oficina_id']) {
                throw ValidationException::withMessages(['oficina_destino_id' => 'La oficina de destino debe ser distinta de la oficina actual.']);
            }

            if (! app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, (int) $data['institucion_destino_id'])) {
                throw ValidationException::withMessages([
                    'institucion_destino_id' => 'No tiene permisos para operar con la institucion de destino seleccionada.',
                ]);
            }
        }

        if ($tipo === Movimiento::TIPO_TRANSFERENCIA_INTERNA && (int) $data['institucion_destino_id'] !== (int) $ubicacionOrigen['institucion_id']) {
            throw ValidationException::withMessages([
                'institucion_destino_id' => 'La transferencia interna debe mantenerse en la misma institucion.',
            ]);
        }

        if ($tipo === Movimiento::TIPO_PRESTAMO && $equipo->tienePrestamoActivo()) {
            throw ValidationException::withMessages(['tipo_movimiento' => 'El equipo ya tiene un prestamo activo.']);
        }

        if ($tipo === Movimiento::TIPO_DEVOLUCION) {
            $this->prestamoActivo($equipo);
        }
    }

    private function validarJerarquiaDestino(int $institucionDestinoId, int $servicioDestinoId, int $oficinaDestinoId): void
    {
        $service = Service::query()->find($servicioDestinoId);

        if ($service === null || (int) $service->institution_id !== $institucionDestinoId) {
            throw ValidationException::withMessages([
                'servicio_destino_id' => 'El servicio de destino no pertenece a la institucion de destino seleccionada.',
            ]);
        }

        $office = Office::query()->find($oficinaDestinoId);

        if ($office === null || (int) $office->service_id !== $servicioDestinoId) {
            throw ValidationException::withMessages([
                'oficina_destino_id' => 'La oficina de destino no pertenece al servicio de destino seleccionado.',
            ]);
        }
    }

    private function prestamoActivo(Equipo $equipo): Movimiento
    {
        $prestamo = Movimiento::query()
            ->where('equipo_id', $equipo->id)
            ->where('tipo_movimiento', Movimiento::TIPO_PRESTAMO)
            ->whereNull('fecha_devolucion_real')
            ->latest('fecha')
            ->latest('id')
            ->first();

        if ($prestamo === null) {
            throw ValidationException::withMessages([
                'tipo_movimiento' => 'No existe prestamo activo para registrar la devolucion.',
            ]);
        }

        return $prestamo;
    }

    /**
     * @return array<string, string>
     */
    private function movementAuditSnapshot(string $estado, ?Office $office): array
    {
        return [
            'estado' => $this->estadoLabel($estado),
            'institucion' => $office?->service?->institution?->nombre ?? 'Sin institucion',
            'servicio' => $office?->service?->nombre ?? 'Sin servicio',
            'oficina' => $office?->nombre ?? 'Sin oficina',
        ];
    }

    private function movementAction(string $type): string
    {
        return match ($type) {
            Movimiento::TIPO_PRESTAMO => 'prestamo_registrado',
            Movimiento::TIPO_DEVOLUCION => 'devolucion_registrada',
            Movimiento::TIPO_MANTENIMIENTO => 'movimiento_mantenimiento_registrado',
            Movimiento::TIPO_BAJA => 'baja_registrada',
            Movimiento::TIPO_TRANSFERENCIA_INTERNA => 'transferencia_interna_registrada',
            Movimiento::TIPO_TRANSFERENCIA_EXTERNA => 'transferencia_externa_registrada',
            default => 'traslado_registrado',
        };
    }

    private function movementSummary(string $type, Equipo $equipo): string
    {
        $reference = $this->equipmentReference($equipo);

        return match ($type) {
            Movimiento::TIPO_PRESTAMO => sprintf('Se registro un prestamo para el equipo %s.', $reference),
            Movimiento::TIPO_DEVOLUCION => sprintf('Se registro la devolucion del equipo %s.', $reference),
            Movimiento::TIPO_MANTENIMIENTO => sprintf('Se registro un movimiento de mantenimiento para el equipo %s.', $reference),
            Movimiento::TIPO_BAJA => sprintf('Se registro la baja del equipo %s.', $reference),
            Movimiento::TIPO_TRANSFERENCIA_INTERNA => sprintf('Se registro una transferencia interna del equipo %s.', $reference),
            Movimiento::TIPO_TRANSFERENCIA_EXTERNA => sprintf('Se registro una transferencia externa del equipo %s.', $reference),
            default => sprintf('Se registro un traslado del equipo %s.', $reference),
        };
    }

    private function movementLevel(string $type): string
    {
        return match ($type) {
            Movimiento::TIPO_BAJA => AuditLog::LEVEL_CRITICAL,
            Movimiento::TIPO_MANTENIMIENTO, Movimiento::TIPO_TRANSFERENCIA_EXTERNA => AuditLog::LEVEL_WARNING,
            default => AuditLog::LEVEL_INFO,
        };
    }

    private function movementIsCritical(string $type): bool
    {
        return in_array($type, [Movimiento::TIPO_BAJA, Movimiento::TIPO_MANTENIMIENTO, Movimiento::TIPO_TRANSFERENCIA_EXTERNA], true);
    }

    private function movementLabel(string $type): string
    {
        return match ($type) {
            Movimiento::TIPO_PRESTAMO => 'Prestamo',
            Movimiento::TIPO_DEVOLUCION => 'Devolucion',
            Movimiento::TIPO_MANTENIMIENTO => 'Mantenimiento',
            Movimiento::TIPO_BAJA => 'Baja',
            Movimiento::TIPO_TRANSFERENCIA_INTERNA => 'Transferencia interna',
            Movimiento::TIPO_TRANSFERENCIA_EXTERNA => 'Transferencia externa',
            default => 'Traslado',
        };
    }

    private function officeLabel(?Office $office): ?string
    {
        if ($office === null) {
            return null;
        }

        return collect([
            $office->service?->institution?->nombre,
            $office->service?->nombre,
            $office->nombre,
        ])->filter()->implode(' / ');
    }

    private function equipmentReference(Equipo $equipo): string
    {
        return $equipo->reference();
    }

    private function estadoLabel(string $estado): string
    {
        return match ($estado) {
            Equipo::ESTADO_OPERATIVO => 'Operativo',
            Equipo::ESTADO_PRESTADO => 'Prestado',
            Equipo::ESTADO_MANTENIMIENTO, Equipo::ESTADO_EN_MANTENIMIENTO => 'Mantenimiento',
            Equipo::ESTADO_FUERA_DE_SERVICIO => 'Fuera de servicio',
            Equipo::ESTADO_BAJA => 'Baja',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
    }
}
