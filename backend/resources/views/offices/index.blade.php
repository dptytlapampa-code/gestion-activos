@extends('layouts.app')

@section('title', 'Oficinas')
@section('header', 'Oficinas')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-semibold text-surface-900">Gestión de oficinas</h3>
            <p class="text-sm text-surface-500">Administre las oficinas operativas por servicio e institución.</p>
        </div>
        @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
            <a href="{{ route('offices.create') }}" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                Nueva oficina
            </a>
        @endif
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Nombre</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Servicio</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Institución</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Descripción</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-surface-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($offices as $office)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-surface-900">{{ $office->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-surface-700">{{ $office->service?->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-surface-700">{{ $office->service?->institution?->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-surface-500">{{ $office->descripcion ?? 'Sin descripción' }}</td>
                        <td class="px-6 py-4 text-right text-sm text-surface-500">
                            @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('offices.edit', $office) }}" class="rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('offices.destroy', $office) }}" onsubmit="return confirm('¿Desea eliminar esta oficina?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-700">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-surface-400">Solo lectura</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-surface-500">
                            No hay oficinas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $offices->links() }}
    </div>
@endsection
