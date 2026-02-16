@extends('layouts.app')

@section('title', 'Tipos de equipo')
@section('header', 'Tipos de equipo')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-semibold text-surface-900">Gestión de tipos de equipo</h3>
            <p class="text-sm text-surface-500">Administre el catálogo de clasificación para equipos biomédicos y tecnológicos.</p>
        </div>
        @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
            <a href="{{ route('tipos-equipos.create') }}" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                Crear tipo de equipo
            </a>
        @endif
    </div>

    <div class="mt-6 rounded-2xl border border-surface-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('tipos-equipos.index') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <div>
                <label for="q" class="sr-only">Buscar por nombre</label>
                <input
                    id="q"
                    name="q"
                    type="text"
                    value="{{ request('q') }}"
                    placeholder="Buscar por nombre..."
                    class="w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                />
            </div>
            <button type="submit" class="rounded-xl border border-surface-200 px-4 py-2 text-sm font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                Buscar
            </button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Descripción</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-surface-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($tipos_equipos as $tipo_equipo)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-surface-900">{{ $tipo_equipo->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-surface-600">{{ $tipo_equipo->descripcion ?: 'Sin descripción' }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('tipos-equipos.show', $tipo_equipo) }}" class="rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">Ver</a>
                                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                                    <a href="{{ route('tipos-equipos.edit', $tipo_equipo) }}" class="rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">Editar</a>
                                    <form method="POST" action="{{ route('tipos-equipos.destroy', $tipo_equipo) }}" onsubmit="return confirm('¿Desea eliminar este tipo de equipo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-700">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-sm text-surface-500">No hay tipos de equipo registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $tipos_equipos->links() }}
    </div>
@endsection
