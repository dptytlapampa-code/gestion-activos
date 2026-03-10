@extends('layouts.app')

@section('title', 'Detalle de acta')
@section('header', 'Detalle de acta '.$acta->codigo)

@section('content')
@php($isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA)
<div class="space-y-6">
    @if ($isAnulada)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            ACTA ANULADA
        </div>
    @endif

    <div class="card grid gap-4 md:grid-cols-2">
        <div><span class="text-xs text-slate-500">Codigo</span><p class="font-medium">{{ $acta->codigo }}</p></div>
        <div><span class="text-xs text-slate-500">Tipo</span><p class="font-medium">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</p></div>
        <div><span class="text-xs text-slate-500">Estado</span><p class="font-medium">{{ $isAnulada ? 'Anulada' : 'Activa' }}</p></div>
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

        @if ($isAnulada)
            <div><span class="text-xs text-slate-500">Anulada por</span><p class="font-medium">{{ $acta->annulledBy?->name ?: '-' }}</p></div>
            <div><span class="text-xs text-slate-500">Fecha anulacion</span><p class="font-medium">{{ $acta->anulada_at?->format('d/m/Y H:i') ?: '-' }}</p></div>
            <div class="md:col-span-2"><span class="text-xs text-slate-500">Motivo de anulacion</span><p class="font-medium">{{ $acta->motivo_anulacion ?: '-' }}</p></div>
        @endif
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

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-end">
        @can('anular', $acta)
            @if (! $isAnulada)
                <form method="POST" action="{{ route('actas.anular', $acta) }}" class="flex flex-col gap-2 md:w-[420px]" onsubmit="return confirm('Esta acci\u00f3n no puede deshacerse. \u00bfDesea anular el acta?');">
                    @csrf
                    <label for="motivo_anulacion" class="text-xs font-medium text-slate-600">Motivo de anulacion</label>
                    <textarea id="motivo_anulacion" name="motivo_anulacion" rows="2" required class="rounded-xl border-slate-300 text-sm" placeholder="Detalle el motivo administrativo"></textarea>
                    <button type="submit" class="min-h-[48px] rounded-xl bg-red-600 px-5 py-3 font-semibold text-white">Anular acta</button>
                </form>
            @endif
        @endcan

        <a href="{{ route('actas.download', $acta) }}" class="min-h-[48px] rounded-xl bg-primary-600 px-5 py-3 font-semibold text-white text-center">Descargar PDF</a>
    </div>
</div>
@endsection
