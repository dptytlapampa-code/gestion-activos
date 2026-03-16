@extends('layouts.guest')

@section('title', 'Ingreso')

@section('content')
    @php
        $systemConfig = system_config();
        $siteName = $systemConfig->nombre_sistema;
        $systemLogoUrl = $systemConfig->system_logo_url;
        $logoInstitucionalUrl = $systemConfig->logo_url;
        $loginLogoUrl = $logoInstitucionalUrl ?: $systemLogoUrl;
    @endphp

    <div class="card">
        <div class="flex flex-col items-center text-center">
            <img src="{{ $loginLogoUrl }}" alt="Logo institucional" class="h-16 w-auto rounded-lg bg-surface-100 p-2">
            <h1 class="mt-3 text-xl font-semibold text-surface-800">{{ $siteName }}</h1>
            <p class="mt-1 text-sm text-surface-500">Acceso al sistema</p>
            <p class="mt-1 text-sm text-surface-500">Ingresa con tus credenciales institucionales.</p>
        </div>

        <form class="mt-6 space-y-4" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-surface-600" for="email">Correo</label>
                <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                    class="form-control @error('email') form-control-error @enderror" />
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-surface-600" for="password">Contrasena</label>
                <input id="password" name="password" type="password" required
                    class="form-control @error('password') form-control-error @enderror" />
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center justify-between text-sm text-surface-500">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-surface-300" />
                    Recordarme
                </label>
            </div>
            <button type="submit" class="btn btn-primary w-full">
                Ingresar
            </button>
        </form>
    </div>
@endsection
