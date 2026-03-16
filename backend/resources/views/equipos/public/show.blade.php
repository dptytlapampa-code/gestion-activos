@extends('layouts.guest')

@section('title', 'Ficha publica de equipo')

@section('content')
    @php
        $tipoEquipo = $equipo->tipoEquipo?->nombre ?? $equipo->tipo ?? '-';
        $hospital = $equipo->oficina?->service?->institution?->nombre ?? '-';
        $servicio = $equipo->oficina?->service?->nombre ?? '-';
        $oficina = $equipo->oficina?->nombre ?? '-';
        $estado = $equipo->equipoStatus?->name ?? $equipo->estado ?? '-';
    @endphp

    <section class="card space-y-6">
        <header class="flex items-start gap-4">
            <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="lg" class="rounded-xl" />
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Ficha publica del equipo</h1>
                <p class="mt-1 text-sm text-slate-500">Consulta institucional de solo lectura.</p>
            </div>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Tipo de equipo</p>
                <div class="mt-1 flex items-center gap-2">
                    <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="xs" class="rounded-lg" />
                    <p class="text-sm font-medium text-slate-800">{{ $tipoEquipo }}</p>
                </div>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Marca</p>
                <p class="text-sm font-medium text-slate-800">{{ $equipo->marca ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Modelo</p>
                <p class="text-sm font-medium text-slate-800">{{ $equipo->modelo ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Numero de serie</p>
                <p class="text-sm font-medium text-slate-800">{{ $equipo->numero_serie ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Bien patrimonial</p>
                <p class="text-sm font-medium text-slate-800">{{ $equipo->bien_patrimonial ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Estado del equipo</p>
                <p class="text-sm font-medium text-slate-800">{{ $estado }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Hospital</p>
                <p class="text-sm font-medium text-slate-800">{{ $hospital }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Servicio</p>
                <p class="text-sm font-medium text-slate-800">{{ $servicio }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-slate-500">Oficina</p>
                <p class="text-sm font-medium text-slate-800">{{ $oficina }}</p>
            </div>
        </div>

        <div class="app-subcard p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Ultima accion</p>
            @if ($ultimaAccion)
                <p class="mt-1 text-sm font-medium text-slate-800">
                    {{ strtoupper((string) $ultimaAccion->tipo_evento) }}
                    {{ $ultimaAccion->fecha?->format('d/m/Y H:i') ? ' - '.$ultimaAccion->fecha->format('d/m/Y H:i') : '' }}
                </p>
            @else
                <p class="mt-1 text-sm text-slate-600">Sin movimientos registrados.</p>
            @endif
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Actas asociadas</h2>

            <div class="app-table-panel mt-2 rounded-lg">
                <table class="app-table text-sm">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($actas as $acta)
                            <tr>
                                <td>{{ $acta->codigo }}</td>
                                <td>{{ $acta->tipo_label }}</td>
                                <td>{{ $acta->fecha?->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ strtoupper((string) ($acta->status ?? '-')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-500">No hay actas asociadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

