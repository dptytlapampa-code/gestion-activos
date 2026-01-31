@extends('layouts.app')

@section('title', 'Instituciones')
@section('header', 'Instituciones')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-semibold text-surface-800 dark:text-surface-100">Listado de instituciones</h3>
            <p class="text-sm text-surface-500 dark:text-surface-400">Gestione hospitales, servicios y oficinas desde un único lugar.</p>
        </div>
        @if (auth()->user()->hasRole('superadmin', 'admin'))
            <a href="{{ route('institutions.create') }}" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                Nueva institución
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-surface-200/70 bg-surface-50 shadow-sm dark:border-surface-700/70 dark:bg-surface-900">
        <table class="min-w-full divide-y divide-surface-200/60 text-sm dark:divide-surface-700/60">
            <thead class="bg-surface-50 text-left text-xs font-semibold uppercase tracking-wide text-surface-500 dark:bg-surface-800 dark:text-surface-400">
                <tr>
                    <th class="px-6 py-4">Institución</th>
                    <th class="px-6 py-4">Código</th>
                    <th class="px-6 py-4">Servicios</th>
                    <th class="px-6 py-4">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-200/60 dark:divide-surface-700/60">
                @forelse ($institutions as $institution)
                    <tr class="hover:bg-surface-50/70 dark:hover:bg-surface-800/60">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-surface-800 dark:text-surface-100">{{ $institution->name }}</div>
                            <div class="text-xs text-surface-500">ID {{ $institution->id }}</div>
                        </td>
                        <td class="px-6 py-4 text-surface-600 dark:text-surface-300">{{ $institution->code ?? '—' }}</td>
                        <td class="px-6 py-4 text-surface-600 dark:text-surface-300">{{ $institution->services_count }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $institution->active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300' }}">
                                {{ $institution->active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('institutions.show', $institution->id) }}" class="rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-600 transition hover:text-surface-900 dark:border-surface-700 dark:text-surface-300 dark:hover:text-white">Ver</a>
                                @if (auth()->user()->hasRole('superadmin', 'admin'))
                                    <a href="{{ route('institutions.edit', $institution->id) }}" class="rounded-lg border border-primary-200 px-3 py-1 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-primary-700/60 dark:text-primary-200 dark:hover:bg-primary-500/10">Editar</a>
                                @endif
                                @if (auth()->user()->hasRole('superadmin') && $institution->active)
                                    <form method="POST" action="{{ route('institutions.destroy', $institution->id) }}" onsubmit="return confirm('¿Desea desactivar esta institución?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-700/60 dark:text-rose-200 dark:hover:bg-rose-500/10">
                                            Desactivar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-surface-500 dark:text-surface-400">
                            No hay instituciones registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $institutions->links() }}
    </div>
@endsection
