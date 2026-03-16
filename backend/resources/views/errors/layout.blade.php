<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('title', 'Error')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hospital-body min-h-screen text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-6 py-12">
        @yield('content')
    </main>
</body>
</html>
