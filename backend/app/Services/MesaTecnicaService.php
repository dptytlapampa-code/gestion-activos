<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\RecepcionTecnica;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class MesaTecnicaService
{
    public function __construct(
        private readonly ActiveInstitutionContext $activeInstitutionContext,
    ) {}

    /**
     * @return array{
     *     accessibleInstitutions: Collection<int, \App\Models\Institution>,
     *     activeInstitutionId: int|null,
     *     enMesaCount: int,
     *     readyCount: int,
     *     pendingCount: int,
     *     finalizedCount: int,
     *     enMesaTickets: Collection<int, \App\Models\RecepcionTecnica>,
     *     readyTickets: Collection<int, \App\Models\RecepcionTecnica>,
     *     pendingTickets: Collection<int, \App\Models\RecepcionTecnica>,
     *     finalizedTickets: Collection<int, \App\Models\RecepcionTecnica>
     * }
     */
    public function dashboard(User $user): array
    {
        $query = $this->dashboardQuery($user);

        $enMesaStatuses = [
            RecepcionTecnica::ESTADO_RECIBIDO,
            RecepcionTecnica::ESTADO_EN_DIAGNOSTICO,
            RecepcionTecnica::ESTADO_EN_REPARACION,
        ];

        $readyTickets = (clone $query)
            ->where('estado', RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR)
            ->operationalOrder()
            ->limit(8)
            ->get();

        $enMesaTickets = (clone $query)
            ->whereIn('estado', $enMesaStatuses)
            ->operationalOrder()
            ->limit(8)
            ->get();

        $pendingTickets = (clone $query)
            ->where('estado', RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO)
            ->operationalOrder()
            ->limit(8)
            ->get();

        $finalizedTickets = (clone $query)
            ->history()
            ->orderByDesc('entregada_at')
            ->orderByDesc('status_changed_at')
            ->latest('id')
            ->limit(8)
            ->get();

        return [
            'accessibleInstitutions' => $this->activeInstitutionContext->accessibleInstitutions($user),
            'activeInstitutionId' => $this->activeInstitutionContext->currentId($user),
            'enMesaCount' => (clone $query)->whereIn('estado', $enMesaStatuses)->count(),
            'readyCount' => (clone $query)->where('estado', RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR)->count(),
            'pendingCount' => (clone $query)->where('estado', RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO)->count(),
            'finalizedCount' => (clone $query)->history()->count(),
            'enMesaTickets' => $enMesaTickets,
            'readyTickets' => $readyTickets,
            'pendingTickets' => $pendingTickets,
            'finalizedTickets' => $finalizedTickets,
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
    private function transformEquipo(Equipo $equipo): array
    {
        $equipo->loadMissing(['tipoEquipo:id,nombre', 'oficina.service.institution', 'recepcionTecnicaAbierta']);

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
            'ingreso_tecnico_abierto' => $equipo->recepcionTecnicaAbierta?->codigo,
        ];
    }

    private function dashboardQuery(User $user): Builder
    {
        return RecepcionTecnica::query()
            ->with([
                'creator:id,name',
                'procedenciaInstitution:id,nombre',
                'procedenciaService:id,nombre',
                'procedenciaOffice:id,nombre',
                'equipo:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
                'equipoCreado:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
            ])
            ->visibleToUser($user);
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
}
