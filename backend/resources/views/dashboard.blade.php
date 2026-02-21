@extends('layouts.app')

@section('title', 'Panel')
@section('header', 'Panel general')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card">
            <h3 class="text-sm font-semibold text-surface-700">Total de equipos</h3>
            <p class="mt-3 text-3xl font-bold text-primary-700">{{ $totalEquipos }}</p>
        </div>

        <div class="card lg:col-span-2">
            <h3 class="text-sm font-semibold text-surface-700">Equipos por tipo (Top 10)</h3>
            <div class="mt-4 space-y-2">
                @forelse ($equiposPorTipo as $fila)
                    <div>
                        <div class="mb-1 flex justify-between text-xs text-surface-600">
                            <span>{{ $fila->tipo }}</span>
                            <span>{{ $fila->total }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-surface-200">
                            <div class="h-2 rounded-full bg-primary-500" style="width: {{ $totalEquipos > 0 ? ($fila->total * 100 / $totalEquipos) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-surface-500">Sin datos para mostrar.</p>
                @endforelse
            </div>
        </div>

        <div class="card lg:col-span-2">
            <h3 class="text-sm font-semibold text-surface-700">Últimos movimientos</h3>
            <div class="mt-3 space-y-2 text-sm">
                @forelse ($movimientos as $movimiento)
                    <div class="rounded-xl border border-surface-200 px-3 py-2">
                        <p class="font-medium">{{ ucfirst($movimiento->tipo_movimiento) }} · {{ $movimiento->equipo?->tipo }} ({{ $movimiento->equipo?->numero_serie }})</p>
                        <p class="text-xs text-surface-500">{{ optional($movimiento->fecha)->format('d/m/Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-surface-500">No hay movimientos recientes.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h3 class="text-sm font-semibold text-surface-700">Últimas actas</h3>
            <div class="mt-3 space-y-2 text-sm">
                @forelse ($actas as $acta)
                    <a href="{{ route('actas.show', $acta) }}" class="block rounded-xl border border-surface-200 px-3 py-2 hover:border-primary-300">
                        <p class="font-medium">{{ $acta->codigo }} · {{ ucfirst($acta->tipo) }}</p>
                        <p class="text-xs text-surface-500">{{ $acta->fecha?->format('d/m/Y') }} · {{ $acta->equipos_count }} equipos</p>
                    </a>
                @empty
                    <p class="text-sm text-surface-500">No hay actas recientes.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
