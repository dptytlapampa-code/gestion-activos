<!DOCTYPE html>
<html lang="es" class="bg-surface-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('title', 'Acceso')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface-100 text-surface-900">
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </div>
</body>
</html>
