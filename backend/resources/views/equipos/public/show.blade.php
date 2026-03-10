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
        <header>
            <h1 class="text-xl font-semibold text-slate-800">Ficha publica del equipo</h1>
            <p class="mt-1 text-sm text-slate-500">Consulta institucional de solo lectura.</p>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Tipo de equipo</p>
                <p class="text-sm font-medium text-slate-800">{{ $tipoEquipo }}</p>
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

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
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

            <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Codigo</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Tipo</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Fecha</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($actas as $acta)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $acta->codigo }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $acta->tipo_label }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $acta->fecha?->format('d/m/Y') ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ strtoupper((string) ($acta->status ?? '-')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-center text-slate-500">No hay actas asociadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
