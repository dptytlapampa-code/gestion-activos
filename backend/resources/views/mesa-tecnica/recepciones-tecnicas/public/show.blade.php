<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = system_config();
        $siteName = $settings->site_name ?? config('app.name');
        $systemLogoUrl = $settings->system_logo_url ?? asset('images/system/logo-sistema.png');
    @endphp
    <title>{{ $siteName }} - Seguimiento de ingreso tecnico</title>
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
    <div class="mx-auto flex min-h-screen w-full max-w-5xl items-start px-6 py-10">
        <section class="card w-full space-y-6">
            <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Seguimiento publico</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $recepcionTecnica->codigo }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Consulta de solo lectura para seguimiento general del ingreso tecnico.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    {{ $publicStatus }}
                </span>
            </header>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Fecha</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->fecha_recepcion?->format('d/m/Y') ?: '-' }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Equipo</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->equipmentReference() }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Sector receptor</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->institution?->nombre ?: 'Sin institucion' }} / {{ $recepcionTecnica->sector_receptor }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Codigo interno</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->codigo_interno_equipo ?: 'Pendiente de vinculacion' }}</p>
                </div>
            </div>

            <div class="app-subcard border-blue-200 bg-blue-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Estado general del proceso</p>
                <p class="mt-2 text-sm text-slate-800">{{ $publicProgress }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Procedencia publicada</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $recepcionTecnica->procedenciaResumen() }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Motivo informado</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $recepcionTecnica->falla_motivo ?: 'Sin detalle publicado' }}</p>
                </div>
            </div>

            <p class="text-xs text-slate-500">
                Esta vista no publica documento completo, telefono, observaciones internas ni otros datos sensibles del registro operativo.
            </p>
        </section>
    </div>
</body>
</html>
