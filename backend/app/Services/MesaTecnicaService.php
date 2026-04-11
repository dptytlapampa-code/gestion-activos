<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\RecepcionTecnica;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class MesaTecnicaService
{
    private const QUEUE_ALL = 'all';
    private const QUEUE_CRITICAL = 'critical';
    private const QUEUE_DELAYED = 'delayed';
    private const QUEUE_READY = 'ready';
    private const QUEUE_RECENT = 'recent';

    private const QUEUE_FILTERS = [
        self::QUEUE_ALL,
        self::QUEUE_CRITICAL,
        self::QUEUE_DELAYED,
        self::QUEUE_READY,
        self::QUEUE_RECENT,
    ];

    public function __construct(
        private readonly ActiveInstitutionContext $activeInstitutionContext,
    ) {}

    /**
     * @param  array{selectedQueue?: mixed}  $context
     * @return array<string, mixed>
     */
    public function dashboard(User $user, array $context = []): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $recentWindowStart = $now->copy()->subDays(30);
        $selectedQueue = $this->normalizeQueue($context['selectedQueue'] ?? null);

        $query = $this->dashboardQuery($user);
        $openTickets = (clone $query)
            ->open()
            ->operationalOrder()
            ->get();

        $recentTickets = (clone $query)
            ->where('ingresado_at', '>=', $recentWindowStart)
            ->orderByDesc('ingresado_at')
            ->get();

        $closedRecentTickets = (clone $query)
            ->history()
            ->whereNotNull('entregada_at')
            ->where('entregada_at', '>=', $recentWindowStart)
            ->orderByDesc('entregada_at')
            ->get();

        $reincidentCounts = $this->reincidentCounts($user);

        $evaluatedQueue = $openTickets
            ->map(fn (RecepcionTecnica $recepcion): array => $this->evaluateQueueTicket($recepcion, $now, $reincidentCounts))
            ->values();

        $sortedQueue = $evaluatedQueue
            ->sort(fn (array $left, array $right): int => $this->compareQueueItems($left, $right))
            ->values();

        $criticalQueue = $sortedQueue->where('priority', self::QUEUE_CRITICAL)->values();
        $delayedQueue = $sortedQueue->where('priority', self::QUEUE_DELAYED)->values();
        $readyQueue = $sortedQueue->where('priority', self::QUEUE_READY)->values();
        $recentQueue = $sortedQueue->where('priority', self::QUEUE_RECENT)->values();

        $queueCounts = [
            self::QUEUE_ALL => $sortedQueue->count(),
            self::QUEUE_CRITICAL => $criticalQueue->count(),
            self::QUEUE_DELAYED => $delayedQueue->count(),
            self::QUEUE_READY => $readyQueue->count(),
            self::QUEUE_RECENT => $recentQueue->count(),
        ];

        $queueItems = match ($selectedQueue) {
            self::QUEUE_CRITICAL => $criticalQueue,
            self::QUEUE_DELAYED => $delayedQueue,
            self::QUEUE_READY => $readyQueue,
            self::QUEUE_RECENT => $recentQueue,
            default => $sortedQueue,
        };

        $queueItems = $queueItems->take(12)->values();

        $readyCount = $openTickets->where('estado', RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR)->count();
        $todayIngressCount = (clone $query)->whereDate('ingresado_at', $today)->count();
        $todayDeliveryCount = (clone $query)->whereDate('entregada_at', $today)->count();
        $delayedCount = $delayedQueue->count();
        $criticalCount = $criticalQueue->count();

        $alerts = $this->buildAlerts(
            $sortedQueue,
            $criticalQueue,
            $readyQueue
        );

        $flowBalance = $todayIngressCount - $todayDeliveryCount;
        $flowTone = $flowBalance >= 4
            ? 'danger'
            : ($flowBalance > 0 ? 'warning' : 'success');

        $openAverageHours = (int) round($openTickets->avg(fn (RecepcionTecnica $recepcion): int => $this->hoursBetween($recepcion->ingresado_at, $now)) ?? 0);
        $closedAverageHours = (int) round($closedRecentTickets->avg(fn (RecepcionTecnica $recepcion): int => $this->hoursBetween($recepcion->ingresado_at, $recepcion->entregada_at)) ?? 0);

        return [
            'accessibleInstitutions' => $this->activeInstitutionContext->accessibleInstitutions($user),
            'activeInstitutionId' => $this->activeInstitutionContext->currentId($user),
            'selectedQueue' => $selectedQueue,
            'kpis' => [
                [
                    'label' => 'Equipos en mesa',
                    'value' => $sortedQueue->count(),
                    'context' => $sortedQueue->count() === 1 ? '1 ticket activo visible.' : sprintf('%d tickets activos visibles.', $sortedQueue->count()),
                    'hint' => $criticalCount > 0
                        ? sprintf('%d critico(s) requieren resolucion inmediata.', $criticalCount)
                        : 'Carga activa total de la mesa tecnica.',
                    'tone' => $this->metricTone($sortedQueue->count(), 8, 15),
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_ALL], 'mt-cola-operativa'),
                ],
                [
                    'label' => 'Listos para entregar',
                    'value' => $readyCount,
                    'context' => $readyQueue->count() > 0
                        ? sprintf('%d aun esperan salida operativa.', $readyQueue->count())
                        : 'No hay equipos listos pendientes.',
                    'hint' => $readyQueue->where('flags.ready_stalled', true)->count() > 0
                        ? 'Hay listos que ya superaron la permanencia esperada.'
                        : 'Tickets que ya pueden pasar a entrega.',
                    'tone' => $readyQueue->where('flags.ready_stalled', true)->count() >= 2
                        ? 'danger'
                        : ($readyCount >= 4 ? 'warning' : 'success'),
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_READY], 'mt-cola-operativa'),
                ],
                [
                    'label' => 'Ingresos de hoy',
                    'value' => $todayIngressCount,
                    'context' => $todayDeliveryCount > 0
                        ? sprintf('%d entrega(s) registradas hoy.', $todayDeliveryCount)
                        : 'Sin entregas registradas hoy.',
                    'hint' => $flowBalance > 0
                        ? sprintf('La carga del dia crecio en %d ticket(s).', $flowBalance)
                        : 'Ingreso diario dentro de la capacidad visible.',
                    'tone' => $flowTone,
                    'href' => $this->listUrl([
                        'bandeja' => RecepcionTecnicaService::TRAY_TODOS,
                        'fecha_desde' => $today->toDateString(),
                        'fecha_hasta' => $today->toDateString(),
                    ]),
                ],
                [
                    'label' => 'Entregas de hoy',
                    'value' => $todayDeliveryCount,
                    'context' => $todayIngressCount > 0
                        ? sprintf('%d ingreso(s) registrados hoy.', $todayIngressCount)
                        : 'Sin ingresos registrados hoy.',
                    'hint' => $todayDeliveryCount >= $todayIngressCount
                        ? 'La mesa descargo la demanda del dia.'
                        : 'Todavia hay carga pendiente por descargar.',
                    'tone' => $todayDeliveryCount === 0 && $readyCount > 0
                        ? 'warning'
                        : ($todayDeliveryCount >= $todayIngressCount ? 'success' : 'warning'),
                    'href' => $this->listUrl(['bandeja' => RecepcionTecnicaService::TRAY_FINALIZADOS]),
                ],
                [
                    'label' => 'Demorados',
                    'value' => $delayedCount,
                    'context' => $delayedCount === 0
                        ? 'Sin tickets demorados hoy.'
                        : sprintf('%d ticket(s) pasaron la permanencia esperada.', $delayedCount),
                    'hint' => 'Incluye esperas, listos sin salida y seguimientos que ya necesitan empuje.',
                    'tone' => $delayedCount === 0 ? 'success' : $this->metricTone($delayedCount, 2, 5),
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_DELAYED], 'mt-cola-operativa'),
                ],
                [
                    'label' => 'Criticos',
                    'value' => $criticalCount,
                    'context' => $criticalCount === 0
                        ? 'Sin casos criticos visibles.'
                        : sprintf('%d ticket(s) requieren decision inmediata.', $criticalCount),
                    'hint' => 'Prioridad por demora extrema, bloqueo operativo o datos tecnicos faltantes.',
                    'tone' => $criticalCount === 0 ? 'success' : $this->metricTone($criticalCount, 1, 3),
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_CRITICAL], 'mt-cola-operativa'),
                ],
            ],
            'queueFilters' => [
                [
                    'key' => self::QUEUE_ALL,
                    'label' => 'Toda la cola',
                    'count' => $queueCounts[self::QUEUE_ALL],
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_ALL], 'mt-cola-operativa'),
                ],
                [
                    'key' => self::QUEUE_CRITICAL,
                    'label' => 'Criticos',
                    'count' => $queueCounts[self::QUEUE_CRITICAL],
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_CRITICAL], 'mt-cola-operativa'),
                ],
                [
                    'key' => self::QUEUE_DELAYED,
                    'label' => 'Demorados',
                    'count' => $queueCounts[self::QUEUE_DELAYED],
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_DELAYED], 'mt-cola-operativa'),
                ],
                [
                    'key' => self::QUEUE_READY,
                    'label' => 'Listos',
                    'count' => $queueCounts[self::QUEUE_READY],
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_READY], 'mt-cola-operativa'),
                ],
                [
                    'key' => self::QUEUE_RECENT,
                    'label' => 'Recientes',
                    'count' => $queueCounts[self::QUEUE_RECENT],
                    'href' => $this->dashboardUrl(['queue' => self::QUEUE_RECENT], 'mt-cola-operativa'),
                ],
            ],
            'queueItems' => $queueItems,
            'queueSummary' => [
                'selectedLabel' => $this->queueLabel($selectedQueue),
                'selectedDescription' => $this->queueDescription($selectedQueue),
                'selectedCount' => $queueCounts[$selectedQueue] ?? 0,
                'fullListUrl' => $this->listUrl(['bandeja' => RecepcionTecnicaService::TRAY_TODOS]),
                'readyListUrl' => $this->listUrl(['bandeja' => RecepcionTecnicaService::TRAY_LISTOS]),
                'delayedCount' => $delayedCount,
                'criticalCount' => $criticalCount,
            ],
            'alerts' => $alerts,
            'analytics' => [
                'flow' => [
                    'ingresos' => $todayIngressCount,
                    'entregas' => $todayDeliveryCount,
                    'balance' => $flowBalance,
                    'balanceLabel' => $flowBalance > 0
                        ? sprintf('+%d de carga neta hoy', $flowBalance)
                        : ($flowBalance < 0 ? sprintf('%d menos en cola hoy', abs($flowBalance)) : 'Flujo equilibrado hoy'),
                    'tone' => $flowTone,
                ],
                'averageStay' => [
                    'openLabel' => $this->formatDuration($openAverageHours),
                    'closedLabel' => $closedAverageHours > 0 ? $this->formatDuration($closedAverageHours) : 'Sin cierres recientes',
                    'tone' => $openAverageHours >= 120 ? 'danger' : ($openAverageHours >= 72 ? 'warning' : 'success'),
                ],
                'statusDistribution' => $this->buildStatusDistribution($openTickets),
                'topSources' => $this->buildTopSources($recentTickets),
                'topReasons' => $this->buildTopReasons($recentTickets),
                'recentWindowLabel' => 'Ultimos 30 dias',
            ],
            'secondaryActions' => [
                [
                    'title' => 'Recibir equipo',
                    'description' => 'Registrar un nuevo ingreso tecnico sin salir del modulo.',
                    'meta' => 'Accion principal fuera de la cola',
                    'icon' => 'plus',
                    'tone' => 'indigo',
                    'href' => route('mesa-tecnica.recepciones-tecnicas.create'),
                ],
                [
                    'title' => 'Entregas pendientes',
                    'description' => 'Abrir la bandeja enfocada en tickets listos para entregar.',
                    'meta' => 'Salida operativa',
                    'icon' => 'check-circle-2',
                    'tone' => 'emerald',
                    'href' => $this->listUrl(['bandeja' => RecepcionTecnicaService::TRAY_LISTOS]),
                ],
                [
                    'title' => 'Buscar equipo',
                    'description' => 'Usar el buscador principal para CI, serie, acta o codigo interno.',
                    'meta' => 'Acceso directo',
                    'icon' => 'search',
                    'tone' => 'slate',
                    'href' => '#mt-buscador-principal',
                ],
                [
                    'title' => 'Cola completa',
                    'description' => 'Abrir el listado completo con filtros avanzados.',
                    'meta' => 'Control operativo',
                    'icon' => 'layers',
                    'tone' => 'slate',
                    'href' => $this->listUrl(['bandeja' => RecepcionTecnicaService::TRAY_TODOS]),
                ],
                [
                    'title' => 'Actas y movimientos',
                    'description' => 'Entrar solo cuando la decision implique trazabilidad patrimonial.',
                    'meta' => 'Gestion formal',
                    'icon' => 'clipboard-list',
                    'tone' => 'amber',
                    'href' => route('actas.index'),
                ],
            ],
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
    private function evaluateQueueTicket(
        RecepcionTecnica $recepcion,
        CarbonInterface $now,
        array $reincidentCounts
    ): array {
        $ingresadoAt = $recepcion->ingresado_at ?? $recepcion->status_changed_at ?? $now;
        $statusChangedAt = $recepcion->status_changed_at ?? $ingresadoAt;
        $hoursInDesk = $this->hoursBetween($ingresadoAt, $now);
        $hoursWithoutChange = $this->hoursBetween($statusChangedAt, $now);
        $resolvedEquipoId = $recepcion->resolvedEquipo()?->id;
        $reincidentCount = $resolvedEquipoId !== null ? (int) ($reincidentCounts[$resolvedEquipoId] ?? 1) : 1;

        $flags = [
            'missing_diagnostic' => $this->missingDiagnostic($recepcion),
            'unlinked' => $recepcion->canBeIncorporated(),
            'incomplete_source' => $this->incompleteSource($recepcion),
            'ready_stalled' => $recepcion->isReadyForDelivery() && $hoursWithoutChange >= 24,
            'reincident' => $reincidentCount > 1,
        ];

        $criticalReasons = [];
        $delayedReasons = [];

        if ($hoursInDesk >= 240) {
            $criticalReasons[] = 'Supera 10 dias en mesa.';
        } elseif ($hoursInDesk >= 120) {
            $delayedReasons[] = 'Acumula mas de 5 dias en mesa.';
        }

        if ($recepcion->estado === RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO) {
            if ($hoursWithoutChange >= 96) {
                $criticalReasons[] = 'Sigue en espera de repuesto hace 4 dias o mas.';
            } elseif ($hoursWithoutChange >= 48) {
                $delayedReasons[] = 'Sigue en espera de repuesto hace 2 dias o mas.';
            }
        }

        if ($recepcion->isReadyForDelivery()) {
            if ($hoursWithoutChange >= 48) {
                $criticalReasons[] = 'Listo para entregar hace 48 horas o mas.';
            } elseif ($hoursWithoutChange >= 24) {
                $delayedReasons[] = 'Listo para entregar hace 24 horas o mas.';
            }
        }

        if ($flags['unlinked']) {
            if ($hoursInDesk >= 24) {
                $criticalReasons[] = 'No tiene equipo vinculado.';
            } elseif ($hoursInDesk >= 8) {
                $delayedReasons[] = 'Todavia falta vincular el equipo.';
            }
        }

        if ($flags['missing_diagnostic']) {
            if (
                in_array($recepcion->estado, [
                    RecepcionTecnica::ESTADO_EN_DIAGNOSTICO,
                    RecepcionTecnica::ESTADO_EN_REPARACION,
                    RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO,
                ], true)
                && $hoursWithoutChange >= 24
            ) {
                $criticalReasons[] = 'Falta diagnostico documentado.';
            } elseif ($hoursInDesk >= 24) {
                $delayedReasons[] = 'Todavia no tiene diagnostico inicial.';
            }
        }

        if ($flags['incomplete_source'] && $hoursInDesk >= 48) {
            $delayedReasons[] = 'La procedencia sigue incompleta.';
        }

        if ($flags['reincident'] && $hoursInDesk >= 24) {
            $delayedReasons[] = $reincidentCount === 2
                ? 'Equipo con ingresos reiterados.'
                : sprintf('Equipo con %d ingresos historicos.', $reincidentCount);
        }

        $priority = ! empty($criticalReasons)
            ? self::QUEUE_CRITICAL
            : (! empty($delayedReasons)
                ? self::QUEUE_DELAYED
                : ($recepcion->isReadyForDelivery() ? self::QUEUE_READY : self::QUEUE_RECENT));

        return [
            'recepcion' => $recepcion,
            'priority' => $priority,
            'priority_label' => $this->priorityLabel($priority),
            'priority_hint' => $criticalReasons[0]
                ?? $delayedReasons[0]
                ?? ($recepcion->isReadyForDelivery() ? 'Puede salir por entrega.' : 'Sigue dentro del flujo operativo esperado.'),
            'priority_rank' => $this->priorityRank($priority),
            'critical_reasons' => $criticalReasons,
            'delayed_reasons' => $delayedReasons,
            'flags' => $flags,
            'hours_in_desk' => $hoursInDesk,
            'hours_without_change' => $hoursWithoutChange,
            'age_label' => $this->ageLabel($hoursInDesk),
            'age_tone' => $hoursInDesk >= 240 ? 'danger' : ($hoursInDesk >= 120 ? 'warning' : 'neutral'),
            'reincident_count' => $reincidentCount,
            'ingresado_timestamp' => $ingresadoAt->getTimestamp(),
            'status_timestamp' => $statusChangedAt->getTimestamp(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sortedQueue
     * @param  Collection<int, array<string, mixed>>  $criticalQueue
     * @param  Collection<int, array<string, mixed>>  $readyQueue
     * @return Collection<int, array<string, mixed>>
     */
    private function buildAlerts(
        Collection $sortedQueue,
        Collection $criticalQueue,
        Collection $readyQueue
    ): Collection {
        $alerts = collect([
            [
                'key' => 'critical',
                'title' => 'Casos criticos en mesa',
                'description' => $criticalQueue->count() === 1
                    ? 'Hay 1 ticket que ya requiere decision inmediata.'
                    : sprintf('Hay %d tickets que ya requieren decision inmediata.', $criticalQueue->count()),
                'count' => $criticalQueue->count(),
                'tone' => 'danger',
                'action_url' => $this->dashboardUrl(['queue' => self::QUEUE_CRITICAL], 'mt-cola-operativa'),
                'action_label' => 'Abrir cola critica',
                'samples' => $this->alertSamples($criticalQueue),
            ],
            [
                'key' => 'ready-stalled',
                'title' => 'Listos para entregar aun en mesa',
                'description' => $readyQueue->where('flags.ready_stalled', true)->count() === 1
                    ? 'Hay 1 equipo listo para entregar que sigue esperando salida.'
                    : sprintf(
                        'Hay %d equipos listos para entregar que siguen esperando salida.',
                        $readyQueue->where('flags.ready_stalled', true)->count()
                    ),
                'count' => $readyQueue->where('flags.ready_stalled', true)->count(),
                'tone' => 'warning',
                'action_url' => $this->dashboardUrl(['queue' => self::QUEUE_READY], 'mt-cola-operativa'),
                'action_label' => 'Revisar entregas',
                'samples' => $this->alertSamples($readyQueue->where('flags.ready_stalled', true)->values()),
            ],
            [
                'key' => 'missing-diagnostic',
                'title' => 'Ingresos sin diagnostico documentado',
                'description' => $sortedQueue->where('flags.missing_diagnostic', true)->count() === 1
                    ? 'Hay 1 ticket en seguimiento sin diagnostico visible.'
                    : sprintf(
                        'Hay %d tickets en seguimiento sin diagnostico visible.',
                        $sortedQueue->where('flags.missing_diagnostic', true)->count()
                    ),
                'count' => $sortedQueue->where('flags.missing_diagnostic', true)->count(),
                'tone' => 'warning',
                'action_url' => $this->dashboardUrl(['queue' => self::QUEUE_ALL], 'mt-cola-operativa'),
                'action_label' => 'Priorizar seguimiento',
                'samples' => $this->alertSamples($sortedQueue->where('flags.missing_diagnostic', true)->values()),
            ],
            [
                'key' => 'unlinked',
                'title' => 'Tickets pendientes de vincular',
                'description' => $sortedQueue->where('flags.unlinked', true)->count() === 1
                    ? 'Hay 1 ingreso tecnico que todavia no se asocio a un equipo.'
                    : sprintf(
                        'Hay %d ingresos tecnicos que todavia no se asociaron a un equipo.',
                        $sortedQueue->where('flags.unlinked', true)->count()
                    ),
                'count' => $sortedQueue->where('flags.unlinked', true)->count(),
                'tone' => 'warning',
                'action_url' => $this->dashboardUrl(['queue' => self::QUEUE_ALL], 'mt-cola-operativa'),
                'action_label' => 'Resolver vinculacion',
                'samples' => $this->alertSamples($sortedQueue->where('flags.unlinked', true)->values()),
            ],
            [
                'key' => 'reincident',
                'title' => 'Equipos reincidentes',
                'description' => $sortedQueue->where('flags.reincident', true)->count() === 1
                    ? 'Hay 1 equipo con ingresos repetidos que conviene seguir de cerca.'
                    : sprintf(
                        'Hay %d equipos con ingresos repetidos que conviene seguir de cerca.',
                        $sortedQueue->where('flags.reincident', true)->count()
                    ),
                'count' => $sortedQueue->where('flags.reincident', true)->count(),
                'tone' => 'warning',
                'action_url' => $this->dashboardUrl(['queue' => self::QUEUE_DELAYED], 'mt-cola-operativa'),
                'action_label' => 'Ver reincidencias',
                'samples' => $this->alertSamples($sortedQueue->where('flags.reincident', true)->values()),
            ],
        ]);

        return $alerts
            ->filter(fn (array $alert): bool => (int) $alert['count'] > 0)
            ->take(4)
            ->values();
    }

    /**
     * @param  Collection<int, RecepcionTecnica>  $openTickets
     * @return Collection<int, array<string, mixed>>
     */
    private function buildStatusDistribution(Collection $openTickets): Collection
    {
        $distribution = collect(RecepcionTecnica::ESTADOS_ABIERTOS)
            ->map(function (string $status) use ($openTickets): array {
                $count = $openTickets->where('estado', $status)->count();

                return [
                    'label' => RecepcionTecnica::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)),
                    'count' => $count,
                ];
            })
            ->filter(fn (array $item): bool => $item['count'] > 0)
            ->values();

        $max = (int) $distribution->max('count');

        return $distribution
            ->map(function (array $item) use ($max): array {
                $item['width'] = $max > 0 ? (int) round(($item['count'] / $max) * 100) : 0;

                return $item;
            })
            ->values();
    }

    /**
     * @param  Collection<int, RecepcionTecnica>  $recentTickets
     * @return Collection<int, array<string, mixed>>
     */
    private function buildTopSources(Collection $recentTickets): Collection
    {
        $grouped = $recentTickets
            ->groupBy(fn (RecepcionTecnica $recepcion): string => $recepcion->procedenciaResumen())
            ->map(fn (Collection $items): int => $items->count())
            ->sortDesc()
            ->take(5);

        $max = (int) $grouped->max();

        return $grouped
            ->map(fn (int $count, string $label): array => [
                'label' => $label,
                'count' => $count,
                'width' => $max > 0 ? (int) round(($count / $max) * 100) : 0,
            ])
            ->values();
    }

    /**
     * @param  Collection<int, RecepcionTecnica>  $recentTickets
     * @return Collection<int, array<string, mixed>>
     */
    private function buildTopReasons(Collection $recentTickets): Collection
    {
        $grouped = $recentTickets
            ->filter(fn (RecepcionTecnica $recepcion): bool => trim((string) $recepcion->falla_motivo) !== '')
            ->groupBy(fn (RecepcionTecnica $recepcion): string => trim((string) $recepcion->falla_motivo))
            ->map(fn (Collection $items): int => $items->count())
            ->sortDesc()
            ->take(5);

        $max = (int) $grouped->max();

        return $grouped
            ->map(fn (int $count, string $label): array => [
                'label' => $label,
                'count' => $count,
                'width' => $max > 0 ? (int) round(($count / $max) * 100) : 0,
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function alertSamples(Collection $items): Collection
    {
        return $items
            ->take(3)
            ->map(function (array $item): array {
                /** @var RecepcionTecnica $recepcion */
                $recepcion = $item['recepcion'];

                return [
                    'code' => $recepcion->codigo,
                    'reference' => $recepcion->equipmentReference(),
                    'hint' => $item['priority_hint'],
                    'url' => route('mesa-tecnica.recepciones-tecnicas.show', [
                        'recepcionTecnica' => $recepcion,
                        'return_to' => $this->dashboardUrl(),
                    ]),
                ];
            })
            ->values();
    }

    /**
     * @return array<int, int>
     */
    private function reincidentCounts(User $user): array
    {
        return RecepcionTecnica::query()
            ->visibleToUser($user)
            ->where(function (Builder $query): void {
                $query
                    ->whereNotNull('equipo_id')
                    ->orWhereNotNull('equipo_creado_id');
            })
            ->selectRaw('coalesce(equipo_id, equipo_creado_id) as resolved_equipo_id, count(*) as total')
            ->groupByRaw('coalesce(equipo_id, equipo_creado_id)')
            ->havingRaw('count(*) > 1')
            ->pluck('total', 'resolved_equipo_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    private function dashboardQuery(User $user): Builder
    {
        return RecepcionTecnica::query()
            ->with([
                'institution:id,nombre',
                'creator:id,name',
                'recibidoPor:id,name',
                'procedenciaInstitution:id,nombre',
                'procedenciaService:id,nombre',
                'procedenciaOffice:id,nombre',
                'equipo:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
                'equipoCreado:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
            ])
            ->visibleToUser($user);
    }

    private function missingDiagnostic(RecepcionTecnica $recepcion): bool
    {
        $diagnostico = trim((string) $recepcion->diagnostico);

        if ($diagnostico !== '') {
            return false;
        }

        return in_array($recepcion->estado, [
            RecepcionTecnica::ESTADO_RECIBIDO,
            RecepcionTecnica::ESTADO_EN_DIAGNOSTICO,
            RecepcionTecnica::ESTADO_EN_REPARACION,
            RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO,
        ], true);
    }

    private function incompleteSource(RecepcionTecnica $recepcion): bool
    {
        $hasStructuredSource = $recepcion->procedenciaInstitution !== null
            || $recepcion->procedenciaService !== null
            || $recepcion->procedenciaOffice !== null;
        $hasFreeSource = trim((string) $recepcion->procedencia_hospital) !== ''
            || trim((string) $recepcion->procedencia_libre) !== '';

        return ! $hasStructuredSource && ! $hasFreeSource;
    }

    private function normalizeQueue(mixed $value): string
    {
        $normalized = trim((string) $value);

        return in_array($normalized, self::QUEUE_FILTERS, true)
            ? $normalized
            : self::QUEUE_ALL;
    }

    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            self::QUEUE_CRITICAL => 'Critico',
            self::QUEUE_DELAYED => 'Demorado',
            self::QUEUE_READY => 'Listo',
            default => 'Reciente',
        };
    }

    private function priorityRank(string $priority): int
    {
        return match ($priority) {
            self::QUEUE_CRITICAL => 0,
            self::QUEUE_DELAYED => 1,
            self::QUEUE_READY => 2,
            default => 3,
        };
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function compareQueueItems(array $left, array $right): int
    {
        $priorityComparison = $left['priority_rank'] <=> $right['priority_rank'];

        if ($priorityComparison !== 0) {
            return $priorityComparison;
        }

        $priority = (string) $left['priority'];

        return match ($priority) {
            self::QUEUE_CRITICAL, self::QUEUE_DELAYED => $this->compareDescendingChain(
                [(int) $left['hours_in_desk'], (int) $left['hours_without_change'], (int) $left['ingresado_timestamp']],
                [(int) $right['hours_in_desk'], (int) $right['hours_without_change'], (int) $right['ingresado_timestamp']]
            ),
            self::QUEUE_READY => $this->compareDescendingChain(
                [(int) $left['hours_without_change'], (int) $left['hours_in_desk'], (int) $left['ingresado_timestamp']],
                [(int) $right['hours_without_change'], (int) $right['hours_in_desk'], (int) $right['ingresado_timestamp']]
            ),
            default => $this->compareDescendingChain(
                [(int) $left['ingresado_timestamp'], (int) $left['status_timestamp']],
                [(int) $right['ingresado_timestamp'], (int) $right['status_timestamp']]
            ),
        };
    }

    /**
     * @param  array<int, int>  $left
     * @param  array<int, int>  $right
     */
    private function compareDescendingChain(array $left, array $right): int
    {
        foreach ($left as $index => $value) {
            $comparison = ($right[$index] ?? 0) <=> $value;

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function metricTone(int $value, int $warningThreshold, int $dangerThreshold): string
    {
        if ($value >= $dangerThreshold) {
            return 'danger';
        }

        if ($value >= $warningThreshold) {
            return 'warning';
        }

        return 'success';
    }

    private function queueLabel(string $queue): string
    {
        return match ($queue) {
            self::QUEUE_CRITICAL => 'Criticos primero',
            self::QUEUE_DELAYED => 'Demorados',
            self::QUEUE_READY => 'Listos para salir',
            self::QUEUE_RECENT => 'Recientes',
            default => 'Cola priorizada',
        };
    }

    private function queueDescription(string $queue): string
    {
        return match ($queue) {
            self::QUEUE_CRITICAL => 'Se priorizan bloqueos, demoras extremas y tickets con datos tecnicos faltantes.',
            self::QUEUE_DELAYED => 'Se muestran los casos que ya pasaron la permanencia operativa esperada.',
            self::QUEUE_READY => 'Se enfocan los tickets que pueden salir por entrega.',
            self::QUEUE_RECENT => 'Se muestran los ingresos recientes que todavia estan dentro de la ventana normal.',
            default => 'Orden operativo: criticos, demorados, listos para entregar y luego flujo reciente.',
        };
    }

    private function dashboardUrl(array $params = [], ?string $fragment = null): string
    {
        $url = route('mesa-tecnica.index', $params);

        return $fragment ? $url.'#'.$fragment : $url;
    }

    private function listUrl(array $params = []): string
    {
        return route('mesa-tecnica.recepciones-tecnicas.index', $params);
    }

    private function hoursBetween(?CarbonInterface $from, ?CarbonInterface $to): int
    {
        if (! $from instanceof CarbonInterface || ! $to instanceof CarbonInterface) {
            return 0;
        }

        return max(0, $from->diffInHours($to));
    }

    private function ageLabel(int $hours): string
    {
        if ($hours < 24) {
            return $hours <= 1 ? 'Menos de 1 h en mesa' : sprintf('%d h en mesa', $hours);
        }

        $days = (int) floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($remainingHours === 0) {
            return $days === 1 ? '1 dia en mesa' : sprintf('%d dias en mesa', $days);
        }

        return sprintf('%d d %d h en mesa', $days, $remainingHours);
    }

    private function formatDuration(int $hours): string
    {
        if ($hours <= 0) {
            return 'Menos de 1 hora';
        }

        if ($hours < 24) {
            return $hours === 1 ? '1 hora' : sprintf('%d horas', $hours);
        }

        $days = (int) floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($remainingHours === 0) {
            return $days === 1 ? '1 dia' : sprintf('%d dias', $days);
        }

        return sprintf('%d d %d h', $days, $remainingHours);
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
