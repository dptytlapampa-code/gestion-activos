<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('title', 'Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hospital-body min-h-screen" x-data="{ sidebarExpanded: false }">
    <div class="hospital-layout flex min-h-screen">
        <aside
            class="hospital-sidebar shrink-0 overflow-hidden transition-all duration-200"
            :class="sidebarExpanded ? 'w-[240px] px-6 py-8' : 'w-[70px] px-2 py-8'"
            @mouseenter="sidebarExpanded = true"
            @mouseleave="sidebarExpanded = false"
        >
            <div class="mb-10 flex items-center" :class="sidebarExpanded ? 'justify-start gap-3' : 'justify-center'">
                <span class="text-2xl leading-none">&#127973;</span>
                <div x-show="sidebarExpanded" x-transition.opacity.duration.200ms x-cloak>
                    <h1 class="text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</h1>
                    <p class="mt-1 text-xs text-blue-100/90">Base administrativa hospitalaria</p>
                </div>
            </div>

            <nav class="space-y-2.5">
                <a
                    href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"
                    :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                    title="Panel"
                >
                    <span class="text-lg leading-none">&#127968;</span>
                    <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Panel</span>
                </a>
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                    <a
                        href="{{ route('institutions.index') }}"
                        class="nav-link {{ request()->routeIs('institutions.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Instituciones"
                    >
                        <span class="text-lg leading-none">&#127973;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Instituciones</span>
                    </a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <a
                        href="{{ route('services.index') }}"
                        class="nav-link {{ request()->routeIs('services.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Servicios"
                    >
                        <span class="text-lg leading-none">&#128295;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Servicios</span>
                    </a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <a
                        href="{{ route('offices.index') }}"
                        class="nav-link {{ request()->routeIs('offices.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Oficinas"
                    >
                        <span class="text-lg leading-none">&#127970;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Oficinas</span>
                    </a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a
                        href="{{ route('tipos-equipos.index') }}"
                        class="nav-link {{ request()->routeIs('tipos-equipos.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Tipos de equipo"
                    >
                        <span class="text-lg leading-none">&#129520;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Tipos de equipo</span>
                    </a>
                @endif
                @can('viewAny', \App\Models\Equipo::class)
                    <a
                        href="{{ route('equipos.index') }}"
                        class="nav-link {{ request()->routeIs('equipos.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Equipos"
                    >
                        <span class="text-lg leading-none">&#128230;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Equipos</span>
                    </a>
                @endcan
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO, \App\Models\User::ROLE_VIEWER))
                    <a
                        href="{{ route('actas.index') }}"
                        class="nav-link {{ request()->routeIs('actas.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Actas"
                    >
                        <span class="text-lg leading-none">&#128196;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Actas</span>
                    </a>
                @endif
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN))
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Usuarios"
                    >
                        <span class="text-lg leading-none">&#128100;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Usuarios</span>
                    </a>
                    <a
                        href="{{ route('admin.audit.index') }}"
                        class="nav-link {{ request()->routeIs('admin.audit.*') ? 'nav-link-active' : '' }}"
                        :class="sidebarExpanded ? '' : '!justify-center !px-2 !gap-0'"
                        title="Auditoria"
                    >
                        <span class="text-lg leading-none">&#129534;</span>
                        <span x-show="sidebarExpanded" x-transition.opacity.duration.150ms x-cloak>Auditoria</span>
                    </a>
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
                        <button type="submit" class="btn btn-neutral">Cerrar sesion</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 space-y-6 p-8">
                <x-flash-alerts />

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
