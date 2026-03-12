<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\EquipoHistorial;
use App\Models\EquipoStatus;
use App\Models\Movimiento;
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
    public function __construct(private readonly ActaPdfDataService $actaPdfDataService) {}

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

            $origenesPorEquipo = $this->resolveOrigenesPorEquipo($equipos);
            $origenActa = $this->resolveOrigenActa($user, $origenesPorEquipo);
            $this->validateScope($user, $data, $equipos, $origenesPorEquipo);

            $tipo = (string) $data['tipo'];
            $destino = $this->resolveDestino($data, $origenActa, $tipo);

            $acta = Acta::query()->create([
                'institution_id' => $origenActa['institucion_id'],
                'institution_destino_id' => $destino['institucion_id'] ?? null,
                'service_origen_id' => $origenActa['servicio_id'],
                'office_origen_id' => $origenActa['oficina_id'],
                'service_destino_id' => $destino['servicio_id'] ?? null,
                'office_destino_id' => $destino['oficina_id'] ?? null,
                'tipo' => $tipo,
                'fecha' => $data['fecha'],
                'receptor_nombre' => $this->nullableString($data['receptor_nombre'] ?? null),
                'receptor_dni' => $this->nullableString($data['receptor_dni'] ?? null),
                'receptor_cargo' => $this->nullableString($data['receptor_cargo'] ?? null),
                'receptor_dependencia' => $this->nullableString($data['receptor_dependencia'] ?? null),
                'motivo_baja' => $this->nullableString($data['motivo_baja'] ?? null),
                'evento_payload' => $this->payload($data, $origenActa, $destino, $origenesPorEquipo),
                'observaciones' => $this->nullableString($data['observaciones'] ?? null),
                'status' => Acta::STATUS_ACTIVA,
                'created_by' => $user->id,
            ]);

            $pivotPayload = collect($data['equipos'])
                ->mapWithKeys(function (array $item) use ($origenesPorEquipo): array {
                    $equipoId = (int) $item['equipo_id'];
                    $origen = $origenesPorEquipo->get($equipoId);

                    if (! is_array($origen)) {
                        throw ValidationException::withMessages([
                            'equipos' => 'No se pudo resolver el origen de todos los equipos seleccionados.',
                        ]);
                    }

                    return [
                        $equipoId => [
                            'cantidad' => (int) ($item['cantidad'] ?? 1),
                            'accesorios' => $item['accesorios'] ?? null,
                            'institucion_origen_id' => $origen['institucion_id'] ?? null,
                            'institucion_origen_nombre' => $origen['institucion_nombre'] ?? null,
                            'servicio_origen_id' => $origen['servicio_id'] ?? null,
                            'servicio_origen_nombre' => $origen['servicio_nombre'] ?? null,
                            'oficina_origen_id' => $origen['oficina_id'] ?? null,
                            'oficina_origen_nombre' => $origen['oficina_nombre'] ?? null,
                        ],
                    ];
                })
                ->all();

            $acta->equipos()->sync($pivotPayload);

            $destinoOffice = null;
            if ($acta->office_destino_id !== null && in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_TRASLADO], true)) {
                $destinoOffice = Office::query()->with('service.institution')->find($acta->office_destino_id);
            }

            foreach ($equipos as $equipo) {
                $this->applyTransition($equipo, $acta, $user, $destinoOffice, $origenesPorEquipo->get($equipo->id));
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

            $pdfData = array_merge(['acta' => $acta], $this->actaPdfDataService->build($acta));

            $pdfBinary = Pdf::loadView('actas.pdf.'.$acta->tipo, $pdfData)
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

    private function validateScope(User $user, array $data, Collection $equipos, Collection $origenesPorEquipo): void
    {
        $tipo = (string) $data['tipo'];

        $equiposFueraDeAlcance = $equipos
            ->filter(function (Equipo $equipo) use ($user, $origenesPorEquipo): bool {
                $origen = $origenesPorEquipo->get($equipo->id);

                if (! is_array($origen)) {
                    return true;
                }

                return ! $user->canAccessInstitution((int) ($origen['institucion_id'] ?? 0));
            })
            ->values();

        if ($equiposFueraDeAlcance->isNotEmpty()) {
            $detalle = $equiposFueraDeAlcance
                ->take(5)
                ->map(function (Equipo $equipo) use ($origenesPorEquipo): string {
                    $origen = $origenesPorEquipo->get($equipo->id, []);
                    $identificador = $equipo->numero_serie ?: ('ID '.$equipo->id);
                    $institucion = $origen['institucion_nombre'] ?? 'institucion no identificada';

                    return sprintf('%s (%s)', $identificador, $institucion);
                })
                ->implode(', ');

            $extra = $equiposFueraDeAlcance->count() > 5
                ? sprintf(' y %d equipo(s) mas.', $equiposFueraDeAlcance->count() - 5)
                : '.';

            throw ValidationException::withMessages([
                'equipos' => 'No tiene permisos sobre algunos equipos seleccionados: '.$detalle.$extra,
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
            if ($serviceDestinoId === null || $officeDestinoId === null) {
                throw ValidationException::withMessages([
                    'service_destino_id' => 'El traslado requiere servicio y oficina destino.',
                ]);
            }

            $service = Service::query()->find($serviceDestinoId);
            if ($service === null) {
                throw ValidationException::withMessages([
                    'service_destino_id' => 'Debe seleccionar un servicio destino valido.',
                ]);
            }

            if ($institutionDestinoId !== null && (int) $service->institution_id !== $institutionDestinoId) {
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
    }

    /**
     * @param  array{institucion_id:int,servicio_id:int|null,oficina_id:int|null}  $origenSnapshot
     */
    private function applyTransition(Equipo $equipo, Acta $acta, User $user, ?Office $destinoOffice, array $origenSnapshot): void
    {
        $anterior = [
            'institucion_id' => $origenSnapshot['institucion_id'] ?? null,
            'servicio_id' => $origenSnapshot['servicio_id'] ?? null,
            'oficina_id' => $origenSnapshot['oficina_id'] ?? null,
        ];

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

        $this->registrarMovimientoDesdeActa($equipo, $acta, $user, $anterior, $nuevaUbicacion);

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

    /**
     * @param  array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}  $origen
     * @param  array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}  $destino
     */
    private function registrarMovimientoDesdeActa(Equipo $equipo, Acta $acta, User $user, array $origen, array $destino): void
    {
        $tipoMovimiento = $this->resolveTipoMovimiento($acta->tipo);

        if ($tipoMovimiento === null) {
            return;
        }

        $usaDestino = in_array($acta->tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_TRASLADO, Acta::TIPO_DEVOLUCION], true);

        Movimiento::query()->create([
            'equipo_id' => $equipo->id,
            'user_id' => $user->id,
            'acta_id' => $acta->id,
            'tipo_movimiento' => $tipoMovimiento,
            'fecha' => $acta->fecha?->toDateTimeString() ?? now()->toDateTimeString(),
            'institucion_origen_id' => $origen['institucion_id'],
            'servicio_origen_id' => $origen['servicio_id'],
            'oficina_origen_id' => $origen['oficina_id'],
            'institucion_destino_id' => $usaDestino ? $destino['institucion_id'] : null,
            'servicio_destino_id' => $usaDestino ? $destino['servicio_id'] : null,
            'oficina_destino_id' => $usaDestino ? $destino['oficina_id'] : null,
            'receptor_nombre' => in_array($acta->tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_PRESTAMO], true) ? $acta->receptor_nombre : null,
            'receptor_dni' => in_array($acta->tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_PRESTAMO], true) ? $acta->receptor_dni : null,
            'receptor_cargo' => in_array($acta->tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_PRESTAMO], true) ? $acta->receptor_cargo : null,
            'fecha_inicio_prestamo' => $acta->tipo === Acta::TIPO_PRESTAMO ? $acta->fecha : null,
            'fecha_estimada_devolucion' => null,
            'fecha_devolucion_real' => null,
            'observacion' => $acta->observaciones,
        ]);
    }

    private function resolveTipoMovimiento(string $tipoActa): ?string
    {
        return match ($tipoActa) {
            Acta::TIPO_ENTREGA, Acta::TIPO_TRASLADO => Movimiento::TIPO_TRASLADO,
            Acta::TIPO_PRESTAMO => Movimiento::TIPO_PRESTAMO,
            Acta::TIPO_MANTENIMIENTO => Movimiento::TIPO_MANTENIMIENTO,
            Acta::TIPO_BAJA => Movimiento::TIPO_BAJA,
            Acta::TIPO_DEVOLUCION => Movimiento::TIPO_DEVOLUCION,
            default => null,
        };
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
     * @return Collection<int, array{institucion_id:int,institucion_nombre:string,servicio_id:int,servicio_nombre:string,oficina_id:int,oficina_nombre:string}>
     */
    private function resolveOrigenesPorEquipo(Collection $equipos): Collection
    {
        return $equipos
            ->mapWithKeys(function (Equipo $equipo): array {
                $institucion = $equipo->oficina?->service?->institution;
                $servicio = $equipo->oficina?->service;
                $oficina = $equipo->oficina;

                $institucionId = (int) ($institucion?->id ?? 0);
                $servicioId = (int) ($servicio?->id ?? 0);
                $oficinaId = (int) ($oficina?->id ?? 0);

                if ($institucionId <= 0 || $servicioId <= 0 || $oficinaId <= 0) {
                    throw ValidationException::withMessages([
                        'equipos' => 'Todos los equipos deben tener una ubicacion de origen valida.',
                    ]);
                }

                return [
                    $equipo->id => [
                        'institucion_id' => $institucionId,
                        'institucion_nombre' => (string) ($institucion?->nombre ?? '-'),
                        'servicio_id' => $servicioId,
                        'servicio_nombre' => (string) ($servicio?->nombre ?? '-'),
                        'oficina_id' => $oficinaId,
                        'oficina_nombre' => (string) ($oficina?->nombre ?? '-'),
                    ],
                ];
            })
            ->sortKeys();
    }

    /**
     * @param  Collection<int, array{institucion_id:int,institucion_nombre:string,servicio_id:int,servicio_nombre:string,oficina_id:int,oficina_nombre:string}>  $origenesPorEquipo
     * @return array{institucion_id:int,servicio_id:int|null,oficina_id:int|null,origen_multiple:bool,instituciones_ids:array<int,int>}
     */
    private function resolveOrigenActa(User $user, Collection $origenesPorEquipo): array
    {
        $instituciones = $origenesPorEquipo
            ->pluck('institucion_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $servicios = $origenesPorEquipo
            ->pluck('servicio_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $oficinas = $origenesPorEquipo
            ->pluck('oficina_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $institucionActaId = $instituciones->count() === 1
            ? (int) $instituciones->first()
            : ($user->institution_id !== null && $instituciones->contains((int) $user->institution_id)
                ? (int) $user->institution_id
                : (int) $instituciones->first());

        if ($institucionActaId <= 0) {
            throw ValidationException::withMessages([
                'equipos' => 'No fue posible determinar la institucion administrativa del acta.',
            ]);
        }

        return [
            'institucion_id' => $institucionActaId,
            'servicio_id' => $servicios->count() === 1 ? (int) $servicios->first() : null,
            'oficina_id' => $oficinas->count() === 1 ? (int) $oficinas->first() : null,
            'origen_multiple' => $instituciones->count() > 1,
            'instituciones_ids' => $instituciones->all(),
        ];
    }

    /**
     * @param  array{institucion_id:int,servicio_id:int|null,oficina_id:int|null,origen_multiple:bool,instituciones_ids:array<int,int>}  $origen
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

        $serviceDestino = Service::query()->find($serviceDestinoId);

        if ($serviceDestino === null) {
            throw ValidationException::withMessages([
                'service_destino_id' => 'Debe seleccionar un servicio destino valido.',
            ]);
        }

        $officeDestino = Office::query()->find($officeDestinoId);

        if ($officeDestino === null || (int) $officeDestino->service_id !== $serviceDestinoId) {
            throw ValidationException::withMessages([
                'office_destino_id' => 'La oficina destino no pertenece al servicio destino.',
            ]);
        }

        $institucionDestinoId = $tipo === Acta::TIPO_ENTREGA
            ? $this->nullableInt($data['institution_destino_id'] ?? null)
            : ($this->nullableInt($data['institution_destino_id'] ?? null) ?? (int) $serviceDestino->institution_id);

        if ($institucionDestinoId === null) {
            throw ValidationException::withMessages([
                'institution_destino_id' => 'Debe seleccionar la institucion destino.',
            ]);
        }

        if ((int) $serviceDestino->institution_id !== $institucionDestinoId) {
            throw ValidationException::withMessages([
                'service_destino_id' => 'El servicio destino no pertenece a la institucion destino.',
            ]);
        }

        return [
            'institucion_id' => $institucionDestinoId,
            'servicio_id' => $serviceDestinoId,
            'oficina_id' => $officeDestinoId,
        ];
    }

    /**
     * @param  array{institucion_id:int,servicio_id:int|null,oficina_id:int|null,origen_multiple:bool,instituciones_ids:array<int,int>}  $origen
     * @param  array{institucion_id:int,servicio_id:int,oficina_id:int}|null  $destino
     * @param  Collection<int, array{institucion_id:int,institucion_nombre:string,servicio_id:int,servicio_nombre:string,oficina_id:int,oficina_nombre:string}>  $origenesPorEquipo
     */
    private function payload(array $data, array $origen, ?array $destino, Collection $origenesPorEquipo): array
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
            'origen_multiple' => $origen['origen_multiple'],
            'instituciones_origen_ids' => $origen['instituciones_ids'],
            'origenes_por_equipo' => $origenesPorEquipo
                ->mapWithKeys(fn (array $item, int $equipoId): array => [(string) $equipoId => $item])
                ->all(),
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

