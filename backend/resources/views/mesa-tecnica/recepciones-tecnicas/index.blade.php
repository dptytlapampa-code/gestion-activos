@extends('layouts.app')

@section('title', 'Ingreso tecnico')
@section('header', 'Ingreso tecnico')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Recepciones tecnicas</h3>
                <p class="text-sm text-slate-500">Tickets operativos de ingreso al area tecnica con seguimiento, impresion y vinculacion opcional a Equipos.</p>
            </div>

            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="btn btn-primary min-h-[48px] w-full gap-2 sm:w-auto">
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo ingreso tecnico
            </a>
        </div>

        <div class="app-filter-panel p-4 sm:p-5 lg:p-6">
            <form method="GET" class="space-y-4">
                <x-listing.toolbar
                    :search="$listing->search"
                    :per-page="$listing->perPage"
                    search-id="recepciones-search"
                    per-page-id="recepciones-per-page"
                    search-label="Busqueda rapida"
                    search-placeholder="Codigo, serie, patrimonial, persona, procedencia o falla"
                    :clear-url="route('mesa-tecnica.recepciones-tecnicas.index')"
                />

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                        <select id="estado" name="estado" class="app-input">
                            <option value="">Todos</option>
                            @foreach ($statusOptions as $code => $label)
                                <option value="{{ $code }}" @selected(($filters['estado'] ?? '') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="fecha_desde" class="mb-2 block text-sm font-medium text-slate-700">Fecha desde</label>
                        <input id="fecha_desde" type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="app-input">
                    </div>

                    <div>
                        <label for="fecha_hasta" class="mb-2 block text-sm font-medium text-slate-700">Fecha hasta</label>
                        <input id="fecha_hasta" type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="app-input">
                    </div>

                    <div>
                        <label for="marca_modelo" class="mb-2 block text-sm font-medium text-slate-700">Marca o modelo</label>
                        <input id="marca_modelo" type="text" name="marca_modelo" value="{{ $filters['marca_modelo'] ?? '' }}" class="app-input" placeholder="Marca, modelo o ambos">
                    </div>

                    <div>
                        <label for="numero_serie" class="mb-2 block text-sm font-medium text-slate-700">Numero de serie</label>
                        <input id="numero_serie" type="text" name="numero_serie" value="{{ $filters['numero_serie'] ?? '' }}" class="app-input" placeholder="Serie visible">
                    </div>

                    <div>
                        <label for="bien_patrimonial" class="mb-2 block text-sm font-medium text-slate-700">Bien patrimonial</label>
                        <input id="bien_patrimonial" type="text" name="bien_patrimonial" value="{{ $filters['bien_patrimonial'] ?? '' }}" class="app-input" placeholder="Patrimonial">
                    </div>

                    <div>
                        <label for="procedencia" class="mb-2 block text-sm font-medium text-slate-700">Hospital o institucion</label>
                        <input id="procedencia" type="text" name="procedencia" value="{{ $filters['procedencia'] ?? '' }}" class="app-input" placeholder="Procedencia">
                    </div>

                    <div>
                        <label for="persona_entrega" class="mb-2 block text-sm font-medium text-slate-700">Quien entrega</label>
                        <input id="persona_entrega" type="text" name="persona_entrega" value="{{ $filters['persona_entrega'] ?? '' }}" class="app-input" placeholder="Nombre o referencia">
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        El buscador principal cruza codigo, persona, procedencia, identificadores y descripcion del problema.
                    </p>

                    <button type="submit" class="btn btn-primary min-h-[44px] w-full gap-2 sm:w-auto">
                        <x-icon name="search" class="h-4 w-4" />
                        Aplicar filtros
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($recepcionesTecnicas as $recepcion)
                @php($equipo = $recepcion->resolvedEquipo())
                <article class="app-subcard p-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <span class="app-badge bg-slate-900 text-white">{{ $recepcion->codigo }}</span>
                                    <span class="app-badge {{ $recepcion->estado === \App\Models\RecepcionTecnica::ESTADO_ANULADO ? 'bg-red-100 text-red-700' : ($recepcion->estado === \App\Models\RecepcionTecnica::ESTADO_ENTREGADO ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $recepcion->statusLabel() }}
                                    </span>
                                </div>
                                <p class="text-base font-semibold text-slate-900">{{ $recepcion->equipmentReference() }}</p>
                                <p class="text-sm text-slate-600">{{ $recepcion->receptorResumen() }}</p>
                            </div>

                            <p class="text-sm font-medium text-slate-500">{{ $recepcion->fecha_recepcion?->format('d/m/Y') ?: '-' }}</p>
                        </div>

                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Procedencia</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $recepcion->procedenciaResumen() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Equipo en sistema</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $equipo?->codigo_interno ?: 'Todavia no vinculado' }}</dd>
                            </div>
                        </dl>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', $recepcion) }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-1.5 gap-1.5">
                                <x-icon name="eye" class="h-4 w-4" />
                                Ver detalle
                            </a>
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcion) }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-full sm:w-auto !px-3 !py-1.5 gap-1.5">
                                <x-icon name="file-text" class="h-4 w-4" />
                                {{ (int) $recepcion->print_count > 0 ? 'Reimprimir' : 'Imprimir' }}
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No hay ingresos tecnicos registrados con los filtros actuales.
                </div>
            @endforelse
        </div>

        <div class="hidden md:block app-table-panel">
            <table class="app-table min-w-[74rem] text-sm">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Equipo</th>
                        <th>Quien entrega</th>
                        <th>Procedencia</th>
                        <th>Equipo sistema</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recepcionesTecnicas as $recepcion)
                        @php($equipo = $recepcion->resolvedEquipo())
                        <tr>
                            <td class="app-cell-nowrap font-medium text-slate-900">{{ $recepcion->codigo }}</td>
                            <td class="app-cell-nowrap">{{ $recepcion->fecha_recepcion?->format('d/m/Y') ?: '-' }}</td>
                            <td class="app-cell-nowrap">
                                <span class="app-badge {{ $recepcion->estado === \App\Models\RecepcionTecnica::ESTADO_ANULADO ? 'bg-red-100 text-red-700' : ($recepcion->estado === \App\Models\RecepcionTecnica::ESTADO_ENTREGADO ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ $recepcion->statusLabel() }}
                                </span>
                            </td>
                            <td class="min-w-[18rem] app-cell-wrap">{{ $recepcion->equipmentReference() }}</td>
                            <td class="min-w-[14rem] app-cell-wrap">{{ $recepcion->receptorResumen() }}</td>
                            <td class="min-w-[14rem] app-cell-wrap">{{ $recepcion->procedenciaResumen() }}</td>
                            <td class="app-cell-nowrap">{{ $equipo?->codigo_interno ?: 'Pendiente' }}</td>
                            <td class="app-cell-nowrap text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', $recepcion) }}" class="btn btn-neutral !px-3 !py-1.5 gap-1.5">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Ver
                                    </a>
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcion) }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary !px-3 !py-1.5 gap-1.5">
                                        <x-icon name="file-text" class="h-4 w-4" />
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
