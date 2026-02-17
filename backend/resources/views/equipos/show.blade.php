@extends('layouts.app')

@section('title', 'Detalle equipo')
@section('header', 'Detalle equipo')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Información del equipo</h3>
            <a href="{{ route('equipos.edit', $equipo) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                Editar
            </a>
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

    @can('update', $equipo)
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Registrar movimiento</h3>

            <form
                method="POST"
                action="{{ route('equipos.movimientos.store', $equipo) }}"
                class="space-y-4"
                x-data="{
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
                }"
            >
                @csrf

                <div>
                    <label for="tipo_movimiento" class="mb-1 block text-sm font-medium text-slate-700">Tipo de movimiento</label>
                    <select id="tipo_movimiento" name="tipo_movimiento" required x-model="tipo_movimiento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">Seleccionar...</option>
                        <option value="traslado" @selected(old('tipo_movimiento') === 'traslado')>Traslado</option>
                        <option value="mantenimiento" @selected(old('tipo_movimiento') === 'mantenimiento')>Mantenimiento</option>
                        <option value="prestamo" @selected(old('tipo_movimiento') === 'prestamo')>Prestamo</option>
                        <option value="baja" @selected(old('tipo_movimiento') === 'baja')>Baja</option>
                    </select>
                    @error('tipo_movimiento')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
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
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
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
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
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
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="observacion" class="mb-1 block text-sm font-medium text-slate-700">Observación</label>
                    <textarea id="observacion" name="observacion" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('observacion') }}</textarea>
                    @error('observacion')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Guardar
                </button>
            </form>
        </div>
    @endcan

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
                            $origenTexto = $origen
                                ? $origen->service?->institution?->nombre.' / '.$origen->service?->nombre.' / '.$origen->nombre
                                : '-';
                            $destinoTexto = $destino
                                ? $destino->service?->institution?->nombre.' / '.$destino->service?->nombre.' / '.$destino->nombre
                                : '-';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ $movimiento->fecha?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ ucfirst($movimiento->tipo_movimiento) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $movimiento->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $origenTexto }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $destinoTexto }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $movimiento->observacion ?? '-' }}</td>
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
