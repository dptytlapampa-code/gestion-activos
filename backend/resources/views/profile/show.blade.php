@extends('layouts.app')

@section('title', 'Perfil')
@section('header', 'Perfil de usuario')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-200">Información básica</h3>
            <dl class="mt-4 space-y-3 text-sm text-surface-500 dark:text-surface-300">
                <div class="flex items-center justify-between">
                    <dt>Nombre</dt>
                    <dd class="font-medium text-surface-700 dark:text-surface-100">{{ $user->name }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Email</dt>
                    <dd class="font-medium text-surface-700 dark:text-surface-100">{{ $user->email }}</dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-200">Preferencias</h3>
            <p class="mt-2 text-sm text-surface-500 dark:text-surface-400">Elegí el modo de color que mejor se adapte a tu jornada.</p>
            <form method="POST" action="{{ route('profile.theme') }}" class="mt-4 flex items-center gap-3">
                @csrf
                <input type="hidden" name="theme" value="{{ $user->theme === 'dark' ? 'light' : 'dark' }}">
                <button type="submit" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600">
                    Cambiar a modo {{ $user->theme === 'dark' ? 'claro' : 'oscuro' }}
                </button>
                <span class="text-xs text-surface-500 dark:text-surface-400">Modo actual: {{ $user->theme === 'dark' ? 'Oscuro' : 'Claro' }}</span>
            </form>
        </div>
    </div>

    <div class="mt-8 card" x-data="{ open: false }">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-200">Modal reutilizable</h3>
                <p class="mt-1 text-sm text-surface-500 dark:text-surface-400">Base para futuras ventanas flotantes de CRUD.</p>
            </div>
            <button type="button" class="rounded-xl border border-surface-200/70 px-4 py-2 text-sm text-surface-600 dark:border-surface-700 dark:text-surface-300" @click="open = true">
                Abrir modal
            </button>
        </div>
        <x-modal title="Modal de ejemplo">
            <p class="text-sm text-surface-500 dark:text-surface-300">Contenido de muestra para validar el diseño con blur.</p>
        </x-modal>
    </div>
@endsection
