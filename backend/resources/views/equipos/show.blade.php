@extends('layouts.app')

@section('title', 'Detalle equipo')
@section('header', 'Detalle equipo')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Información del equipo</h3>
            @can('update', $equipo)
                <a href="{{ route('equipos.movimientos.create', $equipo) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Registrar movimiento
                </a>
            @endcan
        </div>

        <dl class="grid gap-4 md:grid-cols-2">
            <div><dt class="text-xs uppercase text-slate-500">Tipo</dt><dd class="font-medium">{{ $equipo->tipo }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Marca</dt><dd class="font-medium">{{ $equipo->marca }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Modelo</dt><dd class="font-medium">{{ $equipo->modelo }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">N° Serie</dt><dd class="font-medium">{{ $equipo->numero_serie }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Bien patrimonial</dt><dd class="font-medium">{{ $equipo->bien_patrimonial }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Estado</dt><dd class="font-medium">{{ ucfirst($equipo->estado) }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Fecha ingreso</dt><dd class="font-medium">{{ $equipo->fecha_ingreso?->format('d/m/Y') }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Ubicación</dt><dd class="font-medium">{{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}</dd></div>
        </dl>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Historial de movimientos</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Fecha</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Tipo</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Usuario</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Origen</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Destino</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Observación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($equipo->movimientos as $movimiento)
                        @php
                            $origen = $offices->get($movimiento->oficina_origen_id);
                            $destino = $offices->get($movimiento->oficina_destino_id);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ $movimiento->fecha?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ ucfirst($movimiento->tipo_movimiento) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $movimiento->usuario?->name ?? 'Sistema' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $origen?->service?->institution?->nombre }} / {{ $origen?->service?->nombre }} / {{ $origen?->nombre }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $destino?->service?->institution?->nombre }} / {{ $destino?->service?->nombre }} / {{ $destino?->nombre }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $movimiento->observacion ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay movimientos registrados para este equipo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
