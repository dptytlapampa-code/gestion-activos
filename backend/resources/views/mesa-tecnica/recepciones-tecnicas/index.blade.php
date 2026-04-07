@extends('layouts.app')

@section('title', 'Ingresos tecnicos')
@section('header', 'Ingresos tecnicos')

@section('content')
    @php
        $persistedQuickViewQuery = request()->except(['vista', 'page']);
        $returnTo = request()->fullUrl();
        $quickViewDescription = match ($quickView) {
            \App\Models\RecepcionTecnica::VISTA_LISTOS => 'Solo tickets listos para entregar.',
            \App\Models\RecepcionTecnica::VISTA_CERRADOS => 'Historial reciente separado de la operacion diaria.',
            \App\Models\RecepcionTecnica::VISTA_TODOS => 'Vista completa, pero ordenada para que lo operativo quede arriba.',
            default => 'Cola operativa diaria: los cerrados quedan fuera salvo filtro explicito.',
        };
    @endphp

    <div class="space-y-5 lg:space-y-6">
        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-indigo-50 px-3 text-indigo-700">Ingreso tecnico temporal</span>
                        <span class="app-badge bg-slate-100 px-3 text-slate-700">{{ $recepcionesTecnicas->total() }} ticket(s)</span>
                        <span class="app-badge bg-slate-900 px-3 text-white">{{ $quickViewLabels[$quickView] ?? 'Activos' }}</span>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold tracking-tight text-slate-950">Seguimiento tecnico</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $quickViewDescription }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('mesa-tecnica.index') }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="monitor" class="h-4 w-4" />
                        Mesa tecnica
                    </a>
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="btn btn-indigo w-full sm:w-auto">
                        <x-icon name="plus" class="h-4 w-4" />
                        Recibir para reparacion
                    </a>
                </div>
            </div>
        </section>

        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Filtro rapido</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-950">Operacion vs historial</h3>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($quickViewLabels as $view => $label)
                        <a
                            href="{{ route('mesa-tecnica.recepciones-tecnicas.index', array_merge($persistedQuickViewQuery, ['vista' => $view])) }}"
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition',
                                'border-slate-900 bg-slate-900 text-white' => $quickView === $view,
                                'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' => $quickView !== $view,
                            ])
                        >
                            <span>{{ $label }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-bold',
                                'bg-white/15 text-white' => $quickView === $view,
                                'bg-slate-100 text-slate-700' => $quickView !== $view,
                            ])>{{ $quickViewCounts[$view] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>

                <p class="text-xs text-slate-500">
                    Los tickets cerrados solo aparecen en <strong>Cerrados</strong> o <strong>Todos</strong>. La vista por defecto mantiene limpia la operacion diaria.
                </p>
            </div>
        </section>

        <section class="app-filter-panel p-4 sm:p-5">
            <form method="GET" class="space-y-4">
                <input type="hidden" name="vista" value="{{ $quickView }}">

                <x-listing.toolbar
                    :search="$listing->search"
                    :per-page="$listing->perPage"
                    search-id="recepciones-search"
                    per-page-id="recepciones-per-page"
                    search-label="Buscar"
                    search-placeholder="Codigo, serie, patrimonial, persona, procedencia o falla"
                    :clear-url="route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => $quickView])"
                />

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        El buscador principal cruza ticket, persona, procedencia e identificadores. Los filtros avanzados afinan la cola sin mezclar historia y operacion.
                    </p>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        @if ($hasActiveFilters)
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => $quickView]) }}" class="btn btn-slate w-full sm:w-auto">
                                Limpiar
                            </a>
                        @endif

                        <button type="submit" class="btn btn-indigo w-full gap-2 sm:w-auto">
                            <x-icon name="search" class="h-4 w-4" />
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <div class="space-y-3 md:hidden">
            @forelse ($recepcionesTecnicas as $recepcion)
                @php($equipo = $recepcion->resolvedEquipo())

                <article @class([
                    'app-panel rounded-[1.75rem] px-4 py-4',
                    '!border-emerald-200 !bg-emerald-50/70' => $recepcion->isReadyForDelivery(),
                ])>
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="app-badge bg-slate-900 px-3 text-white">{{ $recepcion->codigo }}</span>
                                    @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcion->estado, 'label' => $recepcion->statusLabel()])
                                </div>

                                <div>
                                    <p class="text-base font-semibold text-slate-950">{{ $recepcion->equipmentReference() }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $recepcion->receptorResumen() }}</p>
                                </div>
                            </div>

                            <p class="text-sm font-medium text-slate-500">{{ $recepcion->ingresado_at?->format('d/m/Y H:i') ?: '-' }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Procedencia</p>
                                <p class="mt-1 text-sm text-slate-900">{{ $recepcion->procedenciaResumen() }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Siguiente accion</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcion->nextActionLabel() }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Equipo sistema</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $equipo?->codigo_interno ?: 'Pendiente' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Operacion</p>
                                <p class="mt-1 text-sm text-slate-900">{{ $recepcion->nextActionDescription() }}</p>
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

                        <tr @class([
                            'bg-emerald-50/70' => $recepcion->isReadyForDelivery(),
                        ])>
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
