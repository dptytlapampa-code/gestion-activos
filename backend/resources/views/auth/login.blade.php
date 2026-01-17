@extends('layouts.guest')

@section('title', 'Ingreso')

@section('content')
    <div class="card">
        <h1 class="text-xl font-semibold text-surface-800">Acceso al sistema</h1>
        <p class="mt-1 text-sm text-surface-500">Ingresá con tus credenciales institucionales.</p>

        <form class="mt-6 space-y-4" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-surface-600" for="email">Correo</label>
                <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                    class="mt-1 w-full rounded-xl border border-surface-200 bg-surface-50 px-3 py-2 text-sm text-surface-700 shadow-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-200" />
                @error('email')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-surface-600" for="password">Contraseña</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full rounded-xl border border-surface-200 bg-surface-50 px-3 py-2 text-sm text-surface-700 shadow-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-200" />
                @error('password')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center justify-between text-sm text-surface-500">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-surface-300 text-primary-500 focus:ring-primary-300" />
                    Recordarme
                </label>
            </div>
            <button type="submit" class="w-full rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600">
                Ingresar
            </button>
        </form>
    </div>
@endsection
