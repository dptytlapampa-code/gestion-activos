<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = $settings->site_name ?? config('app.name');
        $systemLogoUrl = $settings->system_logo_url ?? asset('images/system/logo-sistema.png');
    @endphp
    <title>{{ $siteName }} - @yield('title', 'Acceso')</title>
    <link rel="icon" type="image/png" href="{{ $systemLogoUrl }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary-color: {{ $settings->primary_color ?? '#4F46E5' }};
            --primary-color-rgb: {{ $settings->primary_color_rgb ?? '79, 70, 229' }};
            --sidebar-color: {{ $settings->sidebar_color ?? '#4338CA' }};
            --sidebar-color-rgb: {{ $settings->sidebar_color_rgb ?? '67, 56, 202' }};
        }
    </style>
</head>
<body class="hospital-body min-h-screen text-surface-900">
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="w-full max-w-md space-y-4">
            <x-flash-alerts />
            @yield('content')
        </div>
    </div>
</body>
</html>
