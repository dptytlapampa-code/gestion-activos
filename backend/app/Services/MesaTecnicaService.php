<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class MesaTecnicaService
{
    public function __construct(
        private readonly ActaTraceabilityService $actaTraceabilityService,
        private readonly ActiveInstitutionContext $activeInstitutionContext,
    ) {}

    /**
     * @return array{
     *     accessibleInstitutions: Collection<int, \App\Models\Institution>,
     *     activeInstitutionId: int|null,
     *     recentActas: Collection<int, array<string, mixed>>,
     *     recentMovements: Collection<int, array<string, mixed>>
     * }
     */
    public function dashboard(User $user): array
    {
        $recentActas = Acta::query()
            ->withCount('equipos')
            ->with(['creator:id,name', 'institution:id,nombre', 'institucionDestino:id,nombre'])
            ->visibleToUser($user)
            ->latest('fecha')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(function (Acta $acta): array {
                return [
                    'id' => $acta->id,
                    'codigo' => $acta->codigo,
                    'tipo' => $acta->tipo,
                    'tipo_label' => $this->actaTypeLabel($acta->tipo),
                    'fecha' => $acta->fecha?->format('d/m/Y') ?? '-',
                    'status' => $acta->status ?? Acta::STATUS_ACTIVA,
                    'status_label' => ucfirst((string) ($acta->status ?? Acta::STATUS_ACTIVA)),
                    'institution' => $acta->institution?->nombre ?? 'Sin institucion',
                    'destination' => $acta->institucionDestino?->nombre,
                    'creator' => $acta->creator?->name ?? 'Sin usuario',
                    'equipos_count' => (int) $acta->equipos_count,
                ];
            })
            ->values();

        $recentMovements = Movimiento::query()
            ->with([
                'equipo:id,tipo,marca,modelo,numero_serie,codigo_interno,oficina_id',
                'user:id,name',
                'acta:id,institution_id,tipo,fecha,status',
            ])
            ->whereHas('equipo', fn ($query) => $query->visibleToUser($user))
            ->latest('fecha')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (Movimiento $movimiento): array {
                $equipo = $movimiento->equipo;

                return [
                    'id' => $movimiento->id,
                    'fecha' => $movimiento->fecha?->format('d/m/Y H:i') ?? '-',
                    'tipo' => $movimiento->tipo_movimiento,
                    'tipo_label' => $this->movementLabel((string) $movimiento->tipo_movimiento),
                    'observacion' => $this->nullableString($movimiento->observacion) ?? 'Sin observaciones',
                    'usuario' => $movimiento->user?->name ?? 'Sin usuario',
                    'acta_id' => $movimiento->acta?->id,
                    'acta_codigo' => $movimiento->acta?->codigo,
                    'equipo_id' => $equipo?->id,
                    'equipo_label' => $equipo?->tipo ?? 'Equipo',
                    'equipo_reference' => $equipo?->reference() ?? 'Equipo sin referencia visible',
                    'codigo_interno' => $equipo?->codigo_interno,
                ];
            })
            ->values();

        return [
            'accessibleInstitutions' => $this->activeInstitutionContext->accessibleInstitutions($user),
            'activeInstitutionId' => $this->activeInstitutionContext->currentId($user),
            'recentActas' => $recentActas,
            'recentMovements' => $recentMovements,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function selectedEquipo(?User $user, mixed $equipoId): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        $normalizedId = (int) $equipoId;

        if ($normalizedId <= 0) {
            return null;
        }

        $equipo = Equipo::query()
            ->visibleToUser($user)
            ->with(['tipoEquipo:id,nombre', 'oficina.service.institution'])
            ->find($normalizedId);

        return $equipo instanceof Equipo ? $this->transformEquipo($equipo) : null;
    }

    public function registrarRecepcion(User $user, array $data): Acta
    {
        $equipo = $this->resolveOperableEquipo($user, (int) $data['equipo_id']);

        if ((string) $equipo->estado !== Equipo::ESTADO_PRESTADO) {
            throw ValidationException::withMessages([
                'equipo_id' => 'En esta primera etapa solo puede recepcionarse un equipo que se encuentre prestado.',
            ]);
        }

        return $this->actaTraceabilityService->crear($user, [
            'tipo' => Acta::TIPO_DEVOLUCION,
            'fecha' => $data['fecha'],
            'observaciones' => $this->mergeReceptionNotes(
                $data['motivo'] ?? null,
                $data['observaciones'] ?? null
            ),
            'equipos' => [
                [
                    'equipo_id' => $equipo->id,
                    'cantidad' => 1,
                ],
            ],
        ]);
    }

    public function registrarEntrega(User $user, array $data): Acta
    {
        $equipo = $this->resolveOperableEquipo($user, (int) $data['equipo_id']);

        if ((string) $equipo->estado === Equipo::ESTADO_BAJA) {
            throw ValidationException::withMessages([
                'equipo_id' => 'Este equipo no puede entregarse porque ya se encuentra dado de baja.',
            ]);
        }

        return $this->actaTraceabilityService->crear($user, [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => $data['fecha'],
            'institution_destino_id' => (int) $data['institution_destino_id'],
            'service_destino_id' => (int) $data['service_destino_id'],
            'office_destino_id' => (int) $data['office_destino_id'],
            'receptor_nombre' => $this->nullableString($data['receptor_nombre'] ?? null),
            'receptor_dni' => $this->nullableString($data['receptor_dni'] ?? null),
            'receptor_cargo' => $this->nullableString($data['receptor_cargo'] ?? null),
            'receptor_dependencia' => $this->nullableString($data['receptor_dependencia'] ?? null),
            'observaciones' => $this->nullableString($data['observaciones'] ?? null),
            'equipos' => [
                [
                    'equipo_id' => $equipo->id,
                    'cantidad' => 1,
                ],
            ],
        ]);
    }

    /**
     * @return array{
     *     equipo: Equipo,
     *     qrSvg: string|null,
     *     publicUrl: string|null,
     *     location: string,
     *     estadoLabel: string,
     *     generatedAt: string
     * }
     */
    public function labelData(Equipo $equipo): array
    {
        $equipo->loadMissing(['tipoEquipo:id,nombre', 'oficina.service.institution']);

        $publicUrl = $equipo->uuid
            ? route('equipos.public.show', ['uuid' => $equipo->uuid])
            : null;
        $qrSvg = null;

        if ($publicUrl !== null && $publicUrl !== '') {
            try {
                $qrSvg = QrCode::size(170)
                    ->margin(1)
                    ->generate($publicUrl);
            } catch (Throwable $exception) {
                Log::warning('mesa tecnica qr generation failed', [
                    'equipo_id' => $equipo->id,
                    'equipo_uuid' => $equipo->uuid,
                    'url' => $publicUrl,
                    'error' => $exception->getMessage(),
                    'exception' => get_class($exception),
                ]);
            }
        }

        return [
            'equipo' => $equipo,
            'qrSvg' => $qrSvg,
            'publicUrl' => $publicUrl,
            'location' => collect([
                $equipo->oficina?->service?->institution?->nombre,
                $equipo->oficina?->service?->nombre,
                $equipo->oficina?->nombre,
            ])->filter()->implode(' / '),
            'estadoLabel' => $this->estadoLabel((string) $equipo->estado),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function operationResult(Acta $acta): array
    {
        $acta->loadMissing(['equipos.tipoEquipo', 'equipos.oficina.service.institution']);

        $equipo = $acta->equipos->first();

        return [
            'acta_id' => $acta->id,
            'acta_codigo' => $acta->codigo,
            'acta_tipo' => $this->actaTypeLabel($acta->tipo),
            'equipo_id' => $equipo?->id,
            'equipo_reference' => $equipo?->reference(),
            'codigo_interno' => $equipo?->codigo_interno,
        ];
    }

    private function resolveOperableEquipo(User $user, int $equipoId): Equipo
    {
        $equipo = Equipo::query()
            ->visibleToUser($user)
            ->with(['tipoEquipo:id,nombre', 'oficina.service.institution'])
            ->find($equipoId);

        if (! $equipo instanceof Equipo) {
            throw ValidationException::withMessages([
                'equipo_id' => 'No se encontro un equipo valido dentro del alcance actual.',
            ]);
        }

        return $equipo;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEquipo(Equipo $equipo): array
    {
        $equipo->loadMissing(['tipoEquipo:id,nombre', 'oficina.service.institution']);

        $tipo = $this->nullableString($equipo->tipo)
            ?? $this->nullableString($equipo->tipoEquipo?->nombre)
            ?? 'Equipo';

        return [
            'id' => $equipo->id,
            'label' => trim(collect([$tipo, $equipo->marca, $equipo->modelo])->filter()->implode(' ')),
            'tipo' => $tipo,
            'marca' => $equipo->marca,
            'modelo' => $equipo->modelo,
            'numero_serie' => $equipo->numero_serie,
            'bien_patrimonial' => $equipo->bien_patrimonial,
            'codigo_interno' => $equipo->codigo_interno,
            'estado' => $equipo->estado,
            'estado_label' => $this->estadoLabel((string) $equipo->estado),
            'institucion' => $equipo->oficina?->service?->institution?->nombre,
            'institucion_id' => $equipo->oficina?->service?->institution?->id,
            'servicio' => $equipo->oficina?->service?->nombre,
            'servicio_id' => $equipo->oficina?->service?->id,
            'oficina' => $equipo->oficina?->nombre,
            'oficina_id' => $equipo->oficina?->id,
            'ubicacion_resumida' => collect([
                $equipo->oficina?->service?->institution?->nombre,
                $equipo->oficina?->service?->nombre,
                $equipo->oficina?->nombre,
            ])->filter()->implode(' / '),
        ];
    }

    private function mergeReceptionNotes(mixed $reason, mixed $observations): ?string
    {
        $reasonText = $this->nullableString($reason);
        $observationsText = $this->nullableString($observations);

        $notes = collect([
            $reasonText !== null ? 'Motivo de recepcion: '.$reasonText : null,
            $observationsText,
        ])->filter();

        return $notes->isEmpty() ? null : $notes->implode("\n");
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function estadoLabel(string $estado): string
    {
        return match ($estado) {
            Equipo::ESTADO_OPERATIVO => 'Operativo',
            Equipo::ESTADO_PRESTADO => 'Prestado',
            Equipo::ESTADO_EN_MANTENIMIENTO => 'Mantenimiento',
            Equipo::ESTADO_FUERA_DE_SERVICIO => 'Fuera de servicio',
            Equipo::ESTADO_BAJA => 'Baja',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
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

    private function actaTypeLabel(string $type): string
    {
        return ucfirst(strtolower(Acta::LABELS[$type] ?? strtoupper($type)));
    }
}
