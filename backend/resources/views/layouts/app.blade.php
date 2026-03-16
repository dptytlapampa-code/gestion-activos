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
<body class="hospital-body min-h-screen text-slate-800">
    <div class="hospital-layout flex min-h-screen gap-6 p-6">
        @include('layouts.sidebar', ['siteName' => $siteName, 'logoInstitucionalUrl' => $logoInstitucionalUrl, 'systemLogoUrl' => $systemLogoUrl])

        <div class="flex flex-1 flex-col gap-6">
            <header class="app-page-header">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-800">@yield('header', 'Panel')</h2>
                    <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j \\d\\e F Y') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="app-user-chip">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-neutral">Cerrar sesion</button>
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
