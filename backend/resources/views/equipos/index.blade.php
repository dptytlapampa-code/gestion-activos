@extends('layouts.app')

@section('title', 'Equipos')
@section('header', 'Equipos')

@section('content')
@php
    $resultExportQuery = array_merge(request()->except('page'), ['scope' => \App\Enums\ExportScope::RESULTS->value]);
    $allExportQuery = ['scope' => \App\Enums\ExportScope::ALL->value];
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Listado de equipos</h2>
            <p class="text-sm text-slate-500">Gestion visual de activos biomedicos y tecnologicos.</p>
        </div>
        @can('create', \App\Models\Equipo::class)
            <a href="{{ route('equipos.create') }}" class="btn btn-primary gap-2">
                <x-icon name="plus" class="h-4 w-4" />
                Crear equipo
            </a>
        @endcan
    </div>

    <form method="GET" class="card space-y-4">
        <x-listing.toolbar
            :search="$listing->search"
            :per-page="$listing->perPage"
            search-id="equipos-search"
            per-page-id="equipos-per-page"
            search-label="Busqueda rapida"
            search-placeholder="Tipo, marca, serie, patrimonial o ubicacion"
            :clear-url="route('equipos.index')"
        />

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="tipo" class="mb-2 block text-sm font-medium text-slate-700">Tipo</label>
                <input id="tipo" name="tipo" value="{{ $filters['tipo'] }}" placeholder="Ej. Monitor" class="app-input w-full" />
            </div>
            <div>
                <label for="marca" class="mb-2 block text-sm font-medium text-slate-700">Marca</label>
                <input id="marca" name="marca" value="{{ $filters['marca'] }}" placeholder="Ej. Philips" class="app-input w-full" />
            </div>
            <div>
                <label for="modelo" class="mb-2 block text-sm font-medium text-slate-700">Modelo</label>
                <input id="modelo" name="modelo" value="{{ $filters['modelo'] }}" placeholder="Ej. IntelliVue" class="app-input w-full" />
            </div>
            <div>
                <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                <select id="estado" name="estado" class="app-input w-full">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}" @selected($filters['estado'] === $estado)>{{ strtoupper(str_replace('_', ' ', $estado)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">
                Use la busqueda rapida para encontrar equipos al instante y estos filtros para refinar el listado.
            </p>
            <button class="btn btn-primary gap-2">
                <x-icon name="search" class="h-4 w-4" />
                Aplicar filtros
            </button>
        </div>
    </form>

    @can('export', \App\Models\Equipo::class)
        <x-listing.export-actions
            :results-url="route('equipos.export.csv', $resultExportQuery)"
            :all-url="route('equipos.export.csv', $allExportQuery)"
            :has-active-filters="$hasActiveFilters"
        />
    @endcan

    <div class="app-table-panel overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Estado</th>
                    <th>N serie</th>
                    <th>Ubicacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($equipos as $equipo)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="xs" class="rounded-lg" />
                            <span>{{ $equipo->tipo }}</span>
                        </div>
                    </td>
                    <td>{{ $equipo->marca }}</td>
                    <td>{{ $equipo->modelo }}</td>
                    <td>
                        @php($estadoClase = match($equipo->estado) {
                            'operativo' => 'status-operativo',
                            'prestado' => 'status-prestado',
                            'mantenimiento' => 'status-mantenimiento',
                            'fuera_de_servicio' => 'status-fuera-de-servicio',
                            'baja' => 'status-baja',
                            default => 'bg-slate-100 text-slate-700'
                        })
                        <span class="status-badge {{ $estadoClase }}">{{ strtoupper(str_replace('_', ' ', $equipo->estado)) }}</span>
                    </td>
                    <td>{{ $equipo->numero_serie }}</td>
                    <td>
                        <div class="space-y-1">
                            <div class="font-semibold text-slate-900">{{ $equipo->oficina?->service?->institution?->nombre }}</div>
                            <div class="text-sm text-slate-600">{{ $equipo->oficina?->service?->nombre }}</div>
                            <div class="text-xs text-slate-400">{{ $equipo->oficina?->nombre }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-2">
                            @can('view', $equipo)
                                <a class="btn btn-neutral !px-3 !py-1.5 gap-1.5" href="{{ route('equipos.show',$equipo) }}">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Ver
                                </a>
                            @endcan
                            @can('update', $equipo)
                                <a class="btn btn-info !px-3 !py-1.5 gap-1.5" href="{{ route('equipos.edit',$equipo) }}">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                    Editar
                                </a>
                            @endcan
                            @can('delete', $equipo)
                                <form method="POST" action="{{ route('equipos.destroy',$equipo) }}" onsubmit="return confirm('Eliminar equipo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger !px-3 !py-1.5 gap-1.5">
                                        <x-icon name="trash-2" class="h-4 w-4" />
                                        Eliminar
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-slate-500">Sin resultados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <x-listing.pagination :paginator="$equipos" />
</div>
@endsection

