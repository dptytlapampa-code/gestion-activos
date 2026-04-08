@extends('layouts.app')

@section('title', 'Detalle de acta')
@section('header', 'Detalle de acta '.$acta->codigo)

@section('content')
@php
    $isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA;
    $origenMultiple = (bool) data_get($acta->evento_payload, 'origen_multiple', false);
    $origenInstituciones = collect(data_get($acta->evento_payload, 'instituciones_origen_ids', []))->filter()->values();

    $isPrestamo = $acta->tipo === \App\Models\Acta::TIPO_PRESTAMO;
    $destinatarioPrestamo = [
        'nombre' => trim((string) ($acta->receptor_nombre ?? '')),
        'dni' => trim((string) ($acta->receptor_dni ?? '')),
        'cargo' => trim((string) ($acta->receptor_cargo ?? '')),
        'dependencia' => trim((string) ($acta->receptor_dependencia ?? '')),
    ];

    $hasDestinatarioPrestamo = $isPrestamo
        && collect($destinatarioPrestamo)->contains(fn (?string $value): bool => $value !== null && $value !== '');

    $destinoInstitucional = [
        'institucion' => $acta->institucionDestino?->nombre,
        'servicio' => $acta->servicioDestino?->nombre,
        'oficina' => $acta->oficinaDestino?->nombre,
    ];

    $destinoInstitucionalPartes = array_values(array_filter([
        $destinoInstitucional['institucion'],
        $destinoInstitucional['servicio'],
        $destinoInstitucional['oficina'],
    ], fn (?string $value): bool => $value !== null && trim($value) !== ''));

    $destinoInstitucionalTexto = $destinoInstitucionalPartes !== []
        ? implode(' / ', $destinoInstitucionalPartes)
        : '-';

    $hasDestinoInstitucional = $destinoInstitucionalPartes !== [];

    $destinoPrestamoResumen = trim(implode(' | ', array_values(array_filter([
        $destinatarioPrestamo['nombre'] !== '' ? $destinatarioPrestamo['nombre'] : null,
        $destinatarioPrestamo['dni'] !== '' ? 'DNI '.$destinatarioPrestamo['dni'] : null,
        $destinatarioPrestamo['cargo'] !== '' ? $destinatarioPrestamo['cargo'] : null,
        $destinatarioPrestamo['dependencia'] !== '' ? $destinatarioPrestamo['dependencia'] : null,
    ], fn (?string $item): bool => $item !== null && trim($item) !== ''))));
@endphp
<div class="space-y-6">
    @if ($isAnulada)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            ACTA ANULADA
        </div>
    @endif

    <x-collapsible-panel
        title="Resumen del acta"
        eyebrow="Documento"
        icon="file-text"
        summary="Datos administrativos, origen, destino y observaciones principales."
        :default-open="true"
        :persist-key="'acta-'.$acta->id.'.resumen'"
    >
        <div class="grid gap-4 md:grid-cols-2">
            <div><span class="text-xs text-slate-500">Codigo</span><p class="font-medium">{{ $acta->codigo }}</p></div>
            <div><span class="text-xs text-slate-500">Tipo</span><p class="font-medium">{{ $tipoLabels[$acta->tipo] ?? strtoupper($acta->tipo) }}</p></div>
            <div><span class="text-xs text-slate-500">Estado</span><p class="font-medium">{{ $isAnulada ? 'Anulada' : 'Activa' }}</p></div>
            <div><span class="text-xs text-slate-500">Fecha</span><p class="font-medium">{{ $acta->fecha?->format('d/m/Y') }}</p></div>
            <div><span class="text-xs text-slate-500">Generado por</span><p class="font-medium">{{ $acta->creator?->name }}</p></div>
            <div>
                <span class="text-xs text-slate-500">Institucion origen</span>
                <p class="font-medium">
                    @if ($origenMultiple)
                        Multiples instituciones ({{ $origenInstituciones->count() }})
                    @else
                        {{ $acta->institution?->nombre ?: '-' }}
                    @endif
                </p>
            </div>
            <div><span class="text-xs text-slate-500">Institucion destino</span><p class="font-medium">{{ $acta->institucionDestino?->nombre ?: ($isPrestamo ? 'No aplica (prestamo a persona)' : '-') }}</p></div>
            <div><span class="text-xs text-slate-500">Servicio origen</span><p class="font-medium">{{ $origenMultiple ? 'Multiples (ver detalle)' : ($acta->servicioOrigen?->nombre ?: '-') }}</p></div>
            <div><span class="text-xs text-slate-500">Oficina origen</span><p class="font-medium">{{ $origenMultiple ? 'Multiples (ver detalle)' : ($acta->oficinaOrigen?->nombre ?: '-') }}</p></div>
            <div><span class="text-xs text-slate-500">Servicio destino</span><p class="font-medium">{{ $acta->servicioDestino?->nombre ?: ($isPrestamo ? 'No aplica (prestamo a persona)' : '-') }}</p></div>
            <div><span class="text-xs text-slate-500">Oficina destino</span><p class="font-medium">{{ $acta->oficinaDestino?->nombre ?: ($isPrestamo ? 'No aplica (prestamo a persona)' : '-') }}</p></div>
            <div><span class="text-xs text-slate-500">Receptor</span><p class="font-medium">{{ $acta->receptor_nombre ?: '-' }}</p></div>
            <div><span class="text-xs text-slate-500">DNI / Cargo</span><p class="font-medium">{{ $acta->receptor_dni ?: '-' }} {{ $acta->receptor_cargo ? '| '.$acta->receptor_cargo : '' }}</p></div>
            <div><span class="text-xs text-slate-500">Dependencia receptor</span><p class="font-medium">{{ $acta->receptor_dependencia ?: '-' }}</p></div>
            <div><span class="text-xs text-slate-500">Motivo de baja</span><p class="font-medium">{{ $acta->motivo_baja ?: '-' }}</p></div>
            <div class="md:col-span-2"><span class="text-xs text-slate-500">Observaciones</span><p class="font-medium">{{ $acta->observaciones ?: '-' }}</p></div>

            @if ($isAnulada)
                <div><span class="text-xs text-slate-500">Anulada por</span><p class="font-medium">{{ $acta->annulledBy?->name ?: '-' }}</p></div>
                <div><span class="text-xs text-slate-500">Fecha anulacion</span><p class="font-medium">{{ $acta->anulada_at?->format('d/m/Y H:i') ?: '-' }}</p></div>
                <div class="md:col-span-2"><span class="text-xs text-slate-500">Motivo de anulacion</span><p class="font-medium">{{ $acta->motivo_anulacion ?: '-' }}</p></div>
            @endif
        </div>
    </x-collapsible-panel>

    @if ($isPrestamo)
        <x-collapsible-panel
            title="Destinatario del prestamo"
            eyebrow="Contexto secundario"
            icon="users"
            summary="Datos personales y referencia institucional complementaria."
            :default-open="false"
            :persist-key="'acta-'.$acta->id.'.prestamo'"
            class="border-blue-200 bg-blue-50"
            icon-class="text-blue-700"
            eyebrow-class="text-blue-700"
            title-class="text-blue-900"
            description-class="text-blue-800"
            summary-class="text-blue-800"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Nombre y apellido</span>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $destinatarioPrestamo['nombre'] !== '' ? $destinatarioPrestamo['nombre'] : '-' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">DNI</span>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $destinatarioPrestamo['dni'] !== '' ? $destinatarioPrestamo['dni'] : '-' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Cargo</span>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $destinatarioPrestamo['cargo'] !== '' ? $destinatarioPrestamo['cargo'] : '-' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Dependencia</span>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $destinatarioPrestamo['dependencia'] !== '' ? $destinatarioPrestamo['dependencia'] : '-' }}</p>
                </div>
            </div>

            @if ($hasDestinoInstitucional)
                <div class="app-subcard mt-4 border-blue-200 px-4 py-3 text-sm text-slate-700">
                    <p class="font-semibold text-blue-900">Destino institucional complementario</p>
                    <p class="mt-1">{{ $destinoInstitucionalTexto }}</p>
                </div>
            @endif
        </x-collapsible-panel>
    @endif

    <x-collapsible-panel
        title="Equipos del acta"
        eyebrow="Detalle principal"
        icon="layers"
        summary="Inventario, origen, destino y accesorios incluidos en el documento."
        :default-open="true"
        :persist-key="'acta-'.$acta->id.'.equipos'"
        class="overflow-hidden px-0 py-0"
        content-class="px-0 pb-0"
    >
        <div class="overflow-x-auto">
            <table class="app-table min-w-[78rem] text-sm">
                <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Codigo interno</th>
                    <th>Serie</th>
                    <th>Patrimonial</th>
                    <th>Origen snapshot</th>
                    <th>Destino</th>
                    <th>Cantidad</th>
                    <th>Accesorios</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($acta->equipos as $equipo)
                    @php
                        $origenEquipo = trim(implode(' / ', [
                            $equipo->pivot->institucion_origen_nombre ?: ($equipo->oficina?->service?->institution?->nombre ?? '-'),
                            $equipo->pivot->servicio_origen_nombre ?: ($equipo->oficina?->service?->nombre ?? '-'),
                            $equipo->pivot->oficina_origen_nombre ?: ($equipo->oficina?->nombre ?? '-'),
                        ]));

                        $destinoEquipo = $destinoInstitucionalTexto;

                        if ($isPrestamo && $hasDestinatarioPrestamo) {
                            $destinoEquipo = $destinoPrestamoResumen !== ''
                                ? $destinoPrestamoResumen
                                : 'Destinatario del prestamo no informado';

                            if ($hasDestinoInstitucional) {
                                $destinoEquipo .= ' (Ref. institucional: '.$destinoInstitucionalTexto.')';
                            }
                        }
                    @endphp
                    <tr>
                        <td class="app-cell-nowrap font-medium text-slate-900">{{ $equipo->tipo }}</td>
                        <td class="app-cell-nowrap">{{ $equipo->marca }}</td>
                        <td class="app-cell-nowrap">{{ $equipo->modelo }}</td>
                        <td class="app-cell-nowrap">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $equipo->codigo_interno }}</span>
                        </td>
                        <td class="app-cell-nowrap">{{ $equipo->numero_serie ?: '-' }}</td>
                        <td class="app-cell-nowrap">{{ $equipo->bien_patrimonial ?: '-' }}</td>
                        <td class="min-w-[16rem] app-cell-wrap">{{ $origenEquipo }}</td>
                        <td class="min-w-[18rem] app-cell-wrap">{{ $destinoEquipo }}</td>
                        <td class="app-cell-nowrap text-center">{{ $equipo->pivot->cantidad }}</td>
                        <td class="min-w-[14rem] app-cell-wrap">{{ $equipo->pivot->accesorios ?: '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-collapsible-panel>

    <x-collapsible-panel
        title="Historial generado"
        eyebrow="Trazabilidad"
        icon="clipboard-list"
        summary="Cambios de estado y ubicacion generados por el acta."
        :default-open="false"
        :persist-key="'acta-'.$acta->id.'.historial'"
        class="overflow-hidden px-0 py-0"
        content-class="px-0 pb-0"
    >
        <div class="overflow-x-auto">
            <table class="app-table min-w-[72rem] text-sm">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Estado anterior</th>
                        <th>Estado nuevo</th>
                        <th>Ubicacion anterior</th>
                        <th>Ubicacion nueva</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acta->historial as $item)
                        <tr>
                            <td class="min-w-[14rem] app-cell-wrap">{{ $item->equipo?->tipo }} ({{ $item->equipo?->codigo_interno ?: ($item->equipo?->numero_serie ?: '-') }})</td>
                            <td class="app-cell-nowrap">{{ strtoupper(str_replace('_', ' ', $item->estado_anterior ?: '-')) }}</td>
                            <td class="app-cell-nowrap">{{ strtoupper(str_replace('_', ' ', $item->estado_nuevo ?: '-')) }}</td>
                            <td class="min-w-[16rem] app-cell-wrap">{{ $item->institucionAnterior?->nombre ?: '-' }} / {{ $item->servicioAnterior?->nombre ?: '-' }} / {{ $item->oficinaAnterior?->nombre ?: '-' }}</td>
                            <td class="min-w-[16rem] app-cell-wrap">{{ $item->institucionNueva?->nombre ?: '-' }} / {{ $item->servicioNuevo?->nombre ?: '-' }} / {{ $item->oficinaNueva?->nombre ?: '-' }}</td>
                            <td class="app-cell-nowrap">{{ $item->usuario?->name ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-slate-500">Sin historial asociado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-collapsible-panel>

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-end">
        @can('anular', $acta)
            @if (! $isAnulada)
                <form method="POST" action="{{ route('actas.anular', $acta) }}" class="flex flex-col gap-2 md:w-[420px]" onsubmit="return confirm('Esta accion no puede deshacerse. Desea anular el acta?');">
                    @csrf
                    <label for="motivo_anulacion" class="text-xs font-medium text-slate-600">Motivo de anulacion</label>
                    <textarea id="motivo_anulacion" name="motivo_anulacion" rows="2" required class="app-input text-sm" placeholder="Detalle el motivo administrativo"></textarea>
                    <button type="submit" class="btn w-full bg-red-600 text-white hover:bg-red-700">Anular acta</button>
                </form>
            @endif
        @endcan

        <a href="{{ route('actas.download', $acta) }}" class="btn btn-primary w-full md:w-auto">Descargar PDF</a>
    </div>
</div>
@endsection
