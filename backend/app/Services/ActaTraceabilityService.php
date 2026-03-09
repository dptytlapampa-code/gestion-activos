<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\EquipoHistorial;
use App\Models\EquipoStatus;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ActaTraceabilityService
{
    public function crear(User $user, array $data): Acta
    {
        $equipoIds = collect($data['equipos'])
            ->pluck('equipo_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($equipoIds->isEmpty()) {
            throw ValidationException::withMessages(['equipos' => 'Debe seleccionar al menos un equipo.']);
        }

        return DB::transaction(function () use ($user, $data, $equipoIds): Acta {
            $equipos = Equipo::query()
                ->with('oficina.service')
                ->whereIn('id', $equipoIds)
                ->lockForUpdate()
                ->get();

            if ($equipos->count() !== $equipoIds->count()) {
                throw ValidationException::withMessages([
                    'equipos' => 'Uno o mas equipos seleccionados no existen.',
                ]);
            }

            $this->validateScope($user, $data, $equipos);

            $institutionId = (int) ($data['institution_id'] ?? $user->institution_id);
            $institutionDestinoId = (int) ($data['institution_destino_id'] ?? 0);

            if (! $user->hasRole(User::ROLE_SUPERADMIN)) {
                $institutionId = (int) $user->institution_id;
                if ($institutionDestinoId > 0 && $institutionDestinoId !== (int) $user->institution_id) {
                    throw ValidationException::withMessages([
                        'institution_destino_id' => 'No tiene permisos para operar sobre otra institucion.',
                    ]);
                }
            }

            $acta = Acta::query()->create([
                'institution_id' => $institutionId,
                'institution_destino_id' => $institutionDestinoId > 0 ? $institutionDestinoId : null,
                'service_origen_id' => $this->nullableInt($data['service_origen_id'] ?? null),
                'office_origen_id' => $this->nullableInt($data['office_origen_id'] ?? null),
                'service_destino_id' => $this->nullableInt($data['service_destino_id'] ?? null),
                'office_destino_id' => $this->nullableInt($data['office_destino_id'] ?? null),
                'tipo' => $data['tipo'],
                'fecha' => $data['fecha'],
                'receptor_nombre' => $this->nullableString($data['receptor_nombre'] ?? null),
                'receptor_dni' => $data['receptor_dni'] ?? null,
                'receptor_cargo' => $data['receptor_cargo'] ?? null,
                'receptor_dependencia' => $data['receptor_dependencia'] ?? null,
                'motivo_baja' => $data['motivo_baja'] ?? null,
                'evento_payload' => $this->payload($data),
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $user->id,
            ]);

            $pivotPayload = collect($data['equipos'])
                ->mapWithKeys(fn (array $item): array => [
                    (int) $item['equipo_id'] => [
                        'cantidad' => (int) ($item['cantidad'] ?? 1),
                        'accesorios' => $item['accesorios'] ?? null,
                    ],
                ])
                ->all();

            $acta->equipos()->sync($pivotPayload);

            foreach ($equipos as $equipo) {
                $this->applyTransition($equipo, $acta, $user, $data);
            }

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'create',
                'auditable_type' => 'acta_equipo',
                'auditable_id' => $acta->id,
                'before' => null,
                'after' => [
                    'tipo' => $acta->tipo,
                    'equipos' => $pivotPayload,
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $acta->load([
                'institution',
                'institucionDestino',
                'servicioOrigen',
                'oficinaOrigen',
                'servicioDestino',
                'oficinaDestino',
                'creator',
                'equipos.tipoEquipo',
                'equipos.oficina.service.institution',
            ]);

            $pdfBinary = Pdf::loadView('actas.pdf.'.$acta->tipo, ['acta' => $acta])
                ->setPaper('a4')
                ->output();

            $path = sprintf('documents/%s/%s.pdf', now()->format('Y/m'), strtolower($acta->codigo));
            Storage::put($path, $pdfBinary);

            $acta->documents()->create([
                'uploaded_by' => $user->id,
                'type' => 'acta',
                'note' => sprintf('Acta %s %s', strtoupper($acta->tipo), $acta->codigo),
                'file_path' => $path,
                'original_name' => $acta->codigo.'.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($pdfBinary),
            ]);

            return $acta;
        });
    }

    private function validateScope(User $user, array $data, Collection $equipos): void
    {
        $tipo = (string) $data['tipo'];
        $institutionId = $this->nullableInt($data['institution_id'] ?? $user->institution_id);
        $institutionDestinoId = $this->nullableInt($data['institution_destino_id'] ?? null);

        foreach ($equipos as $equipo) {
            $ubicacion = $equipo->ubicacionActual();
            $equipoInstitutionId = (int) ($ubicacion['institucion_id'] ?? 0);

            if (! $user->hasRole(User::ROLE_SUPERADMIN) && $equipoInstitutionId !== (int) $user->institution_id) {
                throw ValidationException::withMessages([
                    'equipos' => 'No tiene permisos para operar equipos de otra institucion.',
                ]);
            }

            if ($institutionId !== null && $equipoInstitutionId !== $institutionId) {
                throw ValidationException::withMessages([
                    'equipos' => 'Todos los equipos deben pertenecer a la institucion origen del acta.',
                ]);
            }

            $this->assertTransitionAllowed($equipo, $tipo);

            if ($tipo === Acta::TIPO_TRASLADO) {
                $serviceOrigenId = $this->nullableInt($data['service_origen_id'] ?? null);
                $officeOrigenId = $this->nullableInt($data['office_origen_id'] ?? null);

                if ($serviceOrigenId !== null && (int) ($ubicacion['servicio_id'] ?? 0) !== $serviceOrigenId) {
                    throw ValidationException::withMessages([
                        'service_origen_id' => 'El servicio origen no coincide con la ubicacion actual del equipo seleccionado.',
                    ]);
                }

                if ($officeOrigenId !== null && (int) ($ubicacion['oficina_id'] ?? 0) !== $officeOrigenId) {
                    throw ValidationException::withMessages([
                        'office_origen_id' => 'La oficina origen no coincide con la ubicacion actual del equipo seleccionado.',
                    ]);
                }

                if (! $user->hasRole(User::ROLE_SUPERADMIN) && $institutionDestinoId !== null && $institutionDestinoId !== (int) $user->institution_id) {
                    throw ValidationException::withMessages([
                        'institution_destino_id' => 'No tiene permisos para trasladar equipos a otra institucion.',
                    ]);
                }
            }
        }
    }

    private function applyTransition(Equipo $equipo, Acta $acta, User $user, array $data): void
    {
        $anterior = $equipo->ubicacionActual();
        $estadoAnterior = $equipo->estado;

        $tipo = $acta->tipo;
        $estadoNuevo = $this->resolveEstadoNuevo($equipo, $tipo);

        $nuevaUbicacion = [
            'institucion_id' => $anterior['institucion_id'],
            'servicio_id' => $anterior['servicio_id'],
            'oficina_id' => $anterior['oficina_id'],
        ];

        if ($tipo === Acta::TIPO_TRASLADO) {
            $nuevaUbicacion = [
                'institucion_id' => $this->nullableInt($data['institution_destino_id'] ?? null),
                'servicio_id' => $this->nullableInt($data['service_destino_id'] ?? null),
                'oficina_id' => $this->nullableInt($data['office_destino_id'] ?? null),
            ];
        }

        if (in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_PRESTAMO, Acta::TIPO_MANTENIMIENTO, Acta::TIPO_DEVOLUCION], true)) {
            $nuevaUbicacion = [
                'institucion_id' => $this->nullableInt($data['institution_id'] ?? null),
                'servicio_id' => $this->nullableInt($data['service_destino_id'] ?? null),
                'oficina_id' => $this->nullableInt($data['office_destino_id'] ?? null),
            ];
        }

        if ($nuevaUbicacion['oficina_id'] !== null) {
            $equipo->oficina_id = $nuevaUbicacion['oficina_id'];
        }

        $equipo->estado = $estadoNuevo;
        $equipo->equipo_status_id = $this->resolveStatusIdByEstado($estadoNuevo);

        if ($equipo->offsetExists('_audit_before')) {
            $equipo->offsetUnset('_audit_before');
        }

        $equipo->save();

        EquipoHistorial::query()->create([
            'equipo_id' => $equipo->id,
            'usuario_id' => $user->id,
            'tipo_evento' => $tipo,
            'acta_id' => $acta->id,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'institucion_anterior' => $anterior['institucion_id'],
            'institucion_nueva' => $nuevaUbicacion['institucion_id'],
            'servicio_anterior' => $anterior['servicio_id'],
            'servicio_nuevo' => $nuevaUbicacion['servicio_id'],
            'oficina_anterior' => $anterior['oficina_id'],
            'oficina_nueva' => $nuevaUbicacion['oficina_id'],
            'fecha' => $acta->fecha?->toDateTimeString() ?? now()->toDateTimeString(),
            'observaciones' => $acta->observaciones,
        ]);
    }

    private function resolveEstadoNuevo(Equipo $equipo, string $tipo): string
    {
        return match ($tipo) {
            Acta::TIPO_ENTREGA => Equipo::ESTADO_OPERATIVO,
            Acta::TIPO_PRESTAMO => Equipo::ESTADO_PRESTADO,
            Acta::TIPO_MANTENIMIENTO => Equipo::ESTADO_EN_MANTENIMIENTO,
            Acta::TIPO_BAJA => Equipo::ESTADO_BAJA,
            Acta::TIPO_DEVOLUCION => Equipo::ESTADO_OPERATIVO,
            default => $equipo->estado,
        };
    }

    private function resolveStatusIdByEstado(string $estado): int
    {
        $code = match ($estado) {
            Equipo::ESTADO_PRESTADO => EquipoStatus::CODE_PRESTADA,
            Equipo::ESTADO_EN_MANTENIMIENTO => EquipoStatus::CODE_EN_SERVICIO_TECNICO,
            Equipo::ESTADO_FUERA_DE_SERVICIO => EquipoStatus::CODE_FUERA_DE_SERVICIO,
            Equipo::ESTADO_BAJA => EquipoStatus::CODE_BAJA,
            default => EquipoStatus::CODE_OPERATIVA,
        };

        return (int) EquipoStatus::query()->where('code', $code)->value('id');
    }

    private function assertTransitionAllowed(Equipo $equipo, string $tipo): void
    {
        $estado = (string) $equipo->estado;
        $identificador = $equipo->numero_serie ?: ('ID '.$equipo->id);

        if ($estado === Equipo::ESTADO_BAJA) {
            throw ValidationException::withMessages([
                'equipos' => "El equipo {$identificador} esta en BAJA y no admite nuevos eventos.",
            ]);
        }

        $allowed = match ($tipo) {
            Acta::TIPO_PRESTAMO, Acta::TIPO_TRASLADO => [Equipo::ESTADO_OPERATIVO],
            Acta::TIPO_MANTENIMIENTO => [Equipo::ESTADO_OPERATIVO, Equipo::ESTADO_PRESTADO],
            Acta::TIPO_BAJA => [Equipo::ESTADO_OPERATIVO, Equipo::ESTADO_EN_MANTENIMIENTO],
            Acta::TIPO_DEVOLUCION => [Equipo::ESTADO_PRESTADO],
            Acta::TIPO_ENTREGA => [Equipo::ESTADO_PRESTADO, Equipo::ESTADO_EN_MANTENIMIENTO],
            default => [],
        };

        if (! in_array($estado, $allowed, true)) {
            throw ValidationException::withMessages([
                'equipos' => "Transicion no permitida para el equipo {$identificador} desde estado {$estado} con evento {$tipo}.",
            ]);
        }
    }

    private function payload(array $data): array
    {
        return [
            'tipo' => $data['tipo'] ?? null,
            'fecha' => $data['fecha'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'institution_destino_id' => $data['institution_destino_id'] ?? null,
            'service_origen_id' => $data['service_origen_id'] ?? null,
            'office_origen_id' => $data['office_origen_id'] ?? null,
            'service_destino_id' => $data['service_destino_id'] ?? null,
            'office_destino_id' => $data['office_destino_id'] ?? null,
            'receptor_nombre' => $data['receptor_nombre'] ?? null,
            'receptor_dni' => $data['receptor_dni'] ?? null,
            'receptor_cargo' => $data['receptor_cargo'] ?? null,
            'receptor_dependencia' => $data['receptor_dependencia'] ?? null,
            'motivo_baja' => $data['motivo_baja'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cast = (int) $value;

        return $cast > 0 ? $cast : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}

