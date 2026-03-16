@extends('layouts.app')

@section('title', 'Panel')
@section('header', 'Panel general')

@section('content')
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="building-2" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $instituciones }}</p>
                <p class="text-sm text-slate-500">Instituciones</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="map-pin" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $oficinas }}</p>
                <p class="text-sm text-slate-500">Oficinas</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="monitor" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $totalEquipos }}</p>
                <p class="text-sm text-slate-500">Equipos</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                <x-icon name="stethoscope" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $equiposPorEstado[\App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO] ?? 0 }}</p>
                <p class="text-sm text-slate-500">Equipos en mantenimiento</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold">Equipos recientes</h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Maximo 5 registros</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-2 py-3 font-medium">Tipo</th>
                            <th class="px-2 py-3 font-medium">Serial</th>
                            <th class="px-2 py-3 font-medium">Oficina</th>
                            <th class="px-2 py-3 font-medium">Fecha</th>
                            <th class="px-2 py-3 font-medium">Estado</th>
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
                            <tr class="border-b border-slate-100 transition hover:bg-slate-50">
                                <td class="px-2 py-3 text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="xs" class="rounded-lg" />
                                        <span>{{ $equipo->tipo }}</span>
                                    </div>
                                </td>
                                <td class="px-2 py-3 text-slate-700">{{ $equipo->numero_serie ?: '-' }}</td>
                                <td class="px-2 py-3 text-slate-700">{{ $equipo->oficina?->nombre ?: '-' }}</td>
                                <td class="px-2 py-3 text-slate-700">{{ optional($equipo->created_at)->format('d/m/Y') }}</td>
                                <td class="px-2 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-2 py-6 text-center text-sm text-slate-500">No hay equipos recientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="rounded-xl bg-white p-6 shadow-sm lg:col-span-1">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold">Actas recientes</h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Maximo 5 registros</span>
            </div>

            <div class="space-y-3">
                @forelse ($actas as $acta)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 p-4">
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
