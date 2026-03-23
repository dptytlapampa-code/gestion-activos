@extends('layouts.app')

@section('title', 'Usuarios')
@section('header', 'Usuarios')

@section('content')
<div class="space-y-4 sm:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-semibold text-slate-900">Usuarios del sistema</h3>
            <p class="text-sm text-slate-500">Administracion de accesos, roles y alcance institucional.</p>
        </div>

        <a href="{{ route('admin.users.create') }}" class="btn btn-primary w-full sm:w-auto">Nuevo usuario</a>
    </div>

    <div class="space-y-3 md:hidden">
        @forelse ($users as $user)
            <article class="app-subcard p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="mt-1 text-sm text-slate-600 app-cell-wrap">{{ $user->email }}</p>
                    </div>

                    <span class="app-badge {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rol</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $user->role }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Institucion</p>
                        <p class="mt-1 text-sm font-medium text-slate-900 app-cell-wrap">{{ $user->institution?->nombre ?? '-' }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-3 text-sm font-medium">
                    <a class="text-indigo-600 whitespace-nowrap" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                    <form method="POST" action="{{ route('admin.users.toggle_active', $user) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button class="text-amber-600 whitespace-nowrap">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.reset_password', $user) }}" class="inline">
                        @csrf
                        <button class="text-rose-600 whitespace-nowrap">Reset pass</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                No hay usuarios cargados.
            </div>
        @endforelse
    </div>

    <div class="hidden md:block app-table-panel">
        <table class="app-table min-w-[52rem] text-sm">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Institucion</th>
                    <th>Activo</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="app-cell-nowrap font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="app-cell-nowrap text-slate-700">{{ $user->email }}</td>
                        <td class="app-cell-nowrap">{{ $user->role }}</td>
                        <td class="min-w-[14rem] app-cell-wrap">{{ $user->institution?->nombre ?? '-' }}</td>
                        <td class="app-cell-nowrap">
                            <span class="app-badge {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="app-cell-nowrap text-right">
                            <div class="flex justify-end gap-4 text-sm font-medium">
                                <a class="text-indigo-600 whitespace-nowrap" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                                <form method="POST" action="{{ route('admin.users.toggle_active', $user) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-amber-600 whitespace-nowrap">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.reset_password', $user) }}" class="inline">
                                    @csrf
                                    <button class="text-rose-600 whitespace-nowrap">Reset pass</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-slate-500">
                            No hay usuarios cargados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
