@extends('layouts.app')

@section('title', 'Mesa tecnica')
@section('header', 'Mesa tecnica')

@section('content')
    @include('mesa-tecnica.partials.module-styles')

    @php
        $returnTo = request()->fullUrl();
        $advancedFiltersCount = collect($filters)->filter(fn ($value) => filled($value))->count();
        $visibleTrayLabels = collect($trayLabels)
            ->except(\App\Services\RecepcionTecnicaService::TRAY_TODOS)
            ->all();
        $persistedTrayQuery = request()->except(['bandeja', 'page', 'vista']);
        $clearUrl = route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => $tray]);
        $trayDescriptions = [
            \App\Services\RecepcionTecnicaService::TRAY_EN_MESA => 'Equipos con trabajo tecnico activo en la mesa.',
            \App\Services\RecepcionTecnicaService::TRAY_LISTOS => 'Equipos que ya pueden salir por entrega.',
            \App\Services\RecepcionTecnicaService::TRAY_PENDIENTES => 'Tickets bloqueados por espera o definicion.',
            \App\Services\RecepcionTecnicaService::TRAY_FINALIZADOS => 'Historial tecnico cerrado o cancelado.',
            \App\Services\RecepcionTecnicaService::TRAY_TODOS => 'Resultado amplio para busquedas y control general.',
        ];
        $currentTrayLabel = $trayLabels[$tray] ?? 'Bandeja';
        $currentTrayDescription = $trayDescriptions[$tray] ?? 'Cola operativa de Mesa Tecnica.';
    @endphp

    <div class="space-y-4 lg:space-y-5">
        <section class="mt-search-shell space-y-4">
            <div class="space-y-1">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Mesa tecnica</h2>
                <p class="text-sm text-slate-500">Busque, filtre y resuelva la cola sin salir del flujo operativo.</p>
            </div>

            <form method="GET" action="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="mt-search-grid">
                <input type="hidden" name="bandeja" value="{{ $tray }}">

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <x-icon name="search" class="h-5 w-5" />
                    </span>
                    <input
                        id="recepciones-search"
                        name="search"
                        type="search"
                        value="{{ $listing->search }}"
                        class="app-input min-h-[3.5rem] w-full pl-12 text-base"
                        placeholder="Buscar por CI, numero de serie, acta o codigo interno"
                        autocomplete="off"
                    >
                </div>

                <button type="submit" class="btn btn-indigo mt-primary-action min-w-[9rem]">
                    <x-icon name="search" class="h-4 w-4" />
                    Buscar
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                @foreach ($visibleTrayLabels as $trayKey => $label)
                    <a
                        href="{{ route('mesa-tecnica.recepciones-tecnicas.index', array_merge($persistedTrayQuery, ['bandeja' => $trayKey])) }}"
                        @class([
                            'mt-tray-tab',
                            'mt-tray-tab-active' => $tray === $trayKey,
                        ])
                    >
                        <span>{{ $label }}</span>
                        <span class="mt-tray-tab-count">{{ $trayCounts[$trayKey] ?? 0 }}</span>
                    </a>
                @endforeach

                @if ($tray === \App\Services\RecepcionTecnicaService::TRAY_TODOS)
                    <span class="mt-tray-tab mt-tray-tab-active">
                        <span>Todos</span>
                        <span class="mt-tray-tab-count">{{ $trayCounts[\App\Services\RecepcionTecnicaService::TRAY_TODOS] ?? $recepcionesTecnicas->total() }}</span>
                    </span>
                @endif
            </div>
        </section>

        <section class="mt-queue-board space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $currentTrayLabel }}</p>
                    <h3 class="mt-1 text-lg font-semibold tracking-tight text-slate-950">{{ $recepcionesTecnicas->total() }} ticket(s) encontrados</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $currentTrayDescription }}</p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('mesa-tecnica.index') }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="monitor" class="h-4 w-4" />
                        Volver a Mesa tecnica
                    </a>

                    @if ($listing->search !== '' || $advancedFiltersCount > 0)
                        <a href="{{ $clearUrl }}" class="btn btn-neutral w-full sm:w-auto">
                            <x-icon name="x" class="h-4 w-4" />
                            Limpiar vista
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-queue-stack">
                @forelse ($recepcionesTecnicas as $recepcion)
                    @include('mesa-tecnica.partials.queue-ticket-card', [
                        'recepcion' => $recepcion,
                        'returnTo' => $returnTo,
                    ])
                @empty
                    <div class="mt-empty-state">
                        No hay ingresos tecnicos para la bandeja y filtros seleccionados.
                    </div>
                @endforelse
            </div>

            <x-listing.pagination :paginator="$recepcionesTecnicas" />
        </section>

        <x-collapsible-panel
            title="Mas opciones"
            eyebrow="Filtros"
            icon="sliders-horizontal"
            summary="Filtros avanzados y accesos auxiliares que no deben competir con la cola principal."
            :default-open="$hasActiveFilters"
            :force-open="$hasActiveFilters"
            persist-key="mesa-tecnica.recepciones-index.mas-opciones"
            class="mt-operational-panel rounded-[1.5rem]"
            :status-label="$advancedFiltersCount > 0 ? 'Activos' : 'Opcional'"
            :status-class="$advancedFiltersCount > 0 ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-slate-100 text-slate-700'"
            :status-hint="$advancedFiltersCount > 0 ? 'Hay filtros adicionales aplicados.' : 'Use este bloque solo si necesita afinar la cola.'"
        >
            <div class="space-y-5">
                <form method="GET" action="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="space-y-4">
                    <input type="hidden" name="bandeja" value="{{ $tray }}">
                    <input type="hidden" name="search" value="{{ $listing->search }}">

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
                            <label for="per_page" class="mb-2 block text-sm font-medium text-slate-700">Por pagina</label>
                            <select id="per_page" name="per_page" class="app-input">
                                @foreach (\App\Support\Listings\ListingState::perPageOptions() as $option)
                                    <option value="{{ $option }}" @selected((int) $listing->perPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
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

                        <div class="xl:col-span-2">
                            <label for="persona_entrega" class="mb-2 block text-sm font-medium text-slate-700">Persona vinculada</label>
                            <input id="persona_entrega" type="text" name="persona_entrega" value="{{ $filters['persona_entrega'] ?? '' }}" class="app-input" placeholder="Nombre de quien entrega o retira">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-600">El buscador principal resuelve la mayoria de la operacion. Use este bloque solo para afinar casos puntuales.</p>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a href="{{ $clearUrl }}" class="btn btn-neutral w-full sm:w-auto">
                                <x-icon name="x" class="h-4 w-4" />
                                Restablecer
                            </a>

                            <button type="submit" class="btn btn-indigo mt-primary-action w-full sm:w-auto">
                                <x-icon name="search" class="h-4 w-4" />
                                Aplicar filtros
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-secondary-grid">
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="mt-secondary-link">
                        <span class="mt-icon-chip mt-icon-chip-sm text-indigo-700">
                            <x-icon name="plus" class="h-4 w-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Recibir para reparacion</span>
                            <span class="mt-1 block text-sm text-slate-600">Registrar un nuevo ingreso tecnico.</span>
                        </span>
                    </a>

                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_TODOS]) }}" class="mt-secondary-link">
                        <span class="mt-icon-chip mt-icon-chip-sm text-slate-700">
                            <x-icon name="layers" class="h-4 w-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Abrir bandeja completa</span>
                            <span class="mt-1 block text-sm text-slate-600">Ver todos los tickets sin limitar por bandeja.</span>
                        </span>
                    </a>

                    <a href="{{ route('actas.index') }}" class="mt-secondary-link">
                        <span class="mt-icon-chip mt-icon-chip-sm text-amber-700">
                            <x-icon name="clipboard-list" class="h-4 w-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Actas y movimientos</span>
                            <span class="mt-1 block text-sm text-slate-600">Acceda solo si necesita trazabilidad patrimonial formal.</span>
                        </span>
                    </a>
                </div>
            </div>
        </x-collapsible-panel>
    </div>
@endsection
