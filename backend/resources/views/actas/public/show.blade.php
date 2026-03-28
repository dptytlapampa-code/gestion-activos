<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = system_config();
        $siteName = $settings->site_name ?? config('app.name');
        $systemLogoUrl = $settings->system_logo_url ?? asset('images/system/logo-sistema.png');
        $isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA;
        $equipmentRows = collect($equipmentTable['rows'] ?? [])->values();
        $destinoTexto = ($destinoInstitucional['has_data'] ?? false) === true
            ? ($destinoInstitucional['texto'] ?? '-')
            : ($acta->tipo === \App\Models\Acta::TIPO_PRESTAMO
                ? 'Prestamo a responsable identificado en el registro interno.'
                : 'Sin destino adicional publicado.');
    @endphp
    <title>{{ $siteName }} - Validacion publica de acta</title>
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
    <div class="mx-auto flex min-h-screen w-full max-w-6xl items-start px-6 py-10">
        <section class="card w-full space-y-6">
            <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Validacion publica de acta</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $acta->codigo }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Consulta institucional de solo lectura para trazabilidad documental.</p>
                </div>
                <div class="flex flex-col gap-2 md:items-end">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $isAnulada ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $isAnulada ? 'ACTA ANULADA' : 'ACTA ACTIVA' }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $tipoLabel }}</span>
                </div>
            </header>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Fecha</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $acta->fecha?->format('d/m/Y') ?: '-' }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Origen administrativo</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $originSummary['headline'] ?? '-' }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Destino publicado</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $destinoTexto }}</p>
                </div>
                <div class="app-subcard p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Equipos asociados</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $equipmentRows->count() }}</p>
                </div>
            </div>

            @if (! empty($equipoPublicUrl) && $equipmentRows->count() === 1)
                <div class="app-subcard border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Trazabilidad del equipo</p>
                    <p class="mt-1 text-sm text-slate-800">
                        Esta acta corresponde a un unico equipo. Puede consultar su ficha publica estable en
                        <a href="{{ $equipoPublicUrl }}" class="font-semibold text-blue-700 underline">{{ $equipoPublicUrl }}</a>.
                    </p>
                </div>
            @endif

            <div class="app-table-panel">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Resumen de equipos</h2>
                    <p class="mt-1 text-sm text-slate-500">Se publica la identificacion necesaria para validar el documento sin exponer datos sensibles del receptor.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="app-table min-w-[56rem] text-sm">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Marca / Modelo</th>
                                <th>Codigo interno</th>
                                <th>Serie</th>
                                @if (! empty($equipmentTable['show_origin_column']))
                                    <th>Origen individual</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($equipmentRows as $row)
                                <tr>
                                    <td class="min-w-[14rem] app-cell-wrap font-medium text-slate-900">{{ $row['equipo'] ?: '-' }}</td>
                                    <td class="min-w-[14rem] app-cell-wrap">{{ trim(implode(' / ', array_filter([$row['marca'] ?: null, $row['modelo'] ?: null]))) ?: '-' }}</td>
                                    <td class="app-cell-nowrap">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $row['codigo_interno'] ?: '-' }}</span>
                                    </td>
                                    <td class="app-cell-nowrap">{{ $row['serie'] ?: '-' }}</td>
                                    @if (! empty($equipmentTable['show_origin_column']))
                                        <td class="min-w-[18rem] app-cell-wrap">{{ $row['origen'] ?: '-' }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ! empty($equipmentTable['show_origin_column']) ? 5 : 4 }}" class="py-4 text-center text-slate-500">No hay equipos publicados para esta acta.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
