@extends('layouts.guest')

@section('title', 'Ingreso')

@section('content')
    @php
        $systemConfig = system_config();
        $siteName = $systemConfig->nombre_sistema;
        $logoInstitucionalUrl = $systemConfig->logo_url;
    @endphp

    <div class="card">
        <div class="flex flex-col items-center text-center">
            @if ($logoInstitucionalUrl)
                <img src="{{ $logoInstitucionalUrl }}" alt="Logo institucional" class="h-16 w-auto rounded-lg bg-surface-100 p-2">
            @else
                <div class="flex h-16 w-full max-w-[14rem] items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-sm font-medium text-slate-500">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="image" class="h-4 w-4" />
                        Sin logo institucional
                    </span>
                </div>
            @endif
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
