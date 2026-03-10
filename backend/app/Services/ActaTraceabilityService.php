<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\EquipoHistorial;
use App\Models\EquipoStatus;
use App\Models\Office;
use App\Models\Service;
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
                ->with('oficina.service.institution')
                ->whereIn('id', $equipoIds)
                ->lockForUpdate()
                ->get();

            if ($equipos->count() !== $equipoIds->count()) {
                throw ValidationException::withMessages([
                    'equipos' => 'Uno o mas equipos seleccionados no existen.',
                ]);
            }

            $origen = $this->resolveOrigen($equipos);
            $this->validateScope($user, $data, $equipos, $origen);

            $tipo = (string) $data['tipo'];
            $destino = $this->resolveDestino($data, $origen, $tipo);

            $acta = Acta::query()->create([
                'institution_id' => $origen['institucion_id'],
                'institution_destino_id' => $destino['institucion_id'] ?? null,
                'service_origen_id' => $origen['servicio_id'],
                'office_origen_id' => $origen['oficina_id'],
                'service_destino_id' => $destino['servicio_id'] ?? null,
                'office_destino_id' => $destino['oficina_id'] ?? null,
                'tipo' => $tipo,
                'fecha' => $data['fecha'],
                'receptor_nombre' => $this->nullableString($data['receptor_nombre'] ?? null),
                'receptor_dni' => $this->nullableString($data['receptor_dni'] ?? null),
                'receptor_cargo' => $this->nullableString($data['receptor_cargo'] ?? null),
                'receptor_dependencia' => $this->nullableString($data['receptor_dependencia'] ?? null),
                'motivo_baja' => $this->nullableString($data['motivo_baja'] ?? null),
                'evento_payload' => $this->payload($data, $origen, $destino),
                'observaciones' => $this->nullableString($data['observaciones'] ?? null),
                'status' => Acta::STATUS_ACTIVA,
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

            $destinoOffice = null;
            if ($acta->office_destino_id !== null && in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_TRASLADO], true)) {
                $destinoOffice = Office::query()->with('service.institution')->find($acta->office_destino_id);
            }

            foreach ($equipos as $equipo) {
                $this->applyTransition($equipo, $acta, $user, $destinoOffice);
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

    private function validateScope(User $user, array $data, Collection $equipos, array $origen): void
    {
        $tipo = (string) $data['tipo'];
        $institutionOrigenId = (int) $origen['institucion_id'];

        if (! $user->hasRole(User::ROLE_SUPERADMIN) && $institutionOrigenId !== (int) $user->institution_id) {
            throw ValidationException::withMessages([
                'equipos' => 'No tiene permisos para operar equipos de otra institucion.',
            ]);
        }

        foreach ($equipos as $equipo) {
            $this->assertTransitionAllowed($equipo, $tipo);
        }

        $institutionDestinoId = $this->nullableInt($data['institution_destino_id'] ?? null);
        $serviceDestinoId = $this->nullableInt($data['service_destino_id'] ?? null);
        $officeDestinoId = $this->nullableInt($data['office_destino_id'] ?? null);

        if ($tipo === Acta::TIPO_ENTREGA) {
            if ($institutionDestinoId === null || $serviceDestinoId === null || $officeDestinoId === null) {
                throw ValidationException::withMessages([
                    'service_destino_id' => 'La entrega requiere institucion, servicio y oficina destino.',
                ]);
            }

            $service = Service::query()->find($serviceDestinoId);
            if ($service === null || (int) $service->institution_id !== $institutionDestinoId) {
                throw ValidationException::withMessages([
                    'service_destino_id' => 'El servicio destino no pertenece a la institucion destino.',
                ]);
            }

            $office = Office::query()->find($officeDestinoId);
            if ($office === null || (int) $office->service_id !== $serviceDestinoId) {
                throw ValidationException::withMessages([
                    'office_destino_id' => 'La oficina destino no pertenece al servicio destino.',
                ]);
            }
        }

        if ($tipo === Acta::TIPO_TRASLADO) {
            if ($institutionDestinoId !== null) {
                throw ValidationException::withMessages([
                    'institution_destino_id' => 'El traslado no permite informar institucion destino.',
                ]);
            }

            if ($serviceDestinoId === null || $officeDestinoId === null) {
                throw ValidationException::withMessages([
                    'service_destino_id' => 'El traslado requiere servicio y oficina destino.',
                ]);
            }

            $service = Service::query()->find($serviceDestinoId);
            if ($service === null || (int) $service->institution_id !== $institutionOrigenId) {
                throw ValidationException::withMessages([
                    'service_destino_id' => 'No se permite traslado entre instituciones.',
                ]);
            }

            $office = Office::query()->find($officeDestinoId);
            if ($office === null || (int) $office->service_id !== $serviceDestinoId) {
                throw ValidationException::withMessages([
                    'office_destino_id' => 'La oficina destino no pertenece al servicio destino.',
                ]);
            }
        }
    }

    private function applyTransition(Equipo $equipo, Acta $acta, User $user, ?Office $destinoOffice): void
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

        if (in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_TRASLADO], true) && $destinoOffice !== null) {
            $equipo->oficina_id = $destinoOffice->id;
            $nuevaUbicacion = [
                'institucion_id' => $destinoOffice->service?->institution?->id,
                'servicio_id' => $destinoOffice->service?->id,
                'oficina_id' => $destinoOffice->id,
            ];
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
            Acta::TIPO_ENTREGA => [Equipo::ESTADO_OPERATIVO, Equipo::ESTADO_PRESTADO, Equipo::ESTADO_EN_MANTENIMIENTO],
            default => [],
        };

        if (! in_array($estado, $allowed, true)) {
            throw ValidationException::withMessages([
                'equipos' => "Transicion no permitida para el equipo {$identificador} desde estado {$estado} con evento {$tipo}.",
            ]);
        }
    }

    /**
     * @return array{institucion_id:int,servicio_id:int|null,oficina_id:int|null}
     */
    private function resolveOrigen(Collection $equipos): array
    {
        $instituciones = [];
        $servicios = [];
        $oficinas = [];

        foreach ($equipos as $equipo) {
            $ubicacion = $equipo->ubicacionActual();
            $institucionId = (int) ($ubicacion['institucion_id'] ?? 0);
            $servicioId = (int) ($ubicacion['servicio_id'] ?? 0);
            $oficinaId = (int) ($ubicacion['oficina_id'] ?? 0);

            if ($institucionId <= 0 || $servicioId <= 0 || $oficinaId <= 0) {
                throw ValidationException::withMessages([
                    'equipos' => 'Todos los equipos deben tener una ubicacion de origen valida.',
                ]);
            }

            $instituciones[$institucionId] = true;
            $servicios[$servicioId] = true;
            $oficinas[$oficinaId] = true;
        }

        if (count($instituciones) !== 1) {
            throw ValidationException::withMessages([
                'equipos' => 'Todos los equipos seleccionados deben pertenecer a la misma institucion de origen.',
            ]);
        }

        $institucionId = (int) array_key_first($instituciones);

        return [
            'institucion_id' => $institucionId,
            'servicio_id' => count($servicios) === 1 ? (int) array_key_first($servicios) : null,
            'oficina_id' => count($oficinas) === 1 ? (int) array_key_first($oficinas) : null,
        ];
    }

    /**
     * @param  array{institucion_id:int,servicio_id:int|null,oficina_id:int|null}  $origen
     * @return array{institucion_id:int,servicio_id:int,oficina_id:int}|null
     */
    private function resolveDestino(array $data, array $origen, string $tipo): ?array
    {
        if (! in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_TRASLADO], true)) {
            return null;
        }

        $serviceDestinoId = $this->nullableInt($data['service_destino_id'] ?? null);
        $officeDestinoId = $this->nullableInt($data['office_destino_id'] ?? null);

        if ($serviceDestinoId === null || $officeDestinoId === null) {
            throw ValidationException::withMessages([
                'service_destino_id' => 'Debe completar servicio y oficina destino.',
            ]);
        }

        $institucionDestinoId = $tipo === Acta::TIPO_ENTREGA
            ? $this->nullableInt($data['institution_destino_id'] ?? null)
            : (int) $origen['institucion_id'];

        if ($institucionDestinoId === null) {
            throw ValidationException::withMessages([
                'institution_destino_id' => 'Debe seleccionar la institucion destino.',
            ]);
        }

        return [
            'institucion_id' => $institucionDestinoId,
            'servicio_id' => $serviceDestinoId,
            'oficina_id' => $officeDestinoId,
        ];
    }

    /**
     * @param  array{institucion_id:int,servicio_id:int|null,oficina_id:int|null}  $origen
     * @param  array{institucion_id:int,servicio_id:int,oficina_id:int}|null  $destino
     */
    private function payload(array $data, array $origen, ?array $destino): array
    {
        return [
            'tipo' => $data['tipo'] ?? null,
            'fecha' => $data['fecha'] ?? null,
            'institution_id' => $origen['institucion_id'],
            'institution_destino_id' => $destino['institucion_id'] ?? null,
            'service_origen_id' => $origen['servicio_id'],
            'office_origen_id' => $origen['oficina_id'],
            'service_destino_id' => $destino['servicio_id'] ?? null,
            'office_destino_id' => $destino['oficina_id'] ?? null,
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
