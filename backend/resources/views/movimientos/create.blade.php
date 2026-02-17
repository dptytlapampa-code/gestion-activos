@extends('layouts.app')

@section('title', 'Registrar movimiento')
@section('header', 'Registrar movimiento')

@section('content')
<div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900">Nuevo movimiento manual</h3>
        <p class="mt-1 text-sm text-slate-500">Equipo: {{ $equipo->tipo }} - {{ $equipo->marca }} {{ $equipo->modelo }} (Serie: {{ $equipo->numero_serie }})</p>
    </div>

    <form method="POST" action="{{ route('equipos.movimientos.store', $equipo) }}" class="space-y-5" x-data="{
        tipo_movimiento: @js(old('tipo_movimiento', '')),
        institucion_destino_id: @js((string) old('institucion_destino_id', '')),
        servicio_destino_id: @js((string) old('servicio_destino_id', '')),
        oficina_destino_id: @js((string) old('oficina_destino_id', '')),
        servicios: @js($servicios->map(fn ($servicio) => ['id' => (string) $servicio->id, 'nombre' => $servicio->nombre, 'institution_id' => (string) $servicio->institution_id])->values()),
        oficinas: @js($oficinas->map(fn ($oficina) => ['id' => (string) $oficina->id, 'nombre' => $oficina->nombre, 'service_id' => (string) $oficina->service_id])->values()),
        get filteredServicios() {
            if (!this.institucion_destino_id) return [];
            return this.servicios.filter((servicio) => servicio.institution_id === this.institucion_destino_id);
        },
        get filteredOficinas() {
            if (!this.servicio_destino_id) return [];
            return this.oficinas.filter((oficina) => oficina.service_id === this.servicio_destino_id);
        },
        onInstitutionChange() {
            this.servicio_destino_id = '';
            this.oficina_destino_id = '';
        },
        onServiceChange() {
            this.oficina_destino_id = '';
        },
    }">
        @csrf

        <div>
            <label for="tipo_movimiento" class="mb-1 block text-sm font-medium text-slate-700">Tipo de movimiento</label>
            <select id="tipo_movimiento" name="tipo_movimiento" required x-model="tipo_movimiento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Seleccione una opción</option>
                @foreach ($tipos_movimiento as $tipo_movimiento)
                    <option value="{{ $tipo_movimiento }}" @selected(old('tipo_movimiento') === $tipo_movimiento)>
                        {{ ucfirst($tipo_movimiento) }}
                    </option>
                @endforeach
            </select>
            @error('tipo_movimiento')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="tipo_movimiento === 'traslado'" x-cloak class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="institucion_destino_id" class="mb-1 block text-sm font-medium text-slate-700">Institución destino</label>
                <select id="institucion_destino_id" name="institucion_destino_id" :required="tipo_movimiento === 'traslado'" x-model="institucion_destino_id" @change="onInstitutionChange" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="">Seleccionar...</option>
                    @foreach ($instituciones as $institucion)
                        <option value="{{ $institucion->id }}" @selected((string) old('institucion_destino_id') === (string) $institucion->id)>{{ $institucion->nombre }}</option>
                    @endforeach
                </select>
                @error('institucion_destino_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="servicio_destino_id" class="mb-1 block text-sm font-medium text-slate-700">Servicio destino</label>
                <select id="servicio_destino_id" name="servicio_destino_id" :required="tipo_movimiento === 'traslado'" x-model="servicio_destino_id" @change="onServiceChange" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="">Seleccionar...</option>
                    <template x-for="servicio in filteredServicios" :key="servicio.id">
                        <option :value="servicio.id" x-text="servicio.nombre"></option>
                    </template>
                </select>
                @error('servicio_destino_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="oficina_destino_id" class="mb-1 block text-sm font-medium text-slate-700">Oficina destino</label>
                <select id="oficina_destino_id" name="oficina_destino_id" :required="tipo_movimiento === 'traslado'" x-model="oficina_destino_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="">Seleccionar...</option>
                    <template x-for="oficina in filteredOficinas" :key="oficina.id">
                        <option :value="oficina.id" x-text="oficina.nombre"></option>
                    </template>
                </select>
                @error('oficina_destino_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="observacion" class="mb-1 block text-sm font-medium text-slate-700">Observación</label>
            <textarea id="observacion" name="observacion" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('observacion') }}</textarea>
            @error('observacion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('equipos.show', $equipo) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">Guardar movimiento</button>
        </div>
    </form>
</div>
@endsection
