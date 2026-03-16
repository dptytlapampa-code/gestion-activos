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
            <a href="{{ route('actas.create') }}" class="min-h-[48px] rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white">Nueva acta</a>
        @endcan
    </div>

    <div class="card">
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
                    <select id="tipo" name="tipo" class="app-input w-full">
                        <option value="">Todos</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected(($filters['tipo'] ?? null) === $tipo)>{{ $tipoLabels[$tipo] ?? strtoupper($tipo) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="fecha_desde" class="mb-2 block text-sm font-medium text-slate-700">Fecha desde</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="app-input w-full">
                </div>
                <div>
                    <label for="fecha_hasta" class="mb-2 block text-sm font-medium text-slate-700">Fecha hasta</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="app-input w-full">
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    La busqueda rapida se aplica sola. Use tipo y fechas para acotar el periodo.
                </p>
                <button type="submit" class="min-h-[44px] rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white">Aplicar filtros</button>
            </div>
        </form>
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-4 py-3">Codigo</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Responsable</th>
                    <th class="px-4 py-3">Equipos</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($actas as $acta)
                    @php($isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA)
                    <tr>
                        <td class="px-4 py-3">{{ $acta->codigo }}</td>
                        <td class="px-4 py-3">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</td>
                        <td class="px-4 py-3">{{ $acta->fecha?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $isAnulada ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $isAnulada ? 'Anulada' : 'Activa' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $acta->receptor_nombre ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $acta->equipos_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('actas.show', $acta) }}" class="text-primary-600 hover:underline">Ver</a>
                            <a href="{{ route('actas.download', $acta) }}" class="ml-3 text-primary-600 hover:underline">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay actas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-listing.pagination :paginator="$actas" />
    </div>
</div>
@endsection
