@extends('layouts.app')

@section('title', 'Detalle de institución')
@section('header', 'Detalle de institución')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-surface-200/70 bg-white p-6 shadow-sm dark:border-surface-700/70 dark:bg-surface-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-surface-800 dark:text-surface-100">{{ $institution->name }}</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Código: {{ $institution->code ?? 'Sin código' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $institution->active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300' }}">
                        {{ $institution->active ? 'Activa' : 'Inactiva' }}
                    </span>
                    @if (auth()->user()->hasRole('superadmin', 'admin'))
                        <a href="{{ route('institutions.edit', $institution->id) }}" class="rounded-xl border border-primary-200 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50 dark:border-primary-700/60 dark:text-primary-200 dark:hover:bg-primary-500/10">
                            Editar
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-surface-200/70 bg-white p-6 shadow-sm dark:border-surface-700/70 dark:bg-surface-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-surface-800 dark:text-surface-100">Servicios y oficinas</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Vista jerárquica (solo lectura).</p>
                </div>
                <a href="{{ route('institutions.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm font-semibold text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:text-surface-300">
                    Volver
                </a>
            </div>

            <div class="space-y-4">
                @forelse ($institution->services as $service)
                    <div class="rounded-xl border border-surface-200/70 bg-surface-50/70 p-4 dark:border-surface-700/60 dark:bg-surface-800/60">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-surface-700 dark:text-surface-200">{{ $service->name }}</p>
                                <p class="text-xs text-surface-500">Servicio ID {{ $service->id }}</p>
                            </div>
                            <span class="text-xs text-surface-500">{{ $service->offices->count() }} oficinas</span>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            @forelse ($service->offices as $office)
                                <div class="rounded-lg border border-surface-200/60 bg-white px-3 py-2 text-xs text-surface-600 dark:border-surface-700/60 dark:bg-surface-900 dark:text-surface-300">
                                    <div class="font-semibold text-surface-700 dark:text-surface-200">{{ $office->name }}</div>
                                    <div>Piso: {{ $office->floor ?? '—' }}</div>
                                </div>
                            @empty
                                <p class="text-xs text-surface-500">Sin oficinas registradas.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-surface-500">No hay servicios registrados.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
