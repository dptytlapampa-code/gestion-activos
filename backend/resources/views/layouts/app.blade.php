<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('title', 'Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface-100">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 flex items-center justify-between rounded-2xl border border-surface-200 bg-white px-6 py-4">
            <div>
                <h1 class="text-lg font-semibold text-surface-800">{{ config('app.name') }}</h1>
                <p class="text-sm text-surface-500">Infraestructura inicial</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-surface-500">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-700 hover:border-surface-300">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <main class="flex-1">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-700">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
