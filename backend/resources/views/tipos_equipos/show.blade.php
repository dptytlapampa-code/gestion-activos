@extends('layouts.app')

@section('title', 'Detalle tipo de equipo')
@section('header', 'Detalle tipo de equipo')

@section('content')
    <div class="max-w-3xl card">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <x-tipo-equipo-image :tipo-equipo="$tipo_equipo" size="lg" class="rounded-xl" />
                <div>
                    <h3 class="text-xl font-semibold text-surface-900">{{ $tipo_equipo->nombre }}</h3>
                    <p class="mt-1 text-sm text-surface-500">Equipos asociados: {{ $tipo_equipo->equipos_count }}</p>
                </div>
            </div>
            <a href="{{ route('tipos-equipos.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                <x-icon name="x" class="h-4 w-4" />
                Volver
            </a>
        </div>

        <div class="mt-6">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-surface-600">Descripcion</h4>
            <p class="mt-2 whitespace-pre-line text-sm text-surface-700">{{ $tipo_equipo->descripcion ?: 'Sin descripcion' }}</p>
        </div>
    </div>
@endsection

