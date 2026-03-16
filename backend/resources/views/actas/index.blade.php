@extends('layouts.app')

@section('title', 'Actas')
@section('header', 'Actas de trazabilidad')

@section('content')
<div class="space-y-6">
    <div class="app-filter-panel p-6">
        <form method="GET" class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="tipo" class="block text-sm font-medium text-slate-700">Tipo</label>
                <select id="tipo" name="tipo" class="app-input mt-1 w-full">
                    <option value="">Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected(($filters['tipo'] ?? null) === $tipo)>{{ $tipoLabels[$tipo] ?? strtoupper($tipo) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fecha_desde" class="block text-sm font-medium text-slate-700">Fecha desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="app-input mt-1 w-full">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-sm font-medium text-slate-700">Fecha hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="app-input mt-1 w-full">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary min-h-[48px]">Filtrar</button>
                <a href="{{ route('actas.index') }}" class="btn btn-neutral min-h-[48px]">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">Listado</h3>
        @can('create', \App\Models\Acta::class)
            <a href="{{ route('actas.create') }}" class="btn btn-primary min-h-[48px]">Nueva acta</a>
        @endcan
    </div>

    <div class="app-table-panel overflow-x-auto">
        <table class="app-table text-sm">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Equipos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($actas as $acta)
                    @php($isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA)
                    <tr>
                        <td>{{ $acta->codigo }}</td>
                        <td>{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</td>
                        <td>{{ $acta->fecha?->format('d/m/Y') }}</td>
                        <td>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $isAnulada ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $isAnulada ? 'Anulada' : 'Activa' }}
                            </span>
                        </td>
                        <td>{{ $acta->receptor_nombre ?: '-' }}</td>
                        <td>{{ $acta->equipos_count }}</td>
                        <td class="text-right">
                            <a href="{{ route('actas.show', $acta) }}" class="text-primary-600 hover:underline">Ver</a>
                            <a href="{{ route('actas.download', $acta) }}" class="ml-3 text-primary-600 hover:underline">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-500">No hay actas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $actas->links() }}
        </div>
    </div>
</div>
@endsection
