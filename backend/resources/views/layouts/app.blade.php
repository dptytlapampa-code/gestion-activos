<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('title', 'Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen bg-surface-100">
        <aside class="w-72 border-r border-surface-200/70 bg-surface-50/90 px-6 py-8">
            <div class="mb-10">
                <h1 class="text-lg font-semibold text-surface-800 dark:text-surface-100">{{ config('app.name') }}</h1>
                <p class="text-xs text-surface-500 dark:text-surface-400">Base administrativa</p>
            </div>
            <nav class="space-y-2">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">Panel</a>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-surface-200/70 bg-surface-50/80 px-8 py-4 backdrop-blur">
                <div>
                    <h2 class="text-lg font-semibold text-surface-800">@yield('header', 'Panel')</h2>
                    <p class="text-sm text-surface-500">{{ now()->translatedFormat('l, j \d\e F Y') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-surface-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-surface-200/70 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">Cerrar sesión</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-700">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
