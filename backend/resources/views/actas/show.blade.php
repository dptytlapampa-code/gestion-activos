@extends('layouts.app')

@section('title', 'Detalle de acta')
@section('header', 'Detalle de acta '.$acta->codigo)

@section('content')
<div class="space-y-6">
    <div class="card grid gap-4 md:grid-cols-2">
        <div><span class="text-xs text-slate-500">Codigo</span><p class="font-medium">{{ $acta->codigo }}</p></div>
        <div><span class="text-xs text-slate-500">Tipo</span><p class="font-medium">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</p></div>
        <div><span class="text-xs text-slate-500">Fecha</span><p class="font-medium">{{ $acta->fecha?->format('d/m/Y') }}</p></div>
        <div><span class="text-xs text-slate-500">Generado por</span><p class="font-medium">{{ $acta->creator?->name }}</p></div>
        <div><span class="text-xs text-slate-500">Institucion origen</span><p class="font-medium">{{ $acta->institution?->nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">Institucion destino</span><p class="font-medium">{{ $acta->institucionDestino?->nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">Servicio origen</span><p class="font-medium">{{ $acta->servicioOrigen?->nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">Oficina origen</span><p class="font-medium">{{ $acta->oficinaOrigen?->nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">Servicio destino</span><p class="font-medium">{{ $acta->servicioDestino?->nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">Oficina destino</span><p class="font-medium">{{ $acta->oficinaDestino?->nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">Receptor</span><p class="font-medium">{{ $acta->receptor_nombre ?: '-' }}</p></div>
        <div><span class="text-xs text-slate-500">DNI / Cargo</span><p class="font-medium">{{ $acta->receptor_dni ?: '-' }} {{ $acta->receptor_cargo ? '| '.$acta->receptor_cargo : '' }}</p></div>
        <div><span class="text-xs text-slate-500">Motivo de baja</span><p class="font-medium">{{ $acta->motivo_baja ?: '-' }}</p></div>
        <div class="md:col-span-2"><span class="text-xs text-slate-500">Observaciones</span><p class="font-medium">{{ $acta->observaciones ?: '-' }}</p></div>
    </div>

    <div class="card overflow-x-auto">
        <h3 class="mb-3 text-base font-semibold text-slate-900">Equipos del acta</h3>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
            <tr class="text-left text-slate-600">
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3">Marca</th>
                <th class="px-4 py-3">Modelo</th>
                <th class="px-4 py-3">Serie</th>
                <th class="px-4 py-3">Patrimonial</th>
                <th class="px-4 py-3">Cantidad</th>
                <th class="px-4 py-3">Accesorios</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @foreach ($acta->equipos as $equipo)
                <tr>
                    <td class="px-4 py-3">{{ $equipo->tipo }}</td>
                    <td class="px-4 py-3">{{ $equipo->marca }}</td>
                    <td class="px-4 py-3">{{ $equipo->modelo }}</td>
                    <td class="px-4 py-3">{{ $equipo->numero_serie }}</td>
                    <td class="px-4 py-3">{{ $equipo->bien_patrimonial }}</td>
                    <td class="px-4 py-3">{{ $equipo->pivot->cantidad }}</td>
                    <td class="px-4 py-3">{{ $equipo->pivot->accesorios ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card overflow-x-auto">
        <h3 class="mb-3 text-base font-semibold text-slate-900">Historial generado</h3>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-4 py-3">Equipo</th>
                    <th class="px-4 py-3">Estado anterior</th>
                    <th class="px-4 py-3">Estado nuevo</th>
                    <th class="px-4 py-3">Ubicacion anterior</th>
                    <th class="px-4 py-3">Ubicacion nueva</th>
                    <th class="px-4 py-3">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($acta->historial as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->equipo?->tipo }} ({{ $item->equipo?->numero_serie }})</td>
                        <td class="px-4 py-3">{{ strtoupper(str_replace('_', ' ', $item->estado_anterior ?: '-')) }}</td>
                        <td class="px-4 py-3">{{ strtoupper(str_replace('_', ' ', $item->estado_nuevo ?: '-')) }}</td>
                        <td class="px-4 py-3">{{ $item->institucionAnterior?->nombre ?: '-' }} / {{ $item->servicioAnterior?->nombre ?: '-' }} / {{ $item->oficinaAnterior?->nombre ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $item->institucionNueva?->nombre ?: '-' }} / {{ $item->servicioNuevo?->nombre ?: '-' }} / {{ $item->oficinaNueva?->nombre ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $item->usuario?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-4 text-center text-slate-500">Sin historial asociado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">
        <a href="{{ route('actas.download', $acta) }}" class="min-h-[48px] rounded-xl bg-primary-600 px-5 py-3 font-semibold text-white">Descargar PDF</a>
    </div>
</div>
@endsection
