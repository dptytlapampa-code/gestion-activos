@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard operativo')

@section('content')
    <div class="space-y-6">
        <section class="app-panel p-6 lg:p-7">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gestion hospitalaria</p>
                    <h3 class="mt-2 text-3xl font-semibold text-slate-900">Panel institucional</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $dashboardContext['cutoff'] }}</p>
                    <p class="mt-4 max-w-3xl text-sm leading-6 text-slate-600">{{ $dashboardContext['summary'] }}</p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                            {{ $dashboardContext['scopeLabel'] }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                            {{ $dashboardContext['coverageLabel'] }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                            {{ $dashboardContext['attentionLabel'] }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach ($dashboardContext['snapshots'] as $snapshot)
                        <div class="app-subcard p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $snapshot['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $snapshot['value'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $snapshot['detail'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpiCards as $card)
                <section class="app-stat-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $card['title'] }}</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $card['value'] }}</p>
                        </div>

                        <div class="rounded-2xl p-3 {{ $card['icon_classes'] }}">
                            <x-icon :name="$card['icon']" class="h-6 w-6" />
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ $card['subtitle'] }}</p>
                    <div class="mt-4 rounded-xl bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500">
                        {{ $card['footer'] }}
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($operationalHighlights as $item)
                <section class="app-subcard p-4">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl p-2.5 {{ $item['icon_classes'] }}">
                            <x-icon :name="$item['icon']" class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-700">{{ $item['title'] }}</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $item['value'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $item['detail'] }}</p>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Equipos por estado</h3>
                        <p class="mt-1 text-sm text-slate-500">Distribucion actual del inventario segun condicion operativa.</p>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ number_format($dashboardContext['totalEquipos'], 0, ',', '.') }} equipos
                    </span>
                </div>

                @if ($dashboardContext['totalEquipos'] > 0)
                    <div class="mt-6 flex h-3 overflow-hidden rounded-full bg-slate-100">
                        @foreach ($statusChart as $item)
                            @if ($item['total'] > 0)
                                <div class="h-full" style="width: {{ $item['percentage'] }}%; background-color: {{ $item['color'] }};" title="{{ $item['label'] }}"></div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-6 space-y-4">
                        @foreach ($statusChart as $item)
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item['color'] }};"></span>
                                        <p class="text-sm font-medium text-slate-700">{{ $item['label'] }}</p>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $item['description'] }}</p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format($item['total'], 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $item['percentage'] }}%</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        No hay equipos cargados para representar el estado operativo.
                    </div>
                @endif
            </section>

            <section class="card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Equipos por tipo</h3>
                        <p class="mt-1 text-sm text-slate-500">Principales tipologias presentes en el inventario visible.</p>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        Top {{ count($typeChart) }}
                    </span>
                </div>

                @if (count($typeChart) > 0)
                    <div class="mt-6 space-y-4">
                        @foreach ($typeChart as $item)
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-700">{{ $item['label'] }}</p>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-semibold text-slate-900">{{ number_format($item['total'], 0, ',', '.') }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $item['percentage'] }}%</span>
                                    </div>
                                </div>

                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div
                                        class="h-2 rounded-full"
                                        style="width: {{ max($item['width'], 0) }}%; background-color: rgba(var(--primary-color-rgb), 0.88);"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        No hay equipos suficientes para construir la distribucion por tipo.
                    </div>
                @endif
            </section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <section class="card xl:col-span-7">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Equipos recientes</h3>
                        <p class="mt-1 text-sm text-slate-500">Ultimas altas registradas en el inventario.</p>
                    </div>

                    <a href="{{ route('equipos.index') }}" class="btn btn-neutral !px-3 !py-2">Ver inventario</a>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="app-table text-sm">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Ubicacion</th>
                                <th>Alta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($equiposRecientes as $equipo)
                                @php
                                    $statusCode = $equipo->equipoStatus?->code;
                                    $statusLabel = match ($statusCode) {
                                        \App\Models\EquipoStatus::CODE_OPERATIVA => 'Operativo',
                                        \App\Models\EquipoStatus::CODE_PRESTADO => 'Prestado',
                                        \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO => 'Servicio tecnico',
                                        \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'Fuera de servicio',
                                        \App\Models\EquipoStatus::CODE_BAJA => 'Baja',
                                        default => $equipo->equipoStatus?->name ?? 'Sin estado',
                                    };
                                    $statusClasses = match ($statusCode) {
                                        \App\Models\EquipoStatus::CODE_OPERATIVA => 'bg-green-100 text-green-700',
                                        \App\Models\EquipoStatus::CODE_PRESTADO => 'bg-blue-100 text-blue-700',
                                        \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO => 'bg-amber-100 text-amber-700',
                                        \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'bg-orange-100 text-orange-700',
                                        \App\Models\EquipoStatus::CODE_BAJA => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-slate-700">
                                        <div class="flex items-center gap-3">
                                            <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="sm" class="rounded-xl" />
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900">{{ $equipo->tipo }}</p>
                                                <p class="text-sm text-slate-500">{{ trim(($equipo->marca ?? '').' '.($equipo->modelo ?? '')) !== '' ? trim(($equipo->marca ?? '').' '.($equipo->modelo ?? '')) : 'Sin marca / modelo informado' }}</p>
                                                <p class="text-xs text-slate-500">{{ $equipo->numero_serie ? 'NS '.$equipo->numero_serie : 'Sin numero de serie' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="font-medium text-slate-800">{{ $equipo->oficina?->nombre ?? 'Sin oficina' }}</p>
                                        <p class="text-sm text-slate-500">{{ $equipo->oficina?->service?->nombre ?? 'Sin servicio' }}</p>
                                        @if ($dashboardContext['showInstitutionContext'])
                                            <p class="text-xs text-slate-500">{{ $equipo->oficina?->service?->institution?->nombre ?? 'Sin institucion' }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <p class="font-medium text-slate-800">{{ optional($equipo->created_at)->format('d/m/Y') }}</p>
                                        <p class="text-xs text-slate-500">{{ optional($equipo->created_at)->diffForHumans() }}</p>
                                    </td>
                                    <td>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-sm text-slate-500">No hay equipos recientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="space-y-6 xl:col-span-5">
                <section class="card" x-data="{ tab: 'alertas' }">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Seguimiento operativo</h3>
                            <p class="mt-1 text-sm text-slate-500">Alertas prioritarias y actividad reciente del inventario.</p>
                        </div>

                        <div class="rounded-xl bg-slate-100 p-1">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-sm font-semibold transition"
                                :class="tab === 'alertas' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                @click="tab = 'alertas'"
                            >
                                Alertas
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-sm font-semibold transition"
                                :class="tab === 'actividad' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                @click="tab = 'actividad'"
                            >
                                Actividad
                            </button>
                        </div>
                    </div>

                    <div x-cloak x-show="tab === 'alertas'" x-transition.opacity.duration.150ms class="mt-6 space-y-4">
                        @foreach ($alertItems as $alert)
                            <div class="rounded-2xl border px-4 py-4 {{ $alert['container_classes'] }}">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $alert['icon_classes'] }}">
                                        <x-icon :name="$alert['icon']" class="h-5 w-5" />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-slate-900">{{ $alert['title'] }}</p>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $alert['badge'] }}</span>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-600">{{ $alert['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if ($ultimosServicioTecnico->isNotEmpty())
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Casos abiertos en servicio tecnico</p>
                                        <p class="mt-1 text-xs text-slate-500">Equipos que continuan sin egreso registrado.</p>
                                    </div>

                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-500">
                                        {{ $ultimosServicioTecnico->count() }} visibles
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach ($ultimosServicioTecnico->take(4) as $mantenimiento)
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-slate-800">{{ $mantenimiento->equipo?->tipo ?? 'Equipo sin tipo' }}</p>
                                                <p class="text-xs text-slate-500">{{ $mantenimiento->equipo?->numero_serie ? 'NS '.$mantenimiento->equipo->numero_serie : 'Sin numero de serie' }}</p>
                                            </div>

                                            <div class="shrink-0 text-right text-xs text-slate-500">
                                                <p>{{ optional($mantenimiento->fecha_ingreso_st ?? $mantenimiento->fecha)->format('d/m/Y') }}</p>
                                                <p class="mt-1">{{ optional($mantenimiento->fecha_ingreso_st ?? $mantenimiento->fecha)->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div x-cloak x-show="tab === 'actividad'" x-transition.opacity.duration.150ms class="mt-6">
                        @if (count($activityItems) > 0)
                            <div class="space-y-3">
                                @foreach ($activityItems as $item)
                                    <div class="app-subcard p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item['dot_color'] }};"></span>
                                                    <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
                                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item['badge_classes'] }}">
                                                        {{ $item['relative'] }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm text-slate-600">{{ $item['meta'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $item['user'] }}</p>
                                            </div>

                                            <div class="shrink-0 text-right text-xs text-slate-500">
                                                {{ $item['datetime'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                                No hay movimientos recientes para mostrar.
                            </div>
                        @endif
                    </div>
                </section>

                <section class="card">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Actas recientes</h3>
                            <p class="mt-1 text-sm text-slate-500">Documentacion emitida en el periodo mas reciente.</p>
                        </div>

                        <a href="{{ route('actas.index') }}" class="btn btn-neutral !px-3 !py-2">Ver actas</a>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($actas as $acta)
                            @php
                                $actaStatus = $acta->status ?? \App\Models\Acta::STATUS_ACTIVA;
                                $actaStatusClasses = $actaStatus === \App\Models\Acta::STATUS_ANULADA
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-emerald-100 text-emerald-700';
                                $actaStatusLabel = $actaStatus === \App\Models\Acta::STATUS_ANULADA ? 'Anulada' : 'Activa';
                            @endphp
                            <div class="app-subcard app-subcard-hover p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $acta->codigo }}</p>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $acta->tipo_label }}</span>
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $actaStatusClasses }}">{{ $actaStatusLabel }}</span>
                                        </div>

                                        <p class="mt-2 text-sm text-slate-600">{{ $acta->receptor_nombre ?: 'Sin receptor informado' }}</p>

                                        <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                                            <span>{{ optional($acta->fecha)->format('d/m/Y') }}</span>
                                            <span>{{ $acta->equipos_count }} equipos</span>
                                            <span>{{ $acta->creator?->name ?? 'Usuario' }}</span>
                                            @if ($dashboardContext['showInstitutionContext'])
                                                <span>{{ $acta->institution?->nombre ?? 'Sin institucion' }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <a href="{{ route('actas.show', $acta) }}" class="btn btn-neutral !px-3 !py-2 gap-1">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Ver
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                                No hay actas recientes.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
