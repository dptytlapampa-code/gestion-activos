@extends('layouts.app')

@section('title', 'Nueva acta de trazabilidad')
@section('header', 'Actas de trazabilidad')

@section('content')
<div
    x-data="actaWizard(@js([
        'tipo' => old('tipo'),
        'fecha' => old('fecha', now()->toDateString()),
        'institution_destino_id' => old('institution_destino_id'),
        'service_destino_id' => old('service_destino_id'),
        'office_destino_id' => old('office_destino_id'),
        'receptor_nombre' => old('receptor_nombre'),
        'receptor_dni' => old('receptor_dni'),
        'receptor_cargo' => old('receptor_cargo'),
        'receptor_dependencia' => old('receptor_dependencia'),
        'motivo_baja' => old('motivo_baja'),
        'observaciones' => old('observaciones'),
    ]), @js($oldSelectedEquipos), @js($tipoLabels), @js($institutions), @js($isSuperadmin), @js($userInstitutionId))"
    class="space-y-6"
>
    <form method="POST" action="{{ route('actas.store') }}" class="space-y-6">
        @csrf

        <section class="card space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Paso 1 - Tipo de acta</h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Trazabilidad</span>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                @foreach ($tipos as $tipo)
                    <button
                        type="button"
                        @click="selectTipo('{{ $tipo }}')"
                        :class="tipo === '{{ $tipo }}' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-700'"
                        class="min-h-[64px] rounded-2xl border px-4 py-3 text-left text-sm font-semibold uppercase tracking-wide transition hover:border-primary-300"
                    >
                        {{ $tipoLabels[$tipo] ?? strtoupper($tipo) }}
                    </button>
                @endforeach
            </div>

            <input type="hidden" name="tipo" :value="tipo">
            @error('tipo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </section>

        <section class="card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-slate-900">Paso 2 - Seleccion de equipos</h3>
                <button type="button" class="min-h-[48px] rounded-xl border border-dashed border-slate-300 px-4 text-sm font-semibold text-slate-500" disabled>
                    ESCANEAR QR (proximamente)
                </button>
            </div>

            <div class="grid gap-2 md:grid-cols-[1fr_auto]">
                <input
                    type="text"
                    x-model="query"
                    class="min-h-[48px] rounded-xl border-slate-300"
                    placeholder="Buscar por serie, patrimonial, modelo, mac o codigo interno. Use ... para listar todos"
                >
                <button type="button" @click="buscarEquipos" class="min-h-[48px] rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white">
                    Buscar
                </button>
            </div>

            <template x-if="errorBusqueda">
                <p class="text-sm text-red-600" x-text="errorBusqueda"></p>
            </template>

            <div class="grid gap-3 md:grid-cols-2" x-show="results.length" x-cloak>
                <template x-for="item in results" :key="item.id">
                    <article class="rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-base font-semibold text-slate-900" x-text="item.label"></p>
                        <p class="mt-1 text-sm text-slate-600">Serie: <span x-text="item.numero_serie || '-'" class="font-medium"></span></p>
                        <p class="text-sm text-slate-600">Patrimonial: <span x-text="item.bien_patrimonial || '-'" class="font-medium"></span></p>
                        <p class="text-sm text-slate-600">Institucion actual: <span x-text="item.institucion || '-'" class="font-medium"></span></p>
                        <p class="text-sm text-slate-600">Servicio actual: <span x-text="item.servicio || '-'" class="font-medium"></span></p>
                        <p class="text-sm text-slate-600">Oficina actual: <span x-text="item.oficina || '-'" class="font-medium"></span></p>
                        <button type="button" @click="agregar(item)" class="mt-3 min-h-[48px] w-full rounded-xl border border-primary-500 bg-primary-50 text-sm font-semibold text-primary-700">
                            AGREGAR
                        </button>
                    </article>
                </template>
            </div>
        </section>

        <section class="card space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Paso 3 - Equipos seleccionados y origen</h3>

            <template x-if="!selected.length">
                <p class="text-sm text-slate-500">Todavia no agrego equipos al acta.</p>
            </template>

            <div class="space-y-3" x-show="selected.length" x-cloak>
                <template x-for="(item, index) in selected" :key="`sel-${item.id}`">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-slate-900" x-text="item.label"></p>
                                <p class="text-sm text-slate-500">Serie: <span x-text="item.numero_serie || '-'" class="font-medium"></span> | Patrimonial: <span x-text="item.bien_patrimonial || '-'" class="font-medium"></span></p>
                            </div>
                            <button type="button" @click="remove(index)" class="min-h-[48px] rounded-xl border border-red-200 px-3 text-sm font-semibold text-red-600">
                                Quitar
                            </button>
                        </div>

                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                            <p class="font-semibold text-slate-900">Origen</p>
                            <p class="mt-1" x-text="item.institucion || '-'"></p>
                            <p x-text="item.servicio || '-'"></p>
                            <p x-text="item.oficina || '-'"></p>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-600">Cantidad</label>
                                <input type="number" min="1" x-model="item.cantidad" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600">Accesorios</label>
                                <input type="text" x-model="item.accesorios" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            @error('equipos') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('equipos.*.equipo_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('equipos.*.cantidad') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <template x-for="(item, index) in selected" :key="`hidden-${item.id}`">
                <div>
                    <input type="hidden" :name="`equipos[${index}][equipo_id]`" :value="item.id">
                    <input type="hidden" :name="`equipos[${index}][cantidad]`" :value="item.cantidad || 1">
                    <input type="hidden" :name="`equipos[${index}][accesorios]`" :value="item.accesorios || ''">
                </div>
            </template>
        </section>

        <section class="card space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Paso 4 - Datos del acta</h3>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700" x-text="tipo === 'prestamo' ? 'Fecha de prestamo' : 'Fecha'"></label>
                    <input type="date" name="fecha" x-model="fecha" class="mt-1 w-full rounded-xl border-slate-300" required>
                    @error('fecha') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div x-show="tipo === 'entrega'" x-cloak class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Institucion destino</label>
                    <select name="institution_destino_id" x-model="institution_destino_id" @change="onEntregaInstitutionChange" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">Seleccionar</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->nombre }}</option>
                        @endforeach
                    </select>
                    @error('institution_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Servicio destino</label>
                    <select name="service_destino_id" x-model="service_destino_id" @change="onDestinoServiceChange('destino')" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">Seleccionar</option>
                        <template x-for="service in serviceOptions.destino" :key="`sd-${service.id}`">
                            <option :value="service.id" x-text="service.label"></option>
                        </template>
                    </select>
                    @error('service_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Oficina destino</label>
                    <select name="office_destino_id" x-model="office_destino_id" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">Seleccionar</option>
                        <template x-for="office in officeOptions.destino" :key="`od-${office.id}`">
                            <option :value="office.id" x-text="office.label"></option>
                        </template>
                    </select>
                    @error('office_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div x-show="tipo === 'traslado'" x-cloak class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p class="font-semibold text-slate-900">Institucion origen detectada</p>
                    <p class="mt-1" x-text="selected.length ? (getSelectedInstitutionName() || 'Sin institucion') : 'Agregue equipos para detectar la institucion.'"></p>
                    <p class="mt-1 text-xs text-slate-500">Regla: el traslado no permite cambiar de institucion.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Servicio destino</label>
                        <select name="service_destino_id" x-model="service_destino_id" @change="onDestinoServiceChange('traslado')" class="mt-1 w-full rounded-xl border-slate-300">
                            <option value="">Seleccionar</option>
                            <template x-for="service in serviceOptions.traslado" :key="`st-${service.id}`">
                                <option :value="service.id" x-text="service.label"></option>
                            </template>
                        </select>
                        @error('service_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Oficina destino</label>
                        <select name="office_destino_id" x-model="office_destino_id" class="mt-1 w-full rounded-xl border-slate-300">
                            <option value="">Seleccionar</option>
                            <template x-for="office in officeOptions.traslado" :key="`ot-${office.id}`">
                                <option :value="office.id" x-text="office.label"></option>
                            </template>
                        </select>
                        @error('office_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div x-show="['entrega','prestamo'].includes(tipo)" x-cloak class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Receptor</label>
                    <input type="text" name="receptor_nombre" x-model="receptor_nombre" class="mt-1 w-full rounded-xl border-slate-300">
                    @error('receptor_nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">DNI</label>
                    <input type="text" name="receptor_dni" x-model="receptor_dni" class="mt-1 w-full rounded-xl border-slate-300">
                    @error('receptor_dni') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Cargo</label>
                    <input type="text" name="receptor_cargo" x-model="receptor_cargo" class="mt-1 w-full rounded-xl border-slate-300">
                    @error('receptor_cargo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div x-show="tipo === 'baja'" x-cloak>
                <label class="block text-sm font-medium text-slate-700">Motivo de baja</label>
                <input type="text" name="motivo_baja" x-model="motivo_baja" class="mt-1 w-full rounded-xl border-slate-300">
                @error('motivo_baja') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Observaciones</label>
                <textarea name="observaciones" x-model="observaciones" rows="3" class="mt-1 w-full rounded-xl border-slate-300"></textarea>
                @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </section>

        <section class="card">
            <h3 class="text-lg font-semibold text-slate-900">Paso 5 - Generar</h3>
            <button type="submit" class="mt-4 min-h-[56px] w-full rounded-2xl bg-primary-600 text-base font-bold uppercase tracking-wide text-white">
                Generar acta y PDF
            </button>
        </section>
    </form>
</div>

<script>
    function actaWizard(initial, oldSelected, tipoLabels, institutions, isSuperadmin, userInstitutionId) {
        return {
            tipo: initial.tipo || '',
            fecha: initial.fecha,
            institution_destino_id: initial.institution_destino_id ? String(initial.institution_destino_id) : '',
            service_destino_id: initial.service_destino_id ? String(initial.service_destino_id) : '',
            office_destino_id: initial.office_destino_id ? String(initial.office_destino_id) : '',
            receptor_nombre: initial.receptor_nombre || '',
            receptor_dni: initial.receptor_dni || '',
            receptor_cargo: initial.receptor_cargo || '',
            receptor_dependencia: initial.receptor_dependencia || '',
            motivo_baja: initial.motivo_baja || '',
            observaciones: initial.observaciones || '',
            query: '',
            results: [],
            selected: Array.isArray(oldSelected)
                ? oldSelected.map((item) => ({
                    ...item,
                    institucion_id: item.institucion_id ? Number(item.institucion_id) : null,
                    servicio_id: item.servicio_id ? Number(item.servicio_id) : null,
                    oficina_id: item.oficina_id ? Number(item.oficina_id) : null,
                    cantidad: item.cantidad || 1,
                    accesorios: item.accesorios || '',
                }))
                : [],
            errorBusqueda: '',
            serviceOptions: { destino: [], traslado: [] },
            officeOptions: { destino: [], traslado: [] },
            currentTrasladoInstitutionId: '',
            isSuperadmin,
            userInstitutionId,

            init() {
                if (this.tipo === 'entrega' && this.institution_destino_id) {
                    this.loadServices('destino', this.institution_destino_id, false);
                }

                if (this.tipo === 'entrega' && this.institution_destino_id && this.service_destino_id) {
                    this.loadOffices('destino', this.institution_destino_id, this.service_destino_id);
                }

                this.refreshTrasladoContext(false);
            },

            selectTipo(tipo) {
                this.tipo = tipo;
                this.errorBusqueda = '';

                if (tipo !== 'entrega') {
                    this.institution_destino_id = '';
                    this.serviceOptions.destino = [];
                    this.officeOptions.destino = [];
                    this.service_destino_id = '';
                    this.office_destino_id = '';
                }

                if (tipo === 'traslado') {
                    this.refreshTrasladoContext(true);
                }
            },

            getSelectedInstitutionId() {
                const ids = [...new Set(this.selected.map((item) => Number(item.institucion_id || 0)).filter((id) => id > 0))];
                if (ids.length !== 1) {
                    return '';
                }

                return String(ids[0]);
            },

            getSelectedInstitutionName() {
                if (!this.selected.length) {
                    return '';
                }

                return this.selected[0].institucion || '';
            },

            async onEntregaInstitutionChange() {
                this.service_destino_id = '';
                this.office_destino_id = '';
                this.serviceOptions.destino = [];
                this.officeOptions.destino = [];

                if (!this.institution_destino_id) {
                    return;
                }

                await this.loadServices('destino', this.institution_destino_id, true);
            },

            async onDestinoServiceChange(scope) {
                this.office_destino_id = '';
                this.officeOptions[scope] = [];

                if (!this.service_destino_id) {
                    return;
                }

                let institutionId = '';
                if (scope === 'destino') {
                    institutionId = this.institution_destino_id;
                }

                if (scope === 'traslado') {
                    institutionId = this.getSelectedInstitutionId();
                }

                if (!institutionId) {
                    return;
                }

                await this.loadOffices(scope, institutionId, this.service_destino_id);
            },

            async refreshTrasladoContext(resetSelection) {
                if (this.tipo !== 'traslado') {
                    return;
                }

                const institutionId = this.getSelectedInstitutionId();

                if (!institutionId) {
                    this.currentTrasladoInstitutionId = '';
                    this.serviceOptions.traslado = [];
                    this.officeOptions.traslado = [];
                    if (resetSelection) {
                        this.service_destino_id = '';
                        this.office_destino_id = '';
                    }
                    return;
                }

                if (this.currentTrasladoInstitutionId !== institutionId) {
                    this.currentTrasladoInstitutionId = institutionId;
                    this.serviceOptions.traslado = [];
                    this.officeOptions.traslado = [];
                    if (resetSelection) {
                        this.service_destino_id = '';
                        this.office_destino_id = '';
                    }
                    await this.loadServices('traslado', institutionId, true);
                    if (resetSelection) {
                        return;
                    }
                }

                if (this.service_destino_id) {
                    await this.loadOffices('traslado', institutionId, this.service_destino_id);
                }
            },

            async loadServices(scope, institutionId, clearOnError) {
                if (!institutionId) {
                    return;
                }

                try {
                    const params = new URLSearchParams({ q: '...', institution_id: institutionId, acta_context: '1' });
                    const response = await fetch(`/api/search/services?${params.toString()}`);
                    const payload = await response.json();
                    this.serviceOptions[scope] = Array.isArray(payload) ? payload : [];
                } catch (e) {
                    this.serviceOptions[scope] = [];
                    if (clearOnError) {
                        this.errorBusqueda = 'No fue posible cargar servicios para la institucion seleccionada.';
                    }
                }
            },

            async loadOffices(scope, institutionId, serviceId) {
                if (!institutionId || !serviceId) {
                    return;
                }

                try {
                    const params = new URLSearchParams({ q: '...', institution_id: institutionId, service_id: serviceId, acta_context: '1' });
                    const response = await fetch(`/api/search/offices?${params.toString()}`);
                    const payload = await response.json();
                    this.officeOptions[scope] = Array.isArray(payload) ? payload : [];
                } catch (e) {
                    this.officeOptions[scope] = [];
                    this.errorBusqueda = 'No fue posible cargar oficinas para el servicio seleccionado.';
                }
            },

            async buscarEquipos() {
                this.errorBusqueda = '';

                const q = this.query.trim();
                if (q.length < 1) {
                    this.errorBusqueda = 'Ingrese un termino de busqueda o use ... para listar equipos.';
                    return;
                }

                try {
                    const params = new URLSearchParams({ q, acta_context: '1' });
                    const selectedInstitutionId = this.getSelectedInstitutionId();
                    if (selectedInstitutionId) {
                        params.set('institution_id', selectedInstitutionId);
                    }

                    const response = await fetch(`/api/search/equipos?${params.toString()}`);
                    const payload = await response.json();
                    this.results = Array.isArray(payload) ? payload : [];

                    if (!this.results.length) {
                        this.errorBusqueda = 'No se encontraron equipos para los filtros indicados.';
                    }
                } catch (e) {
                    this.results = [];
                    this.errorBusqueda = 'No fue posible buscar equipos en este momento.';
                }
            },

            agregar(item) {
                if (this.selected.find((selectedItem) => Number(selectedItem.id) === Number(item.id))) {
                    return;
                }

                const selectedInstitutionId = this.getSelectedInstitutionId();
                const itemInstitutionId = item.institucion_id ? String(item.institucion_id) : '';

                if (selectedInstitutionId && itemInstitutionId && selectedInstitutionId !== itemInstitutionId) {
                    this.errorBusqueda = 'No se pueden mezclar equipos de instituciones distintas en la misma acta.';
                    return;
                }

                this.selected.push({
                    ...item,
                    institucion_id: item.institucion_id ? Number(item.institucion_id) : null,
                    servicio_id: item.servicio_id ? Number(item.servicio_id) : null,
                    oficina_id: item.oficina_id ? Number(item.oficina_id) : null,
                    cantidad: 1,
                    accesorios: '',
                });

                if (this.tipo === 'traslado') {
                    this.refreshTrasladoContext(true);
                }
            },

            remove(index) {
                this.selected.splice(index, 1);

                if (this.tipo === 'traslado') {
                    this.refreshTrasladoContext(true);
                }
            },
        };
    }
</script>
@endsection


