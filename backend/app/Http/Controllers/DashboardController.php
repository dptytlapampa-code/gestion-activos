<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Institution;
use App\Models\Mantenimiento;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const RECENT_EQUIPOS_LIMIT = 5;

    private const RECENT_ACTAS_LIMIT = 5;

    private const RECENT_ACTIVITY_LIMIT = 8;

    private const OPEN_MAINTENANCE_LIMIT = 6;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $institutionIds = $this->globalAdministrationScopeIds($user);
        $operatesGlobally = $this->operatesWithGlobalScope($user);

        $institucionesVisibles = Institution::query()
            ->when(
                $institutionIds !== null,
                fn (Builder $query) => $institutionIds === []
                    ? $query->whereRaw('1 = 0')
                    : $query->whereIn('id', $institutionIds)
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $equipoScope = Equipo::query()
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('services.institution_id', $institutionIds));

        $totalEquipos = (clone $equipoScope)->count('equipos.id');
        $instituciones = $institucionesVisibles->count();

        $servicios = Service::query()
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('institution_id', $institutionIds))
            ->count();

        $oficinas = Office::query()
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('services.institution_id', $institutionIds))
            ->count('offices.id');

        $equiposPorEstado = $this->buildEstadoTotals($institutionIds);
        $equiposOperativos = $equiposPorEstado[EquipoStatus::CODE_OPERATIVA] ?? 0;
        $equiposPrestados = $equiposPorEstado[EquipoStatus::CODE_PRESTADO] ?? 0;
        $equiposEnServicioTecnico = $equiposPorEstado[EquipoStatus::CODE_EN_SERVICIO_TECNICO] ?? 0;
        $equiposFueraDeServicio = $equiposPorEstado[EquipoStatus::CODE_FUERA_DE_SERVICIO] ?? 0;
        $equiposBaja = $equiposPorEstado[EquipoStatus::CODE_BAJA] ?? 0;
        $equiposConSeguimiento = $equiposEnServicioTecnico + $equiposFueraDeServicio;
        $operatividadPorcentaje = $this->percentage($equiposOperativos, $totalEquipos);

        $tiposAgrupados = (clone $equipoScope)
            ->leftJoin('tipos_equipos', 'tipos_equipos.id', '=', 'equipos.tipo_equipo_id')
            ->selectRaw("coalesce(tipos_equipos.nombre, nullif(equipos.tipo, ''), 'Sin clasificar') as label, count(*) as total")
            ->groupByRaw("coalesce(tipos_equipos.nombre, nullif(equipos.tipo, ''), 'Sin clasificar')")
            ->orderByDesc('total')
            ->get();

        $tiposRegistrados = $tiposAgrupados->count();
        $tipoPrincipal = $tiposAgrupados->first();

        $prestamosActivosScope = Movimiento::query()
            ->where('tipo_movimiento', Movimiento::TIPO_PRESTAMO)
            ->whereNull('fecha_devolucion_real')
            ->when(
                $institutionIds !== null,
                fn (Builder $query) => $query->whereHas(
                    'equipo.oficina.service',
                    fn (Builder $equipoQuery) => $equipoQuery->whereIn('institution_id', $institutionIds)
                )
            );

        $prestamosActivos = (clone $prestamosActivosScope)->count();
        $prestamosVencidos = (clone $prestamosActivosScope)
            ->whereNotNull('fecha_estimada_devolucion')
            ->whereDate('fecha_estimada_devolucion', '<', now()->toDateString())
            ->count();

        $mantenimientoAbiertoScope = Mantenimiento::query()
            ->where('tipo', Mantenimiento::TIPO_EXTERNO)
            ->whereNull('fecha_egreso_st')
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('institution_id', $institutionIds));

        $mantenimientosAbiertos = (clone $mantenimientoAbiertoScope)->count();
        $mantenimientoMasAntiguo = (clone $mantenimientoAbiertoScope)
            ->whereNotNull('fecha_ingreso_st')
            ->oldest('fecha_ingreso_st')
            ->first();
        $diasCasoMasAntiguo = $mantenimientoMasAntiguo?->fecha_ingreso_st?->diffInDays(now());

        $ultimosServicioTecnico = (clone $mantenimientoAbiertoScope)
            ->with(['equipo:id,tipo,numero_serie,codigo_interno'])
            ->latest('fecha')
            ->limit(self::OPEN_MAINTENANCE_LIMIT)
            ->get();

        $movimientoScope = Movimiento::query()
            ->when(
                $institutionIds !== null,
                fn (Builder $query) => $query->whereHas(
                    'equipo.oficina.service',
                    fn (Builder $equipoQuery) => $equipoQuery->whereIn('institution_id', $institutionIds)
                )
            );

        $movimientosUltimos7Dias = (clone $movimientoScope)
            ->where('fecha', '>=', now()->copy()->subDays(7)->startOfDay())
            ->count();

        $movimientos = (clone $movimientoScope)
            ->with(['equipo:id,tipo,numero_serie,codigo_interno', 'user:id,name'])
            ->latest('fecha')
            ->limit(self::RECENT_ACTIVITY_LIMIT)
            ->get();

        $ultimoMovimiento = $movimientos->first();

        $actaScope = Acta::query()
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('institution_id', $institutionIds));

        $actasUltimos30Dias = (clone $actaScope)
            ->whereDate('fecha', '>=', now()->copy()->subDays(30)->toDateString())
            ->count();

        $actasAnuladasUltimos30Dias = (clone $actaScope)
            ->where('status', Acta::STATUS_ANULADA)
            ->whereDate('fecha', '>=', now()->copy()->subDays(30)->toDateString())
            ->count();

        $equiposRecientes = Equipo::query()
            ->with([
                'oficina:id,nombre,service_id',
                'oficina.service:id,nombre,institution_id',
                'oficina.service.institution:id,nombre',
                'equipoStatus:id,code,name',
                'tipoEquipo:id,nombre,image_path',
            ])
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('services.institution_id', $institutionIds))
            ->select('equipos.*')
            ->latest('equipos.created_at')
            ->limit(self::RECENT_EQUIPOS_LIMIT)
            ->get();

        $actas = (clone $actaScope)
            ->withCount('equipos')
            ->with(['creator:id,name', 'institution:id,nombre'])
            ->latest('fecha')
            ->limit(self::RECENT_ACTAS_LIMIT)
            ->get();

        $alertasActivas = collect($this->buildAlertItems(
            $equiposFueraDeServicio,
            $mantenimientosAbiertos,
            $prestamosVencidos,
            $actasAnuladasUltimos30Dias,
            $diasCasoMasAntiguo,
        ));

        $cantidadAlertasActivas = $alertasActivas->count();
        $alertItems = $alertasActivas->whenEmpty(fn (Collection $items) => $items->push([
            'title' => 'Operacion sin alertas criticas',
            'description' => 'No se detectan prestamos vencidos, equipos fuera de servicio ni actas anuladas recientes dentro del alcance visible.',
            'badge' => 'Sin pendientes urgentes',
            'icon' => 'check-circle-2',
            'container_classes' => 'border-emerald-200 bg-emerald-50',
            'icon_classes' => 'bg-emerald-100 text-emerald-700',
        ]))->values();

        $scopeLabel = $this->scopeLabel($institucionesVisibles, $operatesGlobally);

        $dashboardContext = [
            'cutoff' => now()->translatedFormat('l, j \\d\\e F Y'),
            'scopeLabel' => $scopeLabel,
            'summary' => $this->buildSummary(
                $scopeLabel,
                $totalEquipos,
                $operatividadPorcentaje,
                $equiposConSeguimiento,
                $prestamosVencidos,
            ),
            'coverageLabel' => $instituciones > 1
                ? "{$instituciones} instituciones, {$servicios} servicios y {$oficinas} oficinas"
                : "{$servicios} servicios y {$oficinas} oficinas con seguimiento",
            'attentionLabel' => $cantidadAlertasActivas > 0
                ? "{$cantidadAlertasActivas} frentes de atencion prioritaria"
                : 'Operacion estable al cierre del dia',
            'totalEquipos' => $totalEquipos,
            'showInstitutionContext' => $operatesGlobally || $instituciones > 1,
            'snapshots' => [
                [
                    'label' => 'Disponibilidad operativa',
                    'value' => "{$operatividadPorcentaje}%",
                    'detail' => $totalEquipos > 0
                        ? number_format($equiposOperativos, 0, ',', '.')." de ".number_format($totalEquipos, 0, ',', '.')." equipos en condicion operativa"
                        : 'Sin inventario visible',
                ],
                [
                    'label' => 'Seguimiento abierto',
                    'value' => number_format($equiposConSeguimiento + $prestamosVencidos, 0, ',', '.'),
                    'detail' => $equiposConSeguimiento > 0 || $prestamosVencidos > 0
                        ? number_format($equiposConSeguimiento, 0, ',', '.')." equipos con disponibilidad afectada"
                        : 'Sin incidencias activas',
                ],
                [
                    'label' => 'Actividad documental',
                    'value' => number_format($actasUltimos30Dias, 0, ',', '.'),
                    'detail' => number_format($movimientosUltimos7Dias, 0, ',', '.')." movimientos en los ultimos 7 dias",
                ],
            ],
        ];

        $kpiCards = [
            [
                'title' => 'Equipos registrados',
                'value' => number_format($totalEquipos, 0, ',', '.'),
                'subtitle' => 'Inventario visible en el alcance actual del tablero.',
                'footer' => $tiposRegistrados > 0
                    ? number_format($tiposRegistrados, 0, ',', '.')." tipos distintos con registro activo"
                    : 'Sin tipos de equipo informados',
                'icon' => 'monitor',
                'icon_classes' => 'bg-slate-100 text-slate-700',
            ],
            [
                'title' => 'Disponibilidad operativa',
                'value' => "{$operatividadPorcentaje}%",
                'subtitle' => $totalEquipos > 0
                    ? number_format($equiposOperativos, 0, ',', '.')." equipos listos para uso clinico"
                    : 'No hay equipos cargados para medir operatividad',
                'footer' => number_format($equiposPrestados, 0, ',', '.')." equipos prestados y ".number_format($equiposBaja, 0, ',', '.')." en baja",
                'icon' => 'shield-check',
                'icon_classes' => 'bg-emerald-50 text-emerald-700',
            ],
            [
                'title' => 'Prestamos activos',
                'value' => number_format($prestamosActivos, 0, ',', '.'),
                'subtitle' => $prestamosActivos > 0
                    ? 'Equipos con salida registrada y sin devolucion cerrada.'
                    : 'No hay prestamos abiertos al momento del corte.',
                'footer' => $prestamosVencidos > 0
                    ? number_format($prestamosVencidos, 0, ',', '.')." con fecha estimada vencida"
                    : 'Sin prestamos vencidos',
                'icon' => 'clipboard-list',
                'icon_classes' => 'bg-blue-50 text-blue-700',
            ],
            [
                'title' => 'Seguimiento tecnico',
                'value' => number_format($equiposConSeguimiento, 0, ',', '.'),
                'subtitle' => 'Equipos en servicio tecnico o fuera de servicio.',
                'footer' => $mantenimientosAbiertos > 0
                    ? number_format($mantenimientosAbiertos, 0, ',', '.')." casos externos todavia abiertos"
                    : 'Sin mantenimientos externos pendientes',
                'icon' => 'wrench',
                'icon_classes' => 'bg-amber-50 text-amber-700',
            ],
        ];

        $operationalHighlights = [
            [
                'title' => 'Cobertura asistencial',
                'value' => number_format($servicios, 0, ',', '.')." servicios",
                'detail' => number_format($oficinas, 0, ',', '.')." oficinas incluidas en el seguimiento institucional",
                'icon' => 'building-2',
                'icon_classes' => 'bg-slate-100 text-slate-700',
            ],
            [
                'title' => 'Documentacion reciente',
                'value' => number_format($actasUltimos30Dias, 0, ',', '.')." actas",
                'detail' => $actasAnuladasUltimos30Dias > 0
                    ? number_format($actasAnuladasUltimos30Dias, 0, ',', '.')." anuladas en el mismo periodo"
                    : 'Sin anulaciones registradas en los ultimos 30 dias',
                'icon' => 'file-text',
                'icon_classes' => 'bg-slate-100 text-slate-700',
            ],
            [
                'title' => 'Actividad de trazabilidad',
                'value' => number_format($movimientosUltimos7Dias, 0, ',', '.')." movimientos",
                'detail' => $ultimoMovimiento !== null
                    ? $this->movementLabel((string) $ultimoMovimiento->tipo_movimiento).' '.($ultimoMovimiento->fecha?->diffForHumans() ?? '')
                    : 'Sin actividad reciente de movimientos',
                'icon' => 'sliders-horizontal',
                'icon_classes' => 'bg-slate-100 text-slate-700',
            ],
            [
                'title' => 'Composicion del inventario',
                'value' => number_format($tiposRegistrados, 0, ',', '.')." tipos",
                'detail' => $tipoPrincipal !== null
                    ? "{$tipoPrincipal->label} concentra ".number_format((int) $tipoPrincipal->total, 0, ',', '.')." equipos"
                    : 'Sin tipos clasificados en el inventario',
                'icon' => 'layers',
                'icon_classes' => 'bg-slate-100 text-slate-700',
            ],
        ];

        return view('dashboard', [
            'actas' => $actas,
            'activityItems' => $this->buildActivityItems($movimientos),
            'alertItems' => $alertItems->all(),
            'dashboardContext' => $dashboardContext,
            'equiposRecientes' => $equiposRecientes,
            'kpiCards' => $kpiCards,
            'operationalHighlights' => $operationalHighlights,
            'statusChart' => $this->buildStatusChart($equiposPorEstado, $totalEquipos),
            'typeChart' => $this->buildTypeChart($tiposAgrupados, $totalEquipos),
            'ultimosServicioTecnico' => $ultimosServicioTecnico,
        ]);
    }

    /**
     * @param array<int, int>|null $institutionIds
     * @return array<string, int>
     */
    private function buildEstadoTotals(?array $institutionIds): array
    {
        $rawCounts = Equipo::query()
            ->join('equipo_statuses', 'equipo_statuses.id', '=', 'equipos.equipo_status_id')
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when($institutionIds !== null, fn (Builder $query) => $query->whereIn('services.institution_id', $institutionIds))
            ->selectRaw('equipo_statuses.code, count(*) as total')
            ->groupBy('equipo_statuses.code')
            ->pluck('total', 'equipo_statuses.code');

        $totals = [
            EquipoStatus::CODE_OPERATIVA => 0,
            EquipoStatus::CODE_PRESTADO => 0,
            EquipoStatus::CODE_EN_SERVICIO_TECNICO => 0,
            EquipoStatus::CODE_FUERA_DE_SERVICIO => 0,
            EquipoStatus::CODE_BAJA => 0,
        ];

        foreach ($rawCounts as $code => $total) {
            $canonicalCode = $this->canonicalStatusCode((string) $code);

            if (array_key_exists($canonicalCode, $totals)) {
                $totals[$canonicalCode] += (int) $total;
            }
        }

        return $totals;
    }

    private function canonicalStatusCode(string $code): string
    {
        return match (EquipoStatus::normalizeCode($code)) {
            EquipoStatus::CODE_PRESTADA => EquipoStatus::CODE_PRESTADO,
            default => EquipoStatus::normalizeCode($code),
        };
    }

    /**
     * @param array<string, int> $totals
     * @return array<int, array<string, int|string>>
     */
    private function buildStatusChart(array $totals, int $totalEquipos): array
    {
        $items = [
            [
                'code' => EquipoStatus::CODE_OPERATIVA,
                'label' => 'Operativos',
                'description' => 'Disponibles para uso asistencial.',
                'color' => '#10b981',
            ],
            [
                'code' => EquipoStatus::CODE_PRESTADO,
                'label' => 'Prestados',
                'description' => 'Fuera de ubicacion base con trazabilidad activa.',
                'color' => '#3b82f6',
            ],
            [
                'code' => EquipoStatus::CODE_EN_SERVICIO_TECNICO,
                'label' => 'Servicio tecnico',
                'description' => 'Con mantenimiento o reparacion en curso.',
                'color' => '#f59e0b',
            ],
            [
                'code' => EquipoStatus::CODE_FUERA_DE_SERVICIO,
                'label' => 'Fuera de servicio',
                'description' => 'No disponibles para uso clinico.',
                'color' => '#f97316',
            ],
            [
                'code' => EquipoStatus::CODE_BAJA,
                'label' => 'Baja',
                'description' => 'Equipos retirados del inventario operativo.',
                'color' => '#ef4444',
            ],
        ];

        return collect($items)
            ->map(function (array $item) use ($totals, $totalEquipos): array {
                $total = (int) ($totals[$item['code']] ?? 0);

                return [
                    'code' => $item['code'],
                    'label' => $item['label'],
                    'description' => $item['description'],
                    'color' => $item['color'],
                    'total' => $total,
                    'percentage' => $this->percentage($total, $totalEquipos),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function buildTypeChart(Collection $tiposAgrupados, int $totalEquipos): array
    {
        $visibleItems = $tiposAgrupados
            ->map(fn ($item): array => [
                'label' => (string) $item->label,
                'total' => (int) $item->total,
            ])
            ->values();

        $topItems = $visibleItems->take(5)->values();
        $remainingTotal = $visibleItems->slice(5)->sum('total');

        if ($remainingTotal > 0) {
            $topItems->push([
                'label' => 'Otros tipos',
                'total' => $remainingTotal,
            ]);
        }

        $maxTotal = max(1, (int) ($topItems->max('total') ?? 1));

        return $topItems
            ->map(fn (array $item): array => [
                'label' => $item['label'],
                'total' => $item['total'],
                'percentage' => $this->percentage($item['total'], $totalEquipos),
                'width' => (int) round(($item['total'] / $maxTotal) * 100),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildAlertItems(
        int $equiposFueraDeServicio,
        int $mantenimientosAbiertos,
        int $prestamosVencidos,
        int $actasAnuladasUltimos30Dias,
        ?int $diasCasoMasAntiguo,
    ): array {
        $items = [];

        if ($prestamosVencidos > 0) {
            $items[] = [
                'title' => 'Prestamos vencidos',
                'description' => number_format($prestamosVencidos, 0, ',', '.').' equipos superaron la fecha estimada de devolucion y requieren regularizacion.',
                'badge' => number_format($prestamosVencidos, 0, ',', '.').' pendientes',
                'icon' => 'alert-circle',
                'container_classes' => 'border-red-200 bg-red-50',
                'icon_classes' => 'bg-red-100 text-red-700',
            ];
        }

        if ($equiposFueraDeServicio > 0) {
            $items[] = [
                'title' => 'Equipos fuera de servicio',
                'description' => number_format($equiposFueraDeServicio, 0, ',', '.').' equipos no estan disponibles para uso clinico y requieren evaluacion o resolucion.',
                'badge' => number_format($equiposFueraDeServicio, 0, ',', '.').' afectados',
                'icon' => 'info',
                'container_classes' => 'border-amber-200 bg-amber-50',
                'icon_classes' => 'bg-amber-100 text-amber-700',
            ];
        }

        if ($mantenimientosAbiertos > 0) {
            $detalleMantenimiento = number_format($mantenimientosAbiertos, 0, ',', '.').' equipos continuan en servicio tecnico externo.';

            if ($diasCasoMasAntiguo !== null) {
                $detalleMantenimiento .= ' El caso mas antiguo lleva '.number_format($diasCasoMasAntiguo, 0, ',', '.').' dias abiertos.';
            }

            $items[] = [
                'title' => 'Mantenimientos abiertos',
                'description' => $detalleMantenimiento,
                'badge' => number_format($mantenimientosAbiertos, 0, ',', '.').' en seguimiento',
                'icon' => 'wrench',
                'container_classes' => 'border-amber-200 bg-amber-50',
                'icon_classes' => 'bg-amber-100 text-amber-700',
            ];
        }

        if ($actasAnuladasUltimos30Dias > 0) {
            $items[] = [
                'title' => 'Actas anuladas recientes',
                'description' => number_format($actasAnuladasUltimos30Dias, 0, ',', '.').' documentos fueron anulados en los ultimos 30 dias y conviene revisar su impacto operativo.',
                'badge' => number_format($actasAnuladasUltimos30Dias, 0, ',', '.').' anuladas',
                'icon' => 'file-text',
                'container_classes' => 'border-slate-200 bg-slate-50',
                'icon_classes' => 'bg-slate-100 text-slate-700',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildActivityItems(Collection $movimientos): array
    {
        return $movimientos
            ->map(function (Movimiento $movimiento): array {
                $tone = $this->movementTone((string) $movimiento->tipo_movimiento);

                return [
                    'title' => $this->movementLabel((string) $movimiento->tipo_movimiento),
                    'meta' => $this->equipmentReference(
                        $movimiento->equipo?->tipo,
                        $movimiento->equipo?->codigo_interno,
                        $movimiento->equipo?->numero_serie
                    ),
                    'user' => $movimiento->user?->name ?? 'Usuario del sistema',
                    'datetime' => $movimiento->fecha?->format('d/m/Y H:i') ?? '-',
                    'relative' => $movimiento->fecha?->diffForHumans() ?? '',
                    'dot_color' => $tone['dot_color'],
                    'badge_classes' => $tone['badge_classes'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{dot_color:string,badge_classes:string}
     */
    private function movementTone(string $movementType): array
    {
        return match ($movementType) {
            Movimiento::TIPO_DEVOLUCION => [
                'dot_color' => '#10b981',
                'badge_classes' => 'bg-emerald-50 text-emerald-700',
            ],
            Movimiento::TIPO_PRESTAMO => [
                'dot_color' => '#3b82f6',
                'badge_classes' => 'bg-blue-50 text-blue-700',
            ],
            Movimiento::TIPO_MANTENIMIENTO => [
                'dot_color' => '#f59e0b',
                'badge_classes' => 'bg-amber-50 text-amber-700',
            ],
            Movimiento::TIPO_BAJA => [
                'dot_color' => '#ef4444',
                'badge_classes' => 'bg-red-50 text-red-700',
            ],
            Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA => [
                'dot_color' => '#4f46e5',
                'badge_classes' => 'bg-indigo-50 text-indigo-700',
            ],
            default => [
                'dot_color' => '#64748b',
                'badge_classes' => 'bg-slate-100 text-slate-700',
            ],
        };
    }

    private function movementLabel(string $movementType): string
    {
        return match ($movementType) {
            'ingreso' => 'Ingreso',
            Movimiento::TIPO_PRESTAMO => 'Prestamo',
            Movimiento::TIPO_DEVOLUCION => 'Devolucion',
            Movimiento::TIPO_MANTENIMIENTO => 'Mantenimiento',
            Movimiento::TIPO_BAJA => 'Baja',
            Movimiento::TIPO_TRASLADO => 'Traslado',
            Movimiento::TIPO_TRANSFERENCIA_INTERNA => 'Transferencia interna',
            Movimiento::TIPO_TRANSFERENCIA_EXTERNA => 'Transferencia externa',
            default => ucfirst(str_replace('_', ' ', $movementType)),
        };
    }

    private function equipmentReference(?string $tipo, ?string $codigoInterno, ?string $serial): string
    {
        $parts = collect([
            $tipo,
            $codigoInterno !== null && $codigoInterno !== '' ? "CI {$codigoInterno}" : null,
            $serial !== null && $serial !== '' ? "NS {$serial}" : null,
        ])
            ->filter()
            ->values();

        return $parts->isNotEmpty()
            ? $parts->implode(' / ')
            : 'Equipo sin referencia visible';
    }

    private function scopeLabel(Collection $institucionesVisibles, bool $operatesGlobally): string
    {
        if ($operatesGlobally) {
            return 'Alcance global del sistema';
        }

        if ($institucionesVisibles->isEmpty()) {
            return 'Sin instituciones visibles';
        }

        if ($institucionesVisibles->count() === 1) {
            return (string) $institucionesVisibles->first()->nombre;
        }

        return number_format($institucionesVisibles->count(), 0, ',', '.').' instituciones visibles';
    }

    private function buildSummary(
        string $scopeLabel,
        int $totalEquipos,
        int $operatividadPorcentaje,
        int $equiposConSeguimiento,
        int $prestamosVencidos,
    ): string {
        if ($totalEquipos === 0) {
            return "El tablero no tiene equipos cargados para {$scopeLabel}. Cuando exista inventario, aqui se mostraran disponibilidad, alertas y trazabilidad reciente.";
        }

        $summary = [
            "El tablero consolida la operacion de {$scopeLabel}.",
            "{$operatividadPorcentaje}% del inventario visible se encuentra operativo.",
        ];

        if ($equiposConSeguimiento > 0) {
            $summary[] = number_format($equiposConSeguimiento, 0, ',', '.').' equipos presentan indisponibilidad o seguimiento tecnico.';
        } else {
            $summary[] = 'No se observan equipos fuera de servicio ni en servicio tecnico externo al corte.';
        }

        if ($prestamosVencidos > 0) {
            $summary[] = number_format($prestamosVencidos, 0, ',', '.').' prestamos superaron la fecha estimada de devolucion.';
        }

        return implode(' ', $summary);
    }

    private function percentage(int $value, int $total): int
    {
        if ($total <= 0 || $value <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }
}
