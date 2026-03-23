@extends('layouts.app')

@section('title', 'Actas')
@section('header', 'Actas de trazabilidad')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-semibold text-slate-900">Listado de actas</h3>
            <p class="text-sm text-slate-500">Busqueda compartible y paginacion preparada para volumen alto.</p>
        </div>
        @can('create', \App\Models\Acta::class)
            <a href="{{ route('actas.create') }}" class="btn btn-primary w-full sm:w-auto min-h-[48px] gap-2">
                <x-icon name="plus" class="h-4 w-4" />
                Nueva acta
            </a>
        @endcan
    </div>

    <div class="app-filter-panel p-4 sm:p-5 lg:p-6">
        <form method="GET" class="space-y-4">
            <x-listing.toolbar
                :search="$listing->search"
                :per-page="$listing->perPage"
                search-id="actas-search"
                per-page-id="actas-per-page"
                search-label="Busqueda rapida"
                search-placeholder="Codigo, receptor, DNI, tipo o institucion"
                :clear-url="route('actas.index')"
            />

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="tipo" class="mb-2 block text-sm font-medium text-slate-700">Tipo</label>
                    <select id="tipo" name="tipo" class="app-input">
                        <option value="">Todos</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected(($filters['tipo'] ?? null) === $tipo)>{{ $tipoLabels[$tipo] ?? strtoupper($tipo) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="fecha_desde" class="mb-2 block text-sm font-medium text-slate-700">Fecha desde</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="app-input">
                </div>
                <div>
                    <label for="fecha_hasta" class="mb-2 block text-sm font-medium text-slate-700">Fecha hasta</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="app-input">
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    La busqueda rapida se aplica sola. Use tipo y fechas para acotar el periodo.
                </p>
                <button type="submit" class="btn btn-primary w-full sm:w-auto min-h-[44px] gap-2">
                    <x-icon name="search" class="h-4 w-4" />
                    Aplicar filtros
                </button>
            </div>
        </form>
    </div>

    <div class="space-y-3 md:hidden">
        @forelse ($actas as $acta)
            @php($isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA)
            <article class="app-subcard p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-slate-900">{{ $acta->codigo }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="app-badge bg-slate-100 text-slate-600">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</span>
                            <span class="app-badge {{ $isAnulada ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $isAnulada ? 'Anulada' : 'Activa' }}
                            </span>
                        </div>
                    </div>

                    <p class="app-cell-nowrap text-sm font-medium text-slate-500">{{ $acta->fecha?->format('d/m/Y') }}</p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Responsable</p>
                        <p class="mt-1 text-sm font-medium text-slate-900 app-cell-wrap">{{ $acta->receptor_nombre ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Equipos</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $acta->equipos_count }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('actas.show', $acta) }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-1.5 gap-1.5">
                        <x-icon name="eye" class="h-4 w-4" />
                        Ver
                    </a>
                    <a href="{{ route('actas.download', $acta) }}" class="btn btn-primary w-full sm:w-auto !px-3 !py-1.5 gap-1.5">
                        <x-icon name="download" class="h-4 w-4" />
                        PDF
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                No hay actas registradas.
            </div>
        @endforelse
    </div>

    <div class="hidden md:block app-table-panel">
        <table class="app-table min-w-[58rem] text-sm">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Equipos</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($actas as $acta)
                    @php($isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA)
                    <tr>
                        <td class="app-cell-nowrap font-medium text-slate-900">{{ $acta->codigo }}</td>
                        <td class="app-cell-nowrap">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</td>
                        <td class="app-cell-nowrap">{{ $acta->fecha?->format('d/m/Y') }}</td>
                        <td class="app-cell-nowrap">
                            <span class="app-badge {{ $isAnulada ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $isAnulada ? 'Anulada' : 'Activa' }}
                            </span>
                        </td>
                        <td class="min-w-[14rem] app-cell-wrap">{{ $acta->receptor_nombre ?: '-' }}</td>
                        <td class="app-cell-nowrap">{{ $acta->equipos_count }}</td>
                        <td class="app-cell-nowrap text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('actas.show', $acta) }}" class="btn btn-neutral !px-3 !py-1.5 gap-1.5">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Ver
                                </a>
                                <a href="{{ route('actas.download', $acta) }}" class="btn btn-primary !px-3 !py-1.5 gap-1.5">
                                    <x-icon name="download" class="h-4 w-4" />
                                    PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-500">No hay actas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-listing.pagination :paginator="$actas" />
</div>
@endsection
