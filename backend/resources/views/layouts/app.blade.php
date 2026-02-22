<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('title', 'Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hospital-body min-h-screen">
    <div class="hospital-layout flex min-h-screen">
        <aside class="hospital-sidebar w-72 px-6 py-8">
            <div class="mb-10">
                <h1 class="text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</h1>
                <p class="mt-1 text-xs text-blue-100/90">Base administrativa hospitalaria</p>
            </div>

            <nav class="space-y-2.5">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">Panel</a>
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                    <a href="{{ route('institutions.index') }}" class="nav-link {{ request()->routeIs('institutions.*') ? 'nav-link-active' : '' }}">Instituciones</a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'nav-link-active' : '' }}">Servicios</a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <a href="{{ route('offices.index') }}" class="nav-link {{ request()->routeIs('offices.*') ? 'nav-link-active' : '' }}">Oficinas</a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a href="{{ route('tipos-equipos.index') }}" class="nav-link {{ request()->routeIs('tipos-equipos.*') ? 'nav-link-active' : '' }}">Tipos de equipo</a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a href="{{ route('equipos.index') }}" class="nav-link {{ request()->routeIs('equipos.*') ? 'nav-link-active' : '' }}">Equipos</a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a href="{{ route('actas.index') }}" class="nav-link {{ request()->routeIs('actas.*') ? 'nav-link-active' : '' }}">Actas</a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN))
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">Usuarios</a>
                    <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'nav-link-active' : '' }}">Auditoría</a>
                @endif
            </nav>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="hospital-header flex items-center justify-between px-8 py-5">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">@yield('header', 'Panel')</h2>
                    <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j \\d\\e F Y') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-neutral">Cerrar sesión</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 space-y-6 p-8">
                @if (session('status'))
                    <div class="app-alert app-alert-success" role="status" aria-live="polite">
                        <span class="app-alert-icon" aria-hidden="true">✓</span>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
