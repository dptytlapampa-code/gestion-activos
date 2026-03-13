@extends('layouts.app')

@section('title', 'Usuarios')
@section('header', 'Usuarios')

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.users.create') }}" class="inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Nuevo usuario</a>
    <div class="overflow-hidden rounded-xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left">
                <tr><th class="px-4 py-3">Nombre</th><th>Email</th><th>Rol</th><th>Institución</th><th>Activo</th><th class="px-4 py-3">Acciones</th></tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="border-t"><td class="px-4 py-3">{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->institution?->nombre ?? '-' }}</td><td>{{ $user->is_active ? 'Sí' : 'No' }}</td>
                    <td class="px-4 py-3 space-x-2">
                        <a class="text-indigo-600" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                        <form method="POST" action="{{ route('admin.users.toggle_active', $user) }}" class="inline">@csrf @method('PATCH') <button class="text-amber-600">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button></form>
                        <form method="POST" action="{{ route('admin.users.reset_password', $user) }}" class="inline">@csrf <button class="text-rose-600">Reset pass</button></form>
                    </td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
