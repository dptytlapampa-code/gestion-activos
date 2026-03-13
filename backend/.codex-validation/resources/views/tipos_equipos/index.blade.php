@extends('layouts.app')

@section('title', 'Tipos de equipo')
@section('header', 'Tipos de equipo')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-semibold text-surface-900">Gestion de tipos de equipo</h3>
            <p class="text-sm text-surface-500">Administre el catalogo de clasificacion para equipos biomedicos y tecnologicos.</p>
        </div>
        @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
            <a href="{{ route('tipos-equipos.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                <x-icon name="plus" class="h-4 w-4" />
                Crear tipo de equipo
            </a>
        @endif
    </div>

    <div class="mt-6 rounded-2xl border border-surface-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('tipos-equipos.index') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <div class="relative">
                <label for="q" class="sr-only">Buscar por nombre</label>
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-surface-400" />
                <input
                    id="q"
                    name="q"
                    type="text"
                    value="{{ request('q') }}"
                    placeholder="Buscar por nombre..."
                    class="w-full rounded-xl border border-surface-200 py-2 pl-10 pr-4 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                >
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-surface-200 px-4 py-2 text-sm font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                <x-icon name="search" class="h-4 w-4" />
                Buscar
            </button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Imagen</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Descripcion</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-surface-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($tipos_equipos as $tipo_equipo)
                    <tr>
                        <td class="px-6 py-4">
                            <x-tipo-equipo-image :tipo-equipo="$tipo_equipo" size="sm" class="rounded-lg" />
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-surface-900">{{ $tipo_equipo->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-surface-600">{{ $tipo_equipo->descripcion ?: 'Sin descripcion' }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('tipos-equipos.show', $tipo_equipo) }}" class="inline-flex items-center gap-1 rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                                    <x-icon name="eye" class="h-3.5 w-3.5" />
                                    Ver
                                </a>
                                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                                    <a href="{{ route('tipos-equipos.edit', $tipo_equipo) }}" class="inline-flex items-center gap-1 rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('tipos-equipos.destroy', $tipo_equipo) }}" onsubmit="return confirm('Desea eliminar este tipo de equipo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-700">
                                            <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                            Eliminar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-surface-500">No hay tipos de equipo registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $tipos_equipos->links() }}
    </div>
@endsection
