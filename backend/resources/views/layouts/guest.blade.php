<!DOCTYPE html>
<html lang="es" class="bg-surface-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = $uiSettings['site_name'] ?? config('app.name');
        $primaryColor = $uiSettings['primary_color'] ?? '#4F46E5';
        $sidebarColor = $uiSettings['sidebar_color'] ?? '#4338CA';
        $primaryRgb = $uiSettings['primary_color_rgb'] ?? '79, 70, 229';
        $sidebarRgb = $uiSettings['sidebar_color_rgb'] ?? '67, 56, 202';
    @endphp
    <title>{{ $siteName }} - @yield('title', 'Acceso')</title>
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
<body class="min-h-screen bg-surface-100 text-surface-900">
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="w-full max-w-md space-y-4">
            <x-flash-alerts />
            @yield('content')
        </div>
    </div>
</body>
</html>
