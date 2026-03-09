@extends('layouts.app')

@section('title', 'Actas')
@section('header', 'Actas de trazabilidad')

@section('content')
<div class="space-y-6">
    <div class="card">
        <form method="GET" class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="tipo" class="block text-sm font-medium text-slate-700">Tipo</label>
                <select id="tipo" name="tipo" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected(($filters['tipo'] ?? null) === $tipo)>{{ $tipoLabels[$tipo] ?? strtoupper($tipo) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fecha_desde" class="block text-sm font-medium text-slate-700">Fecha desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-sm font-medium text-slate-700">Fecha hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-300">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="min-h-[48px] rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white">Filtrar</button>
                <a href="{{ route('actas.index') }}" class="min-h-[48px] rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">Listado</h3>
        @can('create', \App\Models\Acta::class)
            <a href="{{ route('actas.create') }}" class="min-h-[48px] rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white">Nueva acta</a>
        @endcan
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-4 py-3">Codigo</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Responsable</th>
                    <th class="px-4 py-3">Equipos</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($actas as $acta)
                    <tr>
                        <td class="px-4 py-3">{{ $acta->codigo }}</td>
                        <td class="px-4 py-3">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</td>
                        <td class="px-4 py-3">{{ $acta->fecha?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $acta->receptor_nombre ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $acta->equipos_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('actas.show', $acta) }}" class="text-primary-600 hover:underline">Ver</a>
                            <a href="{{ route('actas.download', $acta) }}" class="ml-3 text-primary-600 hover:underline">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay actas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $actas->links() }}
        </div>
    </div>
</div>
@endsection
