@extends('layouts.app')

@section('title', 'Ingresos tecnicos')
@section('header', 'Ingresos tecnicos')

@section('content')
    @include('mesa-tecnica.partials.module-styles')

    @php
        $persistedQuickViewQuery = request()->except(['vista', 'page']);
        $returnTo = request()->fullUrl();
        $quickViewDescription = match ($quickView) {
            \App\Models\RecepcionTecnica::VISTA_LISTOS => 'Solo tickets listos para entregar.',
            \App\Models\RecepcionTecnica::VISTA_CERRADOS => 'Historial reciente separado de la operacion diaria.',
            \App\Models\RecepcionTecnica::VISTA_TODOS => 'Vista completa sin perder foco operativo.',
            default => 'Cola operativa diaria sin ruido de tickets cerrados.',
        };
        $quickViewIcons = [
            \App\Models\RecepcionTecnica::VISTA_ACTIVOS => 'layers',
            \App\Models\RecepcionTecnica::VISTA_LISTOS => 'check-circle-2',
            \App\Models\RecepcionTecnica::VISTA_CERRADOS => 'door-closed',
            \App\Models\RecepcionTecnica::VISTA_TODOS => 'dashboard',
        ];
        $quickViewCardTone = fn ($view) => match ($view) {
            \App\Models\RecepcionTecnica::VISTA_LISTOS => 'mt-kpi-card mt-kpi-card-ready mt-kpi-card-compact',
            \App\Models\RecepcionTecnica::VISTA_CERRADOS => 'mt-kpi-card mt-kpi-card-warm mt-kpi-card-compact',
            default => 'mt-kpi-card mt-kpi-card-compact',
        };
        $quickViewCardNote = [
            \App\Models\RecepcionTecnica::VISTA_ACTIVOS => 'Cola diaria',
            \App\Models\RecepcionTecnica::VISTA_LISTOS => 'Prioridad de entrega',
            \App\Models\RecepcionTecnica::VISTA_CERRADOS => 'Historial cerrado',
            \App\Models\RecepcionTecnica::VISTA_TODOS => 'Panorama general',
        ];
        $ticketTone = fn ($status) => match ((string) $status) {
            \App\Models\RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR => 'mt-state-ready',
            \App\Models\RecepcionTecnica::ESTADO_EN_REPARACION => 'mt-state-repair',
            \App\Models\RecepcionTecnica::ESTADO_ENTREGADO => 'mt-state-closed',
            \App\Models\RecepcionTecnica::ESTADO_CANCELADO,
            \App\Models\RecepcionTecnica::ESTADO_NO_REPARABLE => 'mt-state-cancelled',
            default => 'mt-state-neutral',
        };
        $tableTone = fn ($status) => match ((string) $status) {
            \App\Models\RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR => 'bg-emerald-50/70',
            \App\Models\RecepcionTecnica::ESTADO_EN_REPARACION => 'bg-indigo-50/60',
            \App\Models\RecepcionTecnica::ESTADO_ENTREGADO => 'bg-slate-100/80',
            \App\Models\RecepcionTecnica::ESTADO_CANCELADO,
            \App\Models\RecepcionTecnica::ESTADO_NO_REPARABLE => 'bg-rose-50/70',
            default => '',
        };
        $advancedFiltersCount = collect($filters)->filter(fn ($value) => filled($value))->count();
        $clearUrl = route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => $quickView]);
    @endphp

    <div class="space-y-4 lg:space-y-5">
        <section class="app-panel mt-panel mt-panel-soft rounded-[1.75rem] px-4 py-4 sm:px-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge inline-flex items-center gap-1.5 bg-indigo-50 px-3 text-indigo-700">
                            <x-icon name="monitor" class="h-3.5 w-3.5" />
                            Ingreso tecnico temporal
                        </span>
                        <span class="app-badge inline-flex items-center gap-1.5 bg-slate-100 px-3 text-slate-700">
                            <x-icon name="layers" class="h-3.5 w-3.5" />
                            {{ $recepcionesTecnicas->total() }} ticket(s)
                        </span>
                        <span class="app-badge inline-flex items-center gap-1.5 bg-slate-900 px-3 text-white">
                            <x-icon name="{{ $quickViewIcons[$quickView] ?? 'layers' }}" class="h-3.5 w-3.5" />
                            {{ $quickViewLabels[$quickView] ?? 'Activos' }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold tracking-tight text-slate-950">Seguimiento tecnico</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $quickViewDescription }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($quickViewLabels as $view => $label)
                            <a
                                href="{{ route('mesa-tecnica.recepciones-tecnicas.index', array_merge($persistedQuickViewQuery, ['vista' => $view])) }}"
                                @class([
                                    'mt-quick-filter',
                                    'mt-quick-filter-active' => $quickView === $view,
                                ])
                            >
                                <x-icon name="{{ $quickViewIcons[$view] ?? 'layers' }}" class="h-4 w-4" />
                                <span>{{ $label }}</span>
                                <span class="mt-filter-count">{{ $quickViewCounts[$view] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('mesa-tecnica.index') }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="monitor" class="h-4 w-4" />
                        Mesa tecnica
                    </a>
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="btn btn-indigo mt-primary-action w-full sm:w-auto">
                        <x-icon name="plus" class="h-4 w-4" />
                        Recibir para reparacion
                    </a>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($quickViewLabels as $view => $label)
                    <a
                        href="{{ route('mesa-tecnica.recepciones-tecnicas.index', array_merge($persistedQuickViewQuery, ['vista' => $view])) }}"
                        @class([
                            $quickViewCardTone($view),
                            'ring-1 ring-slate-900/10' => $quickView === $view,
                        ])
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="mt-icon-chip mt-icon-chip-sm @if($view === \App\Models\RecepcionTecnica::VISTA_LISTOS) text-emerald-700 @elseif($view === \App\Models\RecepcionTecnica::VISTA_CERRADOS) text-amber-700 @else text-slate-700 @endif">
                                <x-icon name="{{ $quickViewIcons[$view] ?? 'layers' }}" class="h-4 w-4" />
                            </span>
                            <p class="text-xl font-semibold tracking-tight text-slate-950">{{ $quickViewCounts[$view] ?? 0 }}</p>
                        </div>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $quickViewCardNote[$view] ?? '' }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section
            class="mt-toolbar-panel p-4 sm:p-5"
            x-data="{
                search: @js($listing->search),
                advancedOpen: @js($hasActiveFilters),
                submit() { this.$refs.form?.requestSubmit(); },
                clearSearch() { this.search = ''; this.$nextTick(() => this.submit()); },
            }"
        >
            <form method="GET" x-ref="form" class="space-y-3">
                <input type="hidden" name="vista" value="{{ $quickView }}">

                <div class="grid gap-3 xl:grid-cols-[minmax(0,1.3fr)_180px_auto_auto] xl:items-end">
                    <div>
                        <label for="recepciones-search" class="mb-2 block text-sm font-medium text-slate-700">Buscar</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <x-icon name="search" class="h-4 w-4" />
                            </span>
                            <input
                                id="recepciones-search"
                                name="search"
                                type="search"
                                x-model="search"
                                @input.debounce.400ms="submit()"
                                placeholder="Codigo, serie, patrimonial, persona, procedencia o falla"
                                class="app-input w-full pl-10 pr-10"
                                autocomplete="off"
                            >
                            <button
                                type="button"
                                x-cloak
                                x-show="search.length > 0"
                                @click="clearSearch()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600"
                                aria-label="Limpiar busqueda"
                            >
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="recepciones-per-page" class="mb-2 block text-sm font-medium text-slate-700">Por pagina</label>
                        <select id="recepciones-per-page" name="per_page" @change="submit()" class="app-input w-full">
                            @foreach (\App\Support\Listings\ListingState::perPageOptions() as $option)
                                <option value="{{ $option }}" @selected((int) $listing->perPage === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="mt-toolbar-toggle" @click="advancedOpen = !advancedOpen">
                            <x-icon name="sliders-horizontal" class="h-4 w-4" />
                            <span x-text="advancedOpen ? 'Ocultar filtros' : 'Mas filtros'"></span>
                            <span class="mt-filter-count">{{ $advancedFiltersCount }}</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-start xl:justify-end">
                        <a href="{{ $clearUrl }}" class="btn btn-neutral w-full gap-2 sm:w-auto">
                            <x-icon name="x" class="h-4 w-4" />
                            Restablecer
                        </a>
                    </div>
                </div>

                <div x-show="advancedOpen" x-cloak class="space-y-3 rounded-[1.35rem] border border-slate-200 bg-slate-50/80 p-4">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado puntual</label>
                            <select id="estado" name="estado" class="app-input">
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(($filters['estado'] ?? '') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="fecha_desde" class="mb-2 block text-sm font-medium text-slate-700">Desde</label>
                            <input id="fecha_desde" type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="app-input">
                        </div>

                        <div>
                            <label for="fecha_hasta" class="mb-2 block text-sm font-medium text-slate-700">Hasta</label>
                            <input id="fecha_hasta" type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="app-input">
                        </div>

                        <div>
                            <label for="marca_modelo" class="mb-2 block text-sm font-medium text-slate-700">Marca / modelo</label>
                            <input id="marca_modelo" type="text" name="marca_modelo" value="{{ $filters['marca_modelo'] ?? '' }}" class="app-input" placeholder="Marca o modelo">
                        </div>

                        <div>
                            <label for="numero_serie" class="mb-2 block text-sm font-medium text-slate-700">Serie</label>
                            <input id="numero_serie" type="text" name="numero_serie" value="{{ $filters['numero_serie'] ?? '' }}" class="app-input" placeholder="Serie visible">
                        </div>

                        <div>
                            <label for="bien_patrimonial" class="mb-2 block text-sm font-medium text-slate-700">Patrimonial</label>
                            <input id="bien_patrimonial" type="text" name="bien_patrimonial" value="{{ $filters['bien_patrimonial'] ?? '' }}" class="app-input" placeholder="Codigo patrimonial">
                        </div>

                        <div>
                            <label for="procedencia" class="mb-2 block text-sm font-medium text-slate-700">Procedencia</label>
                            <input id="procedencia" type="text" name="procedencia" value="{{ $filters['procedencia'] ?? '' }}" class="app-input" placeholder="Hospital o referencia">
                        </div>

                        <div>
                            <label for="persona_entrega" class="mb-2 block text-sm font-medium text-slate-700">Entrega</label>
                            <input id="persona_entrega" type="text" name="persona_entrega" value="{{ $filters['persona_entrega'] ?? '' }}" class="app-input" placeholder="Nombre o referencia">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">El buscador principal resuelve la mayoria de la operacion. Use estos filtros solo para afinar la cola.</p>
                        <button type="submit" class="btn btn-indigo mt-primary-action w-full gap-2 sm:w-auto">
                            <x-icon name="search" class="h-4 w-4" />
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <div class="space-y-3 md:hidden">
            @forelse ($recepcionesTecnicas as $recepcion)
                @php($equipo = $recepcion->resolvedEquipo())

                <article @class(['mt-ticket-card mt-card-lift', $ticketTone($recepcion->estado)])>
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="app-badge inline-flex items-center gap-1.5 bg-slate-900 px-3 text-white">
                                        <x-icon name="file-text" class="h-3.5 w-3.5" />
                                        {{ $recepcion->codigo }}
                                    </span>
                                    @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcion->estado, 'label' => $recepcion->statusLabel()])
                                </div>
                                <div>
                                    <p class="text-base font-semibold text-slate-950">{{ $recepcion->equipmentReference() }}</p>
                                    <p class="mt-inline-meta text-slate-600">
                                        <x-icon name="users" class="h-4 w-4" />
                                        {{ $recepcion->receptorResumen() }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-inline-meta text-sm font-medium text-slate-500">
                                <x-icon name="file-text" class="h-4 w-4" />
                                {{ $recepcion->ingresado_at?->format('d/m/Y H:i') ?: '-' }}
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="app-subcard p-3">
                                <p class="mt-inline-meta text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    <x-icon name="building-2" class="h-3.5 w-3.5" />
                                    Procedencia
                                </p>
                                <p class="mt-1 text-sm text-slate-900">{{ $recepcion->procedenciaResumen() }}</p>
                            </div>
                            <div class="app-subcard p-3">
                                <p class="mt-inline-meta text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    <x-icon name="clipboard-list" class="h-3.5 w-3.5" />
                                    Siguiente accion
                                </p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcion->nextActionLabel() }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $recepcion, 'return_to' => $returnTo]) }}" class="btn btn-slate w-full sm:w-auto">
                                <x-icon name="eye" class="h-4 w-4" />
                                Ver ticket
                            </a>
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcion) }}" target="_blank" rel="noopener noreferrer" class="btn btn-amber w-full sm:w-auto">
                                <x-icon name="printer" class="h-4 w-4" />
                                {{ (int) $recepcion->print_count > 0 ? 'Reimprimir' : 'Imprimir' }}
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                    No hay ingresos tecnicos para esos filtros.
                </div>
            @endforelse
        </div>

        <div class="hidden md:block app-table-panel">
            <table class="app-table min-w-[78rem] text-sm">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Estado</th>
                        <th>Equipo</th>
                        <th>Entrega</th>
                        <th>Procedencia</th>
                        <th>Inventario</th>
                        <th>Accion siguiente</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recepcionesTecnicas as $recepcion)
                        @php($equipo = $recepcion->resolvedEquipo())

                        <tr @class([$tableTone($recepcion->estado)])>
                            <td class="min-w-[11rem] app-cell-wrap">
                                <p class="font-semibold text-slate-900">{{ $recepcion->codigo }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $recepcion->ingresado_at?->format('d/m/Y H:i') ?: '-' }}</p>
                            </td>
                            <td class="app-cell-nowrap">
                                @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcion->estado, 'label' => $recepcion->statusLabel()])
                            </td>
                            <td class="min-w-[18rem] app-cell-wrap">
                                <p class="font-semibold text-slate-900">{{ $recepcion->equipmentReference() }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $recepcion->nextActionDescription() }}</p>
                            </td>
                            <td class="min-w-[14rem] app-cell-wrap">{{ $recepcion->receptorResumen() }}</td>
                            <td class="min-w-[14rem] app-cell-wrap">{{ $recepcion->procedenciaResumen() }}</td>
                            <td class="app-cell-nowrap">{{ $equipo?->codigo_interno ?: 'Pendiente' }}</td>
                            <td class="min-w-[15rem] app-cell-wrap">
                                <p class="font-semibold text-slate-900">{{ $recepcion->nextActionLabel() }}</p>
                                @if ($recepcion->isReadyForDelivery())
                                    <p class="mt-1 text-xs font-semibold text-emerald-700">Prioridad alta: sacar de la cola activa.</p>
                                @endif
                            </td>
                            <td class="app-cell-nowrap text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $recepcion, 'return_to' => $returnTo]) }}" class="btn btn-slate !px-3 !py-1.5">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Ver
                                    </a>
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcion) }}" target="_blank" rel="noopener noreferrer" class="btn btn-amber !px-3 !py-1.5">
                                        <x-icon name="printer" class="h-4 w-4" />
                                        {{ (int) $recepcion->print_count > 0 ? 'Reimprimir' : 'Imprimir' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-500">No hay ingresos tecnicos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-listing.pagination :paginator="$recepcionesTecnicas" />
    </div>
@endsection
