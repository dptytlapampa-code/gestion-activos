@extends('layouts.app')

@section('title', 'Panel')
@section('header', 'Panel general')

@section('content')
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="app-stat-card flex items-center gap-4 p-6">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="building-2" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $instituciones }}</p>
                <p class="text-sm text-slate-500">Instituciones</p>
            </div>
        </div>

        <div class="app-stat-card flex items-center gap-4 p-6">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="door-closed" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $oficinas }}</p>
                <p class="text-sm text-slate-500">Oficinas</p>
            </div>
        </div>

        <div class="app-stat-card flex items-center gap-4 p-6">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="monitor" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $totalEquipos }}</p>
                <p class="text-sm text-slate-500">Equipos</p>
            </div>
        </div>

        <div class="app-stat-card flex items-center gap-4 p-6">
            <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600">
                <svg class="h-6 w-6 shrink-0 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $equiposPorEstado[\App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO] ?? 0 }}</p>
                <p class="text-sm text-slate-500">Equipos en mantenimiento</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold">Equipos recientes</h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Ultimos 5 registros</span>
            </div>

            <div class="overflow-x-auto">
                <table class="app-table text-sm">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Serial</th>
                            <th>Oficina</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($equiposRecientes as $equipo)
                            @php
                                $statusCode = $equipo->equipoStatus?->code;
                                $statusLabel = match ($statusCode) {
                                    \App\Models\EquipoStatus::CODE_OPERATIVA => 'Operativo',
                                    \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'Fuera de servicio',
                                    \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO => 'Mantenimiento',
                                    \App\Models\EquipoStatus::CODE_BAJA => 'Baja',
                                    default => $equipo->equipoStatus?->name ?? 'Sin estado',
                                };
                                $statusClasses = match ($statusCode) {
                                    \App\Models\EquipoStatus::CODE_OPERATIVA => 'bg-green-100 text-green-700',
                                    \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO, \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'bg-yellow-100 text-yellow-700',
                                    \App\Models\EquipoStatus::CODE_BAJA => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr>
                                <td class="text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="xs" class="rounded-lg" />
                                        <span>{{ $equipo->tipo }}</span>
                                    </div>
                                </td>
                                <td>{{ $equipo->numero_serie ?: '-' }}</td>
                                <td>{{ $equipo->oficina?->nombre ?: '-' }}</td>
                                <td>{{ optional($equipo->created_at)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-slate-500">No hay equipos recientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="card lg:col-span-1">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold">Actas recientes</h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Ultimos 5 registros</span>
            </div>

            <div class="space-y-3">
                @forelse ($actas as $acta)
                    <div class="app-subcard flex items-center justify-between p-4">
                        <div class="min-w-0 text-sm text-slate-700">
                            <p class="truncate font-medium text-slate-800">{{ $acta->codigo }}</p>
                            <p>{{ $acta->created_at?->diffForHumans() }}</p>
                            <p>{{ $acta->creator?->name ?? 'Usuario' }}</p>
                        </div>
                        <a href="{{ route('actas.show', $acta) }}" class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-3 py-1 text-sm text-indigo-600 transition hover:bg-indigo-100">
                            <x-icon name="eye" class="h-4 w-4" />
                            Ver
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay actas recientes.</p>
                @endforelse
            </div>
        </aside>
    </div>
@endsection
