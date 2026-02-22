@extends('layouts.app')

@section('title', 'Movimiento / Transferir')
@section('header', 'Movimiento / Transferir')

@section('content')
<div class="mx-auto max-w-4xl rounded-xl border border-slate-200 bg-white p-6" x-data="{
    tipo_movimiento: @js(old('tipo_movimiento', '')),
    institucion_destino_id: @js((string) old('institucion_destino_id', '')),
    servicio_destino_id: @js((string) old('servicio_destino_id', '')),
    oficina_destino_id: @js((string) old('oficina_destino_id', '')),
    servicios: @js($servicios->map(fn ($servicio) => ['id' => (string) $servicio->id, 'nombre' => $servicio->nombre, 'institution_id' => (string) $servicio->institution_id])->values()),
    oficinas: @js($oficinas->map(fn ($oficina) => ['id' => (string) $oficina->id, 'nombre' => $oficina->nombre, 'service_id' => (string) $oficina->service_id])->values()),
    current_institution_id: @js((string) $current_institution_id),
    get filteredServicios() {
        if (!this.institucion_destino_id) return [];
        return this.servicios.filter((servicio) => servicio.institution_id === this.institucion_destino_id);
    },
    get filteredOficinas() {
        if (!this.servicio_destino_id) return [];
        return this.oficinas.filter((oficina) => oficina.service_id === this.servicio_destino_id);
    },
    onTipoChange() {
        this.servicio_destino_id = '';
        this.oficina_destino_id = '';
        if (this.tipo_movimiento === 'transferencia_interna') {
            this.institucion_destino_id = this.current_institution_id;
        }
    },
    onInstitutionChange() {
        this.servicio_destino_id = '';
        this.oficina_destino_id = '';
    },
    onServiceChange() {
        this.oficina_destino_id = '';
    },
}">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900">Nuevo movimiento</h3>
        <p class="mt-1 text-sm text-slate-500">Equipo: {{ $equipo->tipo }} - {{ $equipo->marca }} {{ $equipo->modelo }} (Serie: {{ $equipo->numero_serie }})</p>
    </div>

    <form method="POST" action="{{ route('equipos.movimientos.store', $equipo) }}" class="space-y-5">
        @csrf

        <div>
            <label for="tipo_movimiento" class="mb-1 block text-sm font-medium text-slate-700">Tipo de movimiento</label>
            <select id="tipo_movimiento" name="tipo_movimiento" required x-model="tipo_movimiento" @change="onTipoChange" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Seleccione una opción</option>
                <option value="transferencia_interna">Transferencia interna</option>
                <option value="transferencia_externa">Transferencia externa</option>
                <option value="prestamo">Préstamo</option>
                <option value="devolucion">Devolución</option>
                <option value="mantenimiento">Mantenimiento</option>
                <option value="baja">Baja</option>
            </select>
            @error('tipo_movimiento')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div x-show="tipo_movimiento === 'transferencia_interna' || tipo_movimiento === 'transferencia_externa'" x-cloak class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="institucion_destino_id" class="mb-1 block text-sm font-medium text-slate-700">Institución destino</label>
                <select id="institucion_destino_id" name="institucion_destino_id" :required="tipo_movimiento === 'transferencia_interna' || tipo_movimiento === 'transferencia_externa'" x-model="institucion_destino_id" @change="onInstitutionChange" :disabled="tipo_movimiento === 'transferencia_interna'" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seleccionar...</option>
                    @foreach ($instituciones as $institucion)
                        <option value="{{ $institucion->id }}">{{ $institucion->nombre }}</option>
                    @endforeach
                </select>
                @error('institucion_destino_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="servicio_destino_id" class="mb-1 block text-sm font-medium text-slate-700">Servicio destino</label>
                <select id="servicio_destino_id" name="servicio_destino_id" :required="tipo_movimiento === 'transferencia_interna' || tipo_movimiento === 'transferencia_externa'" x-model="servicio_destino_id" @change="onServiceChange" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seleccionar...</option>
                    <template x-for="servicio in filteredServicios" :key="servicio.id"><option :value="servicio.id" x-text="servicio.nombre"></option></template>
                </select>
                @error('servicio_destino_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="oficina_destino_id" class="mb-1 block text-sm font-medium text-slate-700">Oficina destino</label>
                <select id="oficina_destino_id" name="oficina_destino_id" :required="tipo_movimiento === 'transferencia_interna' || tipo_movimiento === 'transferencia_externa'" x-model="oficina_destino_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seleccionar...</option>
                    <template x-for="oficina in filteredOficinas" :key="oficina.id"><option :value="oficina.id" x-text="oficina.nombre"></option></template>
                </select>
                @error('oficina_destino_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div x-show="tipo_movimiento === 'prestamo'" x-cloak class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm">Receptor nombre</label><input type="text" name="receptor_nombre" value="{{ old('receptor_nombre') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">DNI</label><input type="text" name="receptor_dni" value="{{ old('receptor_dni') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Cargo</label><input type="text" name="receptor_cargo" value="{{ old('receptor_cargo') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Fecha inicio</label><input type="date" name="fecha_inicio_prestamo" value="{{ old('fecha_inicio_prestamo') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Fecha estimada devolución</label><input type="date" name="fecha_estimada_devolucion" value="{{ old('fecha_estimada_devolucion') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            @foreach (['receptor_nombre','receptor_dni','receptor_cargo','fecha_inicio_prestamo','fecha_estimada_devolucion'] as $field)
                @error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            @endforeach
        </div>

        <div>
            <label for="observacion" class="mb-1 block text-sm font-medium text-slate-700">Observación</label>
            <textarea id="observacion" name="observacion" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('observacion') }}</textarea>
            @error('observacion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-3">
            <a href="{{ route('equipos.show', $equipo) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancelar</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Guardar movimiento</button>
        </div>
    </form>
</div>
@endsection
