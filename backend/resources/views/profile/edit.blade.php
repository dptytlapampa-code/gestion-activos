@extends('layouts.app')

@section('title', 'Mi perfil')
@section('header', 'Mi perfil')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
        <section id="perfil" class="card space-y-5">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Resumen personal</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $user->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="app-subcard p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Institucion activa</dt>
                    <dd class="mt-2 text-base font-semibold text-slate-900">{{ $activeInstitution?->nombre ?? 'Sin institucion activa' }}</dd>
                </div>

                <div class="app-subcard p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Institucion principal</dt>
                    <dd class="mt-2 text-base font-semibold text-slate-900">{{ $user->institution?->nombre ?? 'Sin institucion principal' }}</dd>
                </div>

                <div class="app-subcard p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Rol</dt>
                    <dd class="mt-2 text-base font-semibold text-slate-900">{{ str($user->role)->replace('_', ' ')->title() }}</dd>
                </div>

                <div class="app-subcard p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Estado</dt>
                    <dd class="mt-2 text-base font-semibold text-slate-900">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</dd>
                </div>
            </dl>

            <div class="app-subcard p-4">
                <p class="text-sm font-semibold text-slate-900">Instituciones habilitadas</p>
                <p class="mt-1 text-xs text-slate-500">Estas instituciones le permiten cambiar el contexto de trabajo durante la sesion.</p>

                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($accessibleInstitutions as $institution)
                        <span class="app-badge bg-slate-100 px-3 text-slate-700">{{ $institution->nombre }}</span>
                    @empty
                        <p class="text-sm text-slate-500">No tiene instituciones habilitadas configuradas.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <div class="space-y-6">
            <section id="datos" class="card">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Actualizar mis datos</h3>
                    <p class="mt-1 text-sm text-slate-500">Mantenga al dia su nombre y correo de acceso.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="text-sm font-medium text-slate-700">Nombre</label>
                        <input
                            id="name"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            class="form-control @error('name') form-control-error @enderror"
                        />
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="text-sm font-medium text-slate-700">Correo electronico</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            class="form-control @error('email') form-control-error @enderror"
                        />
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <button class="btn btn-primary">Guardar datos</button>
                </form>
            </section>

            <section id="seguridad" class="card">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Cambiar contrasena</h3>
                    <p class="mt-1 text-sm text-slate-500">Use una contrasena nueva para proteger su sesion institucional.</p>
                </div>

                <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="text-sm font-medium text-slate-700">Contrasena actual</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            class="form-control @error('current_password') form-control-error @enderror"
                        />
                        @error('current_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="text-sm font-medium text-slate-700">Nueva contrasena</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control @error('password') form-control-error @enderror"
                        />
                        @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirmar nueva contrasena</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="form-control"
                        />
                    </div>

                    <button class="btn btn-primary">Actualizar contrasena</button>
                </form>
            </section>
        </div>
    </div>
@endsection
