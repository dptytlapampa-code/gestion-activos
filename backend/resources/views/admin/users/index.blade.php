@extends('layouts.app')

@section('title', 'Usuarios')
@section('header', 'Usuarios')

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Nuevo usuario</a>

    <div class="app-table-panel">
        <table class="app-table text-sm">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Institucion</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role }}</td>
                        <td>{{ $user->institution?->nombre ?? '-' }}</td>
                        <td>{{ $user->is_active ? 'Si' : 'No' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <a class="text-indigo-600" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                                <form method="POST" action="{{ route('admin.users.toggle_active', $user) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-amber-600">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.reset_password', $user) }}" class="inline">
                                    @csrf
                                    <button class="text-rose-600">Reset pass</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
