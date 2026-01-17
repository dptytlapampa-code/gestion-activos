@extends('layouts.app')

@section('title', 'Panel')
@section('header', 'Panel general')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card">
            <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-200">Bienvenido</h3>
            <p class="mt-2 text-sm text-surface-500 dark:text-surface-400">
                Esta es la base institucional del sistema de gestión de activos. Aquí iremos agregando módulos próximamente.
            </p>
        </div>
        <div class="card lg:col-span-2">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-200">Vista general</h3>
                <button type="button" class="rounded-xl border border-surface-200/70 px-3 py-1 text-xs text-surface-500 dark:border-surface-700/70 dark:text-surface-300">
                    Próximamente
                </button>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-surface-200/70 bg-surface-50/60 p-4 text-sm text-surface-500 dark:border-surface-700/70 dark:bg-surface-900/60 dark:text-surface-300">
                    Resúmenes y métricas aparecerán aquí.
                </div>
                <div class="rounded-xl border border-surface-200/70 bg-surface-50/60 p-4 text-sm text-surface-500 dark:border-surface-700/70 dark:bg-surface-900/60 dark:text-surface-300">
                    Agregá módulos cuando la base esté lista.
                </div>
            </div>
        </div>
    </div>
@endsection
