<!DOCTYPE html>
<html lang="es" class="bg-surface-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = $settings->site_name ?? config('app.name');
    @endphp
    <title>{{ $siteName }} - @yield('title', 'Acceso')</title>
    <style>
        :root {
            --primary-color: {{ $settings->primary_color ?? '#4F46E5' }};
            --primary-color-rgb: {{ $settings->primary_color_rgb ?? '79, 70, 229' }};
            --sidebar-color: {{ $settings->sidebar_color ?? '#4338CA' }};
            --sidebar-color-rgb: {{ $settings->sidebar_color_rgb ?? '67, 56, 202' }};
        }
        .btn-primary-theme {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary-theme:hover {
            filter: brightness(0.92);
        }

        .form-control {
            margin-top: 0.5rem;
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid rgb(221 225 238);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgb(15 23 42);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.2);
        }

        .form-control-error {
            border-color: rgb(252 165 165);
        }

        .form-error {
            margin-top: 0.25rem;
            font-size: 0.75rem;
            line-height: 1rem;
            color: rgb(220 38 38);
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

