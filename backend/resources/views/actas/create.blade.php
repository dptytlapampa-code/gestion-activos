@extends('layouts.app')

@section('title', 'Nueva acta de trazabilidad')
@section('header', 'Actas de trazabilidad')

@section('content')
<div
    x-data="actaWizard(@js([
        'tipo' => old('tipo'),
        'fecha' => old('fecha', now()->toDateString()),
        'institution_id' => old('institution_id', $isSuperadmin ? null : $userInstitutionId),
        'institution_destino_id' => old('institution_destino_id'),
        'service_origen_id' => old('service_origen_id'),
        'office_origen_id' => old('office_origen_id'),
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
            <h3 class="text-lg font-semibold text-slate-900">Paso 2 - Datos del acta</h3>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Fecha</label>
                    <input type="date" name="fecha" x-model="fecha" class="mt-1 w-full rounded-xl border-slate-300" required>
                    @error('fecha') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="showMainInstitution()" x-cloak>
                    <label class="block text-sm font-medium text-slate-700">Institucion</label>
                    <select name="institution_id" x-model="institution_id" @change="onInstitutionChange('main')" class="mt-1 w-full rounded-xl border-slate-300" :disabled="!isSuperadmin">
                        <option value="">Seleccionar</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->nombre }}</option>
                        @endforeach
                    </select>
                    @error('institution_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <input x-show="!isSuperadmin" type="hidden" name="institution_id" :value="institution_id">
                </div>

                <div x-show="tipo === 'traslado'" x-cloak>
                    <label class="block text-sm font-medium text-slate-700">Institucion destino</label>
                    <select name="institution_destino_id" x-model="institution_destino_id" @change="onInstitutionChange('destino')" class="mt-1 w-full rounded-xl border-slate-300" :disabled="!isSuperadmin">
                        <option value="">Seleccionar</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->nombre }}</option>
                        @endforeach
                    </select>
                    @error('institution_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <input x-show="!isSuperadmin" type="hidden" name="institution_destino_id" :value="institution_destino_id">
                </div>
            </div>

            <div x-show="tipo === 'traslado'" x-cloak class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h4 class="text-sm font-semibold text-slate-800">Origen</h4>
                    <div class="mt-3 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Servicio origen</label>
                            <select name="service_origen_id" x-model="service_origen_id" @change="onServiceChange('origen')" class="mt-1 w-full rounded-xl border-slate-300">
                                <option value="">Seleccionar</option>
                                <template x-for="service in serviceOptions.origen" :key="`so-${service.id}`">
                                    <option :value="service.id" x-text="service.label"></option>
                                </template>
                            </select>
                            @error('service_origen_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Oficina origen</label>
                            <select name="office_origen_id" x-model="office_origen_id" class="mt-1 w-full rounded-xl border-slate-300">
                                <option value="">Seleccionar</option>
                                <template x-for="office in officeOptions.origen" :key="`oo-${office.id}`">
                                    <option :value="office.id" x-text="office.label"></option>
                                </template>
                            </select>
                            @error('office_origen_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <h4 class="text-sm font-semibold text-slate-800">Destino</h4>
                    <div class="mt-3 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Servicio destino</label>
                            <select name="service_destino_id" x-model="service_destino_id" @change="onServiceChange('destino')" class="mt-1 w-full rounded-xl border-slate-300">
                                <option value="">Seleccionar</option>
                                <template x-for="service in serviceOptions.destino" :key="`sd-${service.id}`">
                                    <option :value="service.id" x-text="service.label"></option>
                                </template>
                            </select>
                            @error('service_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Oficina destino</label>
                            <select name="office_destino_id" x-model="office_destino_id" class="mt-1 w-full rounded-xl border-slate-300">
                                <option value="">Seleccionar</option>
                                <template x-for="office in officeOptions.destino" :key="`od-${office.id}`">
                                    <option :value="office.id" x-text="office.label"></option>
                                </template>
                            </select>
                            @error('office_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="tipo !== 'traslado'" x-cloak class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Servicio</label>
                    <select name="service_destino_id" x-model="service_destino_id" @change="onServiceChange('main')" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">Seleccionar</option>
                        <template x-for="service in serviceOptions.main" :key="`sm-${service.id}`">
                            <option :value="service.id" x-text="service.label"></option>
                        </template>
                    </select>
                    @error('service_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Oficina</label>
                    <select name="office_destino_id" x-model="office_destino_id" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">Seleccionar</option>
                        <template x-for="office in officeOptions.main" :key="`om-${office.id}`">
                            <option :value="office.id" x-text="office.label"></option>
                        </template>
                    </select>
                    @error('office_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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

        <section class="card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-slate-900">Paso 3 - Seleccion de equipos</h3>
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
                        <p class="text-sm text-slate-600">Oficina actual: <span x-text="item.oficina || '-'" class="font-medium"></span></p>
                        <button type="button" @click="agregar(item)" class="mt-3 min-h-[48px] w-full rounded-xl border border-primary-500 bg-primary-50 text-sm font-semibold text-primary-700">
                            AGREGAR
                        </button>
                    </article>
                </template>
            </div>
        </section>

        <section class="card space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Paso 4 - Equipos seleccionados</h3>

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

            <template x-for="(item, index) in selected" :key="`hidden-${item.id}`">
                <div>
                    <input type="hidden" :name="`equipos[${index}][equipo_id]`" :value="item.id">
                    <input type="hidden" :name="`equipos[${index}][cantidad]`" :value="item.cantidad || 1">
                    <input type="hidden" :name="`equipos[${index}][accesorios]`" :value="item.accesorios || ''">
                </div>
            </template>
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
            institution_id: initial.institution_id ? String(initial.institution_id) : (isSuperadmin ? '' : String(userInstitutionId || '')),
            institution_destino_id: initial.institution_destino_id ? String(initial.institution_destino_id) : '',
            service_origen_id: initial.service_origen_id ? String(initial.service_origen_id) : '',
            office_origen_id: initial.office_origen_id ? String(initial.office_origen_id) : '',
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
            selected: Array.isArray(oldSelected) ? oldSelected.map((item) => ({ ...item, cantidad: item.cantidad || 1, accesorios: item.accesorios || '' })) : [],
            errorBusqueda: '',
            serviceOptions: { main: [], origen: [], destino: [] },
            officeOptions: { main: [], origen: [], destino: [] },
            isSuperadmin,

            init() {
                if (!this.isSuperadmin && this.tipo === 'traslado' && !this.institution_destino_id) {
                    this.institution_destino_id = this.institution_id;
                }
                this.bootstrapSelects();
            },

            selectTipo(tipo) {
                this.tipo = tipo;
                if (!this.isSuperadmin && tipo === 'traslado' && !this.institution_destino_id) {
                    this.institution_destino_id = this.institution_id;
                }
                this.results = [];
                this.errorBusqueda = '';
            },

            showMainInstitution() {
                return this.tipo !== 'traslado' || this.isSuperadmin;
            },

            onInstitutionChange(scope) {
                if (scope === 'main') {
                    this.service_destino_id = '';
                    this.office_destino_id = '';
                    this.serviceOptions.main = [];
                    this.officeOptions.main = [];
                    this.loadServices('main');
                }

                if (scope === 'destino') {
                    this.service_destino_id = '';
                    this.office_destino_id = '';
                    this.serviceOptions.destino = [];
                    this.officeOptions.destino = [];
                    this.loadServices('destino');
                }
            },

            onServiceChange(scope) {
                if (scope === 'main') {
                    this.office_destino_id = '';
                    this.officeOptions.main = [];
                    this.loadOffices('main');
                }

                if (scope === 'origen') {
                    this.office_origen_id = '';
                    this.officeOptions.origen = [];
                    this.loadOffices('origen');
                }

                if (scope === 'destino') {
                    this.office_destino_id = '';
                    this.officeOptions.destino = [];
                    this.loadOffices('destino');
                }
            },

            bootstrapSelects() {
                this.loadServices('main');
                this.loadServices('origen');
                this.loadServices('destino');
                this.loadOffices('main');
                this.loadOffices('origen');
                this.loadOffices('destino');
            },

            async loadServices(scope) {
                const institutionId = this.getInstitutionForScope(scope);
                if (!institutionId) return;

                const params = new URLSearchParams({ q: '...', institution_id: institutionId });
                const response = await fetch(`/api/search/services?${params.toString()}`);
                const payload = await response.json();
                this.serviceOptions[scope] = Array.isArray(payload) ? payload : [];
            },

            async loadOffices(scope) {
                const institutionId = this.getInstitutionForScope(scope);
                const serviceId = this.getServiceForScope(scope);
                if (!institutionId || !serviceId) return;

                const params = new URLSearchParams({ q: '...', institution_id: institutionId, service_id: serviceId });
                const response = await fetch(`/api/search/offices?${params.toString()}`);
                const payload = await response.json();
                this.officeOptions[scope] = Array.isArray(payload) ? payload : [];
            },

            getInstitutionForScope(scope) {
                if (scope === 'main') return this.institution_id;
                if (scope === 'origen') return this.institution_id;
                if (scope === 'destino') return this.institution_destino_id;
                return '';
            },

            getServiceForScope(scope) {
                if (scope === 'main') return this.service_destino_id;
                if (scope === 'origen') return this.service_origen_id;
                if (scope === 'destino') return this.service_destino_id;
                return '';
            },

            getSearchInstitutionId() {
                if (this.tipo === 'traslado') {
                    return this.institution_id;
                }

                return this.institution_id;
            },

            async buscarEquipos() {
                this.errorBusqueda = '';

                const institutionId = this.getSearchInstitutionId();
                if (!institutionId) {
                    this.errorBusqueda = 'Seleccione la institucion del acta antes de buscar equipos.';
                    return;
                }

                const q = this.query.trim();
                if (q.length < 1) {
                    this.errorBusqueda = 'Ingrese un termino de busqueda o use ... para listar equipos.';
                    return;
                }

                const params = new URLSearchParams({ q, institution_id: institutionId });
                const response = await fetch(`/api/search/equipos?${params.toString()}`);
                const payload = await response.json();
                this.results = Array.isArray(payload) ? payload : [];

                if (!this.results.length) {
                    this.errorBusqueda = 'No se encontraron equipos para los filtros indicados.';
                }
            },

            agregar(item) {
                if (this.selected.find((selectedItem) => Number(selectedItem.id) === Number(item.id))) {
                    return;
                }

                this.selected.push({ ...item, cantidad: 1, accesorios: '' });
            },

            remove(index) {
                this.selected.splice(index, 1);
            },
        };
    }
</script>
@endsection


