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
>
    <div class="hospital-layout min-h-screen p-4 sm:p-5 lg:p-6">
        <div
            x-cloak
            x-show="$store.appShell.mobileSidebarOpen"
            x-transition.opacity
            class="app-sidebar-backdrop lg:hidden"
            @click="$store.appShell.closeMobileSidebar()"
            aria-hidden="true"
        ></div>

        <div class="flex min-h-screen gap-0 lg:gap-6">
            @include('layouts.sidebar', ['siteName' => $siteName, 'logoInstitucionalUrl' => $logoInstitucionalUrl, 'systemLogoUrl' => $systemLogoUrl])

            <div class="flex min-w-0 flex-1 flex-col gap-4 lg:gap-6">
                <header class="app-page-header flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <button
                            type="button"
                            class="app-mobile-nav-toggle lg:hidden"
                            @click="$store.appShell.toggleSidebar()"
                            aria-controls="app-sidebar"
                            :aria-expanded="$store.appShell.mobileSidebarOpen.toString()"
                            aria-label="Abrir menu lateral"
                        >
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>

                        <div class="min-w-0">
                            <h2 class="text-2xl font-semibold text-slate-800">@yield('header', 'Panel')</h2>
                            <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j \\d\\e F Y') }}</p>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                        <span class="app-user-chip">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-neutral w-full sm:w-auto">Cerrar sesion</button>
                        </form>
                    </div>
                </header>

                <main class="min-w-0 flex-1 space-y-6">
                    <x-flash-alerts />
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
</body>
</html>
