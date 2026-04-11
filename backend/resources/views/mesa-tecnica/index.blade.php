@extends('layouts.app')

@section('title', 'Mesa tecnica')
@section('header', 'Mesa tecnica')

@section('content')
    @include('mesa-tecnica.partials.module-styles')

    @php
        $searchTargets = ['Codigo interno', 'CI', 'Numero de serie', 'Acta'];
        $returnTo = request()->fullUrl();
    @endphp

    <div class="space-y-5 lg:space-y-6">
        <section id="mt-buscador-principal" class="mt-dashboard-shell">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="mt-dashboard-kicker">Mesa Tecnica</span>
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">Mesa Tecnica</h2>
                        <p class="mt-1 text-sm text-slate-600">Vista operativa para decidir primero que atender, que destrabar y que entregar.</p>
                    </div>
                </div>

                <div class="mt-dashboard-meta">
                    <span class="app-badge bg-slate-100 px-3 text-slate-700">Dashboard operativo</span>
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_TODOS]) }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="layers" class="h-4 w-4" />
                        Ver listado completo
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="mt-dashboard-search">
                <input type="hidden" name="bandeja" value="{{ \App\Services\RecepcionTecnicaService::TRAY_TODOS }}">

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <x-icon name="search" class="h-5 w-5" />
                    </span>
                    <input
                        type="search"
                        name="search"
                        class="app-input min-h-[3.75rem] w-full pl-12 text-base"
                        placeholder="Buscar por codigo interno, CI, numero de serie o acta"
                        autocomplete="off"
                    >
                </div>

                <button type="submit" class="btn btn-indigo mt-primary-action min-w-[10rem]">
                    <x-icon name="search" class="h-4 w-4" />
                    Buscar
                </button>
            </form>

            <div class="mt-dashboard-tags">
                @foreach ($searchTargets as $target)
                    <span class="mt-dashboard-tag">{{ $target }}</span>
                @endforeach
            </div>
        </section>

        <section class="mt-kpi-grid">
            @foreach ($kpis as $kpi)
                <a
                    href="{{ $kpi['href'] }}"
                    @class([
                        'mt-kpi-link',
                        'mt-kpi-link-success' => $kpi['tone'] === 'success',
                        'mt-kpi-link-warning' => $kpi['tone'] === 'warning',
                        'mt-kpi-link-danger' => $kpi['tone'] === 'danger',
                    ])
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $kpi['label'] }}</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $kpi['value'] }}</p>
                        </div>

                        <span @class([
                            'mt-kpi-dot',
                            'mt-kpi-dot-success' => $kpi['tone'] === 'success',
                            'mt-kpi-dot-warning' => $kpi['tone'] === 'warning',
                            'mt-kpi-dot-danger' => $kpi['tone'] === 'danger',
                        ])></span>
                    </div>

                    <p class="mt-3 text-sm font-medium text-slate-800">{{ $kpi['context'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $kpi['hint'] }}</p>
                </a>
            @endforeach
        </section>

        <section id="mt-alertas-operativas" class="mt-alert-board">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mt-section-kicker">Alertas operativas</p>
                    <h3 class="mt-section-title">Lo que requiere accion rapida</h3>
                    <p class="mt-section-copy">Alertas derivadas de demora, bloqueo, falta de datos tecnicos y reincidencias visibles.</p>
                </div>

                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_TODOS]) }}" class="btn btn-slate w-full sm:w-auto">
                    <x-icon name="eye" class="h-4 w-4" />
                    Abrir todas las recepciones
                </a>
            </div>

            @if ($alerts->isNotEmpty())
                <div class="mt-alert-grid">
                    @foreach ($alerts as $alert)
                        <article
                            @class([
                                'mt-alert-card',
                                'mt-alert-card-warning' => $alert['tone'] === 'warning',
                                'mt-alert-card-danger' => $alert['tone'] === 'danger',
                            ])
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Alerta</p>
                                    <h4 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">{{ $alert['title'] }}</h4>
                                </div>

                                <span class="mt-alert-count">{{ $alert['count'] }}</span>
                            </div>

                            <p class="mt-3 text-sm text-slate-700">{{ $alert['description'] }}</p>

                            @if (collect($alert['samples'])->isNotEmpty())
                                <div class="mt-alert-samples">
                                    @foreach ($alert['samples'] as $sample)
                                        <a href="{{ $sample['url'] }}" class="mt-alert-sample-link">
                                            <span class="font-semibold text-slate-900">{{ $sample['code'] }}</span>
                                            <span class="block text-sm text-slate-700">{{ $sample['reference'] }}</span>
                                            <span class="mt-1 block text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ $sample['hint'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <a href="{{ $alert['action_url'] }}" class="mt-alert-action">
                                {{ $alert['action_label'] }}
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-empty-state">
                    No hay alertas activas dentro del alcance visible. La cola puede seguirse desde los KPIs y la bandeja priorizada.
                </div>
            @endif
        </section>

        <section id="mt-cola-operativa" class="mt-queue-panel">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mt-section-kicker">Cola de trabajo</p>
                    <h3 class="mt-section-title">{{ $queueSummary['selectedLabel'] }}</h3>
                    <p class="mt-section-copy">{{ $queueSummary['selectedDescription'] }}</p>
                    <div class="mt-dashboard-tags mt-3">
                        <span class="mt-dashboard-tag">{{ $queueSummary['selectedCount'] }} visible(s)</span>
                        <span class="mt-dashboard-tag">{{ $queueSummary['criticalCount'] }} critico(s)</span>
                        <span class="mt-dashboard-tag">{{ $queueSummary['delayedCount'] }} demorado(s)</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ $queueSummary['readyListUrl'] }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="check-circle-2" class="h-4 w-4" />
                        Listos para entregar
                    </a>

                    <a href="{{ $queueSummary['fullListUrl'] }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="layers" class="h-4 w-4" />
                        Cola completa
                    </a>
                </div>
            </div>

            <div class="mt-queue-toolbar">
                @foreach ($queueFilters as $filter)
                    <a
                        href="{{ $filter['href'] }}"
                        @class([
                            'mt-quick-filter',
                            'mt-quick-filter-active' => $selectedQueue === $filter['key'],
                        ])
                    >
                        <span>{{ $filter['label'] }}</span>
                        <span class="mt-filter-count">{{ $filter['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-queue-stack">
                @forelse ($queueItems as $item)
                    @include('mesa-tecnica.partials.queue-ticket-card', [
                        'recepcion' => $item['recepcion'],
                        'returnTo' => $returnTo,
                        'priority' => $item['priority'],
                        'priorityLabel' => $item['priority_label'],
                        'priorityHint' => $item['priority_hint'],
                        'ageLabel' => $item['age_label'],
                        'ageTone' => $item['age_tone'],
                    ])
                @empty
                    <div class="mt-empty-state">
                        No hay tickets para el filtro seleccionado.
                    </div>
                @endforelse
            </div>
        </section>

        <section id="mt-analitica" class="mt-analytics-board">
            <div>
                <p class="mt-section-kicker">Indicadores analiticos</p>
                <h3 class="mt-section-title">Lectura breve para gestion</h3>
                <p class="mt-section-copy">Resumen de flujo, permanencia, sectores derivantes, causas frecuentes y distribucion operativa.</p>
            </div>

            <div class="mt-analytics-grid">
                <article class="mt-analytics-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Hoy</p>
                            <h4 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Ingresos vs entregas</h4>
                        </div>
                        <span @class([
                            'mt-analytics-pill',
                            'mt-analytics-pill-success' => $analytics['flow']['tone'] === 'success',
                            'mt-analytics-pill-warning' => $analytics['flow']['tone'] === 'warning',
                            'mt-analytics-pill-danger' => $analytics['flow']['tone'] === 'danger',
                        ])>
                            {{ $analytics['flow']['balanceLabel'] }}
                        </span>
                    </div>

                    <div class="mt-analytics-stats">
                        <div class="mt-analytics-stat">
                            <span class="mt-analytics-stat-label">Ingresos</span>
                            <span class="mt-analytics-stat-value">{{ $analytics['flow']['ingresos'] }}</span>
                        </div>
                        <div class="mt-analytics-stat">
                            <span class="mt-analytics-stat-label">Entregas</span>
                            <span class="mt-analytics-stat-value">{{ $analytics['flow']['entregas'] }}</span>
                        </div>
                    </div>
                </article>

                <article class="mt-analytics-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tiempo</p>
                            <h4 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Permanencia promedio</h4>
                        </div>
                        <span @class([
                            'mt-analytics-pill',
                            'mt-analytics-pill-success' => $analytics['averageStay']['tone'] === 'success',
                            'mt-analytics-pill-warning' => $analytics['averageStay']['tone'] === 'warning',
                            'mt-analytics-pill-danger' => $analytics['averageStay']['tone'] === 'danger',
                        ])>
                            Activos: {{ $analytics['averageStay']['openLabel'] }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div class="mt-analytics-row">
                            <span>Activos en mesa</span>
                            <strong>{{ $analytics['averageStay']['openLabel'] }}</strong>
                        </div>
                        <div class="mt-analytics-row">
                            <span>Cerrados recientes</span>
                            <strong>{{ $analytics['averageStay']['closedLabel'] }}</strong>
                        </div>
                    </div>
                </article>

                <article class="mt-analytics-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $analytics['recentWindowLabel'] }}</p>
                            <h4 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Sectores que mas derivan</h4>
                        </div>
                    </div>

                    @if (collect($analytics['topSources'])->isNotEmpty())
                        <div class="mt-bar-list">
                            @foreach ($analytics['topSources'] as $source)
                                <div class="mt-bar-item">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-800">{{ $source['label'] }}</span>
                                        <span class="text-sm font-semibold text-slate-950">{{ $source['count'] }}</span>
                                    </div>
                                    <div class="mt-bar-track">
                                        <span class="mt-bar-fill" style="width: {{ $source['width'] }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Todavia no hay volumen reciente suficiente para mostrar derivantes.</p>
                    @endif
                </article>

                <article class="mt-analytics-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $analytics['recentWindowLabel'] }}</p>
                            <h4 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Causas mas frecuentes</h4>
                        </div>
                    </div>

                    @if (collect($analytics['topReasons'])->isNotEmpty())
                        <div class="mt-bar-list">
                            @foreach ($analytics['topReasons'] as $reason)
                                <div class="mt-bar-item">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-800">{{ $reason['label'] }}</span>
                                        <span class="text-sm font-semibold text-slate-950">{{ $reason['count'] }}</span>
                                    </div>
                                    <div class="mt-bar-track">
                                        <span class="mt-bar-fill mt-bar-fill-muted" style="width: {{ $reason['width'] }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Aun no hay motivos cargados suficientes para construir tendencia.</p>
                    @endif
                </article>

                <article class="mt-analytics-card mt-analytics-card-wide">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Estado actual</p>
                            <h4 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Equipos por estado</h4>
                        </div>
                    </div>

                    @if (collect($analytics['statusDistribution'])->isNotEmpty())
                        <div class="mt-status-grid">
                            @foreach ($analytics['statusDistribution'] as $status)
                                <div class="mt-status-card">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-700">{{ $status['label'] }}</span>
                                        <span class="text-base font-semibold text-slate-950">{{ $status['count'] }}</span>
                                    </div>
                                    <div class="mt-bar-track mt-2">
                                        <span class="mt-bar-fill" style="width: {{ $status['width'] }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No hay estados abiertos visibles en este momento.</p>
                    @endif
                </article>
            </div>
        </section>

        <x-collapsible-panel
            title="Mas informacion"
            eyebrow="Secundario"
            icon="sliders-horizontal"
            summary="Acciones rapidas, contexto operativo y reglas que no deben ocupar el centro del dashboard."
            persist-key="mesa-tecnica.dashboard.mas-informacion"
            class="mt-operational-panel rounded-[1.5rem]"
        >
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Acciones rapidas</p>
                        <h4 class="mt-1 text-lg font-semibold tracking-tight text-slate-950">Operaciones secundarias</h4>
                    </div>

                    <div class="mt-secondary-actions-grid">
                        @foreach ($secondaryActions as $action)
                            @include('mesa-tecnica.partials.action-card', $action)
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="mt-note-block">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Regla operativa</p>
                        <p class="mt-2 text-sm text-slate-700">Mesa Tecnica es custodia temporal. Si la decision cambia destino patrimonial, primero corresponde usar Actas.</p>
                    </div>

                    <div class="mt-note-block">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Uso sugerido</p>
                        <p class="mt-2 text-sm text-slate-700">Lea primero KPIs, luego alertas, despues la cola priorizada. El buscador resuelve casos puntuales sin romper el flujo.</p>
                    </div>

                    <div class="mt-note-block">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Trazabilidad</p>
                        <p class="mt-2 text-sm text-slate-700">Impresion, vinculacion y cierre siguen disponibles desde cada ticket, sin sobrecargar la vista principal.</p>
                    </div>
                </div>
            </div>
        </x-collapsible-panel>
    </div>
@endsection
