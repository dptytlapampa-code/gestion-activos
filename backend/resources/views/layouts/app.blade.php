<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $systemConfig = system_config();
        $siteName = $systemConfig->nombre_sistema;
        $logoInstitucionalUrl = $systemConfig->logo_url;
        $systemLogoUrl = $systemConfig->system_logo_url;
        $isMesaTecnicaShell = request()->routeIs('mesa-tecnica.*');
        $institutionContext = $authInstitutionContext ?? [];
        $activeInstitution = $institutionContext['activeInstitution'] ?? null;
        $primaryInstitution = $institutionContext['primaryInstitution'] ?? null;
        $accessibleInstitutions = $institutionContext['accessibleInstitutions'] ?? collect();
        $operatesGlobally = (bool) ($institutionContext['operatesGlobally'] ?? false);
        $scopeLabel = $institutionContext['scopeLabel'] ?? 'Alcance institucional';
    @endphp
    <title>{{ $siteName }} - @yield('title', 'Panel')</title>
    <link rel="icon" type="image/png" href="{{ $systemLogoUrl }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary-color: {{ $systemConfig->color_primario }};
            --primary-color-rgb: {{ $systemConfig->primary_color_rgb }};
            --sidebar-color: {{ $systemConfig->color_sidebar }};
            --sidebar-color-rgb: {{ $systemConfig->sidebar_color_rgb }};
        }
    </style>
</head>
<body
    x-data
    :class="{ 'overflow-hidden': $store.appShell.mobileSidebarOpen }"
    @keydown.escape.window="$store.appShell.closeMobileSidebar()"
    class="hospital-body min-h-screen text-slate-800"
    data-shell-context="{{ $isMesaTecnicaShell ? 'mesa-tecnica' : 'default' }}"
    data-desktop-sidebar-lock="{{ $isMesaTecnicaShell ? 'collapsed' : 'free' }}"
>
    <div class="hospital-layout min-h-screen p-3 sm:p-4 lg:p-5 xl:p-6">
        <div
            x-cloak
            x-show="$store.appShell.mobileSidebarOpen"
            x-transition.opacity
            class="app-sidebar-backdrop lg:hidden"
            @click="$store.appShell.closeMobileSidebar()"
            aria-hidden="true"
        ></div>

        <div class="flex min-h-screen gap-0 lg:gap-4 xl:gap-6">
            @include('layouts.sidebar', ['siteName' => $siteName, 'logoInstitucionalUrl' => $logoInstitucionalUrl, 'systemLogoUrl' => $systemLogoUrl])

            <div class="flex min-w-0 flex-1 flex-col gap-4 lg:gap-6">
                <header class="app-page-header flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <button
                            type="button"
                            class="app-mobile-nav-toggle w-auto gap-2 px-3 text-sm font-semibold lg:hidden"
                            @click="$store.appShell.toggleSidebar()"
                            aria-controls="app-sidebar"
                            :aria-expanded="$store.appShell.mobileSidebarOpen.toString()"
                            :aria-label="$store.appShell.mobileSidebarOpen ? 'Cerrar menu lateral' : 'Abrir menu lateral'"
                        >
                            <x-icon name="menu" class="h-5 w-5" />
                            <span>Menu</span>
                        </button>

                        <div class="min-w-0">
                            <h2 class="break-words text-2xl font-semibold text-slate-800">@yield('header', 'Panel')</h2>
                            <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j \\d\\e F Y') }}</p>
                        </div>
                    </div>

                    <div class="w-full sm:w-auto" x-data="{ open: false }">
                        <div class="relative">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-left shadow-sm transition hover:bg-slate-50 sm:min-w-[20rem]"
                                @click="open = !open"
                                @click.outside="open = false"
                                :aria-expanded="open.toString()"
                            >
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Usuario</p>
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500">Institucion activa: {{ $activeInstitution?->nombre ?? 'Sin institucion activa' }}</p>
                                    <p class="mt-1 text-xs font-semibold {{ $operatesGlobally ? 'text-emerald-700' : 'text-slate-500' }}">{{ $scopeLabel }}</p>
                                </div>
                                <x-icon name="chevron-down" class="h-4 w-4 flex-shrink-0 text-slate-500" />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top.right
                                class="absolute right-0 z-30 mt-3 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:w-[24rem]"
                            >
                                <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">Usuario: {{ auth()->user()->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">Institucion activa: {{ $activeInstitution?->nombre ?? 'Sin institucion activa' }}</p>
                                    <p class="mt-1 text-xs font-semibold {{ $operatesGlobally ? 'text-emerald-700' : 'text-slate-500' }}">{{ $scopeLabel }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Institucion principal: {{ $primaryInstitution?->nombre ?? 'Sin institucion principal' }}</p>
                                </div>

                                <div class="p-3">
                                    <a href="{{ route('profile.edit') }}" class="flex rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                        Mi perfil
                                    </a>

                                    <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Institucion activa</p>
                                        <div class="mt-2 space-y-2">
                                            @forelse ($accessibleInstitutions as $institution)
                                                <form method="POST" action="{{ route('session.active-institution.update') }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="institution_id" value="{{ $institution->id }}">
                                                    <button
                                                        type="submit"
                                                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition {{ (int) ($activeInstitution?->id ?? 0) === (int) $institution->id ? 'bg-emerald-50 font-semibold text-emerald-800' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
                                                    >
                                                        <span class="flex min-w-0 items-center gap-2">
                                                            <span class="truncate">{{ $institution->nombre }}</span>
                                                            @if (($institution->scope_type ?? null) === \App\Models\Institution::SCOPE_GLOBAL)
                                                                <span class="app-badge bg-slate-900 px-2 text-white">Global</span>
                                                            @endif
                                                        </span>
                                                        @if ((int) ($activeInstitution?->id ?? 0) === (int) $institution->id)
                                                            <span class="app-badge bg-emerald-100 px-2 text-emerald-700">Activa</span>
                                                        @endif
                                                    </button>
                                                </form>
                                            @empty
                                                <p class="text-sm text-slate-500">No hay instituciones habilitadas para operar.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <a href="{{ route('profile.edit') }}#seguridad" class="mt-2 flex rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                        Cambiar contrasena
                                    </a>
                                    <a href="{{ route('profile.edit') }}#datos" class="flex rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                        Actualizar mis datos
                                    </a>

                                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                                        @csrf
                                        <button type="submit" class="flex w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-700 transition hover:bg-red-50">
                                            Cerrar sesion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="min-w-0 flex-1 space-y-5 lg:space-y-6">
                    <x-flash-alerts />
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
</body>
</html>
