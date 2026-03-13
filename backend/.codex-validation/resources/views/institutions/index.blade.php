@extends('layouts.app')

@section('title', 'Instituciones')
@section('header', 'Instituciones')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-semibold text-surface-900">Gestión de instituciones</h3>
            <p class="text-sm text-surface-500">Administre el catálogo de hospitales y organismos.</p>
        </div>
        @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN))
            <a href="{{ route('institutions.create') }}" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                Nueva institución
            </a>
        @endif
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Código</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Nombre</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Descripción</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-surface-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($institutions as $institution)
                    <tr>
                        <td class="px-6 py-4 text-sm font-semibold text-surface-900">{{ $institution->codigo ?? 'Pendiente' }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-surface-900">{{ $institution->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-surface-500">{{ $institution->descripcion ?? 'Sin descripción' }}</td>
                        <td class="px-6 py-4 text-right text-sm text-surface-500">
                            @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN))
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('institutions.edit', $institution) }}" class="rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('institutions.destroy', $institution) }}" onsubmit="return confirm('¿Desea eliminar esta institución?');">
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
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-surface-500">
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
