<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = $uiSettings['site_name'] ?? config('app.name');
        $primaryColor = $uiSettings['primary_color'] ?? '#4F46E5';
        $sidebarColor = $uiSettings['sidebar_color'] ?? '#4338CA';
        $primaryRgb = $uiSettings['primary_color_rgb'] ?? '79, 70, 229';
        $sidebarRgb = $uiSettings['sidebar_color_rgb'] ?? '67, 56, 202';
        $logoUrl = $uiSettings['logo_url'] ?? null;
        $navItemBase = 'app-sidebar-link';
    @endphp
    <title>{{ $siteName }} - @yield('title', 'Panel')</title>
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --primary-color-rgb: {{ $primaryRgb }};
            --sidebar-color: {{ $sidebarColor }};
            --sidebar-color-rgb: {{ $sidebarRgb }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <div class="flex min-h-screen gap-6 p-6">
        <aside class="app-sidebar">
            <div class="space-y-3 px-4 py-3">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo institucional" class="h-12 w-auto rounded-lg bg-white/90 p-1">
                @endif
                <div>
                    <h1 class="text-lg font-semibold tracking-tight text-white">{{ $siteName }}</h1>
                    <p class="mt-1 text-xs text-white/80">Panel administrativo</p>
                </div>
            </div>

            <nav class="mt-4 flex-1 space-y-1.5">
                <a href="{{ route('dashboard') }}" class="{{ $navItemBase }} {{ request()->routeIs('dashboard') ? 'app-sidebar-link-active' : '' }}">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-5.25c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" />
                    </svg>
                    <span>Panel</span>
                </a>

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                    <a href="{{ route('institutions.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('institutions.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 21V6.75A2.25 2.25 0 0 1 7.5 4.5h9a2.25 2.25 0 0 1 2.25 2.25V21M9 9h.008v.008H9V9Zm0 3h.008v.008H9V12Zm0 3h.008v.008H9V15Zm3-6h.008v.008H12V9Zm0 3h.008v.008H12V12Zm0 3h.008v.008H12V15Zm3-6h.008v.008H15V9Zm0 3h.008v.008H15V12Zm0 3h.008v.008H15V15Z" />
                        </svg>
                        <span>Instituciones</span>
                    </a>
                @endif

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <a href="{{ route('services.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('services.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 15.17 6.375 6.375a1.875 1.875 0 0 0 2.652-2.652l-6.375-6.375m-6.75 6.75 6.375-6.375m0 0L8.625 3.67a1.875 1.875 0 0 0-2.652 2.652l6.375 6.375m0 0 3.182-3.182m-6.364 6.364 3.182-3.182" />
                        </svg>
                        <span>Servicios</span>
                    </a>
                @endif

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <a href="{{ route('offices.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('offices.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21V6.75a2.25 2.25 0 0 1 2.25-2.25h3a2.25 2.25 0 0 1 2.25 2.25V21m-7.5 0h7.5m-7.5 0H4.875A1.125 1.125 0 0 1 3.75 19.875V11.25c0-.621.504-1.125 1.125-1.125h2.25m8.25 10.875h3.75a1.125 1.125 0 0 0 1.125-1.125V11.25a1.125 1.125 0 0 0-1.125-1.125h-2.25M9 9.75h.008v.008H9V9.75Zm0 3h.008v.008H9v-.008Zm0 3h.008v.008H9v-.008Zm3-6h.008v.008H12V9.75Zm0 3h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm3-6h.008v.008H15V9.75Zm0 3h.008v.008H15v-.008Zm0 3h.008v.008H15v-.008Z" />
                        </svg>
                        <span>Oficinas</span>
                    </a>
                @endif

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a href="{{ route('tipos-equipos.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('tipos-equipos.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5v9m0-9a2.25 2.25 0 0 0-1.5-2.121l-6-2.182a2.25 2.25 0 0 0-1.5 0l-6 2.182A2.25 2.25 0 0 0 4.5 7.5m16.5 0-6.75 2.456a2.25 2.25 0 0 1-1.5 0L6 7.5m15 3.75-6.75 2.456a2.25 2.25 0 0 1-1.5 0L6 11.25m0 0v7.5A2.25 2.25 0 0 0 7.5 21l4.5 1.636a2.25 2.25 0 0 0 1.5 0L18 21a2.25 2.25 0 0 0 1.5-2.121v-7.5" />
                        </svg>
                        <span>Tipos de equipo</span>
                    </a>
                @endif

                @can('viewAny', \App\Models\Equipo::class)
                    <a href="{{ route('equipos.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('equipos.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-1.5 2.121l-6 2.182a2.25 2.25 0 0 1-1.5 0l-6-2.182a2.25 2.25 0 0 1-1.5-2.121v-9m16.5 0-6.75 2.455a2.25 2.25 0 0 1-1.5 0L3.75 7.5m16.5 0-6-2.182a2.25 2.25 0 0 0-1.5 0l-6 2.182" />
                        </svg>
                        <span>Equipos</span>
                    </a>
                @endcan

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a href="{{ route('actas.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('actas.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-8.25A2.25 2.25 0 0 0 17.25 3.75H6.75A2.25 2.25 0 0 0 4.5 6v12A2.25 2.25 0 0 0 6.75 20.25h7.5M9 7.5h6m-6 3h6m-6 3h3m4.5 1.5 1.5 1.5m0 0 1.5 1.5m-1.5-1.5 1.5-1.5m-1.5 1.5-1.5 1.5" />
                        </svg>
                        <span>Actas</span>
                    </a>
                @endif

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN))
                    <a href="{{ route('admin.users.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('admin.users.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198v.001c0 .295-.117.578-.326.787A15.94 15.94 0 0 1 12 21c-2.331 0-4.513-.5-6.427-1.493a1.11 1.11 0 0 1-.326-.787v-.001m12.75 0a3 3 0 0 0-6 0m6 0v.001c0 .295-.117.578-.326.787A15.94 15.94 0 0 1 12 21a15.94 15.94 0 0 1-5.674-1.493A1.11 1.11 0 0 1 6 18.72v-.001m12 0a5.25 5.25 0 0 0-10.5 0m10.5 0v.001c0 .295-.117.578-.326.787A15.94 15.94 0 0 1 12 21a15.94 15.94 0 0 1-5.674-1.493A1.11 1.11 0 0 1 6 18.72v-.001M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM18.75 9a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-9 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        <span>Usuarios</span>
                    </a>

                    <a href="{{ route('admin.audit.index') }}" class="{{ $navItemBase }} {{ request()->routeIs('admin.audit.*') ? 'app-sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m5.25.75A8.25 8.25 0 1 1 3.75 10.5a8.25 8.25 0 0 1 16.5 0Z" />
                        </svg>
                        <span>Auditoria</span>
                    </a>

                    <div class="mt-3 space-y-1.5 border-t border-white/20 pt-3">
                        <p class="px-4 text-xs font-semibold uppercase tracking-wide text-white/80">Configuracion</p>
                        <a href="{{ route('admin.configuracion.general.edit') }}" class="{{ $navItemBase }} {{ request()->routeIs('admin.configuracion.general.*') ? 'app-sidebar-link-active' : '' }}">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 13.293-4.743m-1.39 14.014A7.5 7.5 0 0 1 4.5 12m0 0H3m1.5 0h1.5m10.5 0H21m-1.5 0h-1.5M12 4.5V3m0 1.5V6m0 12v1.5M12 18v-1.5" />
                            </svg>
                            <span>General</span>
                        </a>
                    </div>
                @endif
            </nav>
        </aside>

        <div class="flex flex-1 flex-col gap-6">
            <header class="flex items-center justify-between rounded-2xl bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-800">@yield('header', 'Panel')</h2>
                    <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j \\d\\e F Y') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">Cerrar sesion</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 space-y-6">
                <x-flash-alerts />
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
