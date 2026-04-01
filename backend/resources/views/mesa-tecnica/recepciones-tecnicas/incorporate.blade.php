@extends('layouts.app')

@section('title', 'Incorporar equipo '.$recepcionTecnica->codigo)
@section('header', 'Incorporar equipo')

@section('content')
    @php($equipo = $recepcionTecnica->resolvedEquipo())

    <div
        x-data="recepcionTecnicaIncorporate({
            mode: @js(old('modo_incorporacion', 'nuevo')),
            selectedEquipo: @js($restoredSelectedEquipo),
            activeInstitutionId: @js($authInstitutionContext['activeInstitutionId'] ?? null),
            operatesGlobally: @js((bool) ($authInstitutionContext['operatesGlobally'] ?? false)),
            endpoints: {
                equipos: @js(route('api.search.equipos')),
            },
            destino: {
                institutionId: @js((string) old('institution_id', $recepcionTecnica->institution_id)),
                serviceId: @js((string) old('service_id', '')),
                officeId: @js((string) old('office_id', old('oficina_id', ''))),
                tipoEquipoId: @js((string) old('tipo_equipo_id', '')),
            },
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Incorporar o vincular equipo</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ticket {{ $recepcionTecnica->codigo }}. Puede vincular un equipo ya existente o darlo de alta ahora en el sistema.
                </p>
            </div>

            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', $recepcionTecnica) }}" class="btn btn-neutral w-full sm:w-auto">
                <x-icon name="x" class="h-4 w-4" />
                Volver al detalle
            </a>
        </div>

        @if ($equipo)
            <div class="rounded-[2rem] border border-emerald-200 bg-emerald-50 px-5 py-5 sm:px-6">
                <p class="text-sm font-semibold text-emerald-900">Este ingreso tecnico ya tiene un equipo vinculado: {{ $equipo->reference() }}.</p>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ticket base</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">{{ $recepcionTecnica->equipmentReference() }}</h2>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Referencia</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->referencia_equipo ?: '-' }}</p>
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Tipo / marca / modelo</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ trim(collect([$recepcionTecnica->tipo_equipo_texto, $recepcionTecnica->marca, $recepcionTecnica->modelo])->filter()->implode(' / ')) ?: '-' }}</p>
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Serie</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->numero_serie ?: '-' }}</p>
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Patrimonial</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->bien_patrimonial ?: '-' }}</p>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.store', $recepcionTecnica) }}" class="space-y-6">
                @csrf

                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Modo de incorporacion</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Seleccione una opcion</h2>
                    </div>

                    <div class="mt-4 grid gap-4">
                        <button
                            type="button"
                            @click="mode = 'existente'"
                            :class="mode === 'existente' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-900'"
                            class="rounded-[1.75rem] border px-5 py-5 text-left transition"
                        >
                            <p class="text-sm font-semibold uppercase tracking-[0.14em]">Vincular existente</p>
                            <p class="mt-2 text-sm opacity-80">Buscar un equipo ya registrado y asociarlo a este ticket.</p>
                        </button>

                        <button
                            type="button"
                            @click="mode = 'nuevo'"
                            :class="mode === 'nuevo' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-900'"
                            class="rounded-[1.75rem] border px-5 py-5 text-left transition"
                        >
                            <p class="text-sm font-semibold uppercase tracking-[0.14em]">Crear equipo nuevo</p>
                            <p class="mt-2 text-sm opacity-80">Dar de alta el equipo en el modulo Equipos y dejarlo vinculado a este ingreso.</p>
                        </button>
                    </div>

                    <input type="hidden" name="modo_incorporacion" :value="mode">
                </section>

                <section x-show="mode === 'existente'" x-cloak class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Busqueda rapida</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Equipo existente</h2>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <input type="text" x-model="search.query" @input.debounce.350ms="searchEquipos()" class="app-input" placeholder="Codigo interno, serie, patrimonial o QR">
                            <button type="button" class="btn btn-primary" @click="searchEquipos()">
                                <x-icon name="search" class="h-4 w-4" />
                                Buscar
                            </button>
                        </div>

                        <p class="text-sm text-slate-500" x-text="search.loading ? 'Buscando equipo...' : search.message"></p>

                        <div class="grid gap-3" x-show="search.results.length > 0">
                            <template x-for="item in search.results" :key="`link-${item.id}`">
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-base font-semibold text-slate-950" x-text="item.label || item.tipo"></p>
                                            <p class="mt-1 text-sm text-slate-600" x-text="item.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                        </div>
                                        <button type="button" class="btn btn-neutral" @click="selectEquipo(item)">Seleccionar</button>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div x-show="selectedEquipo" x-cloak class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-5 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Equipo seleccionado</p>
                            <p class="mt-2 text-lg font-semibold text-emerald-950" x-text="selectedEquipo?.label || selectedEquipo?.tipo"></p>
                            <p class="mt-1 text-sm text-emerald-900" x-text="selectedEquipo?.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                        </div>

                        <input type="hidden" name="equipo_id" :value="selectedEquipo?.id || ''">
                        @error('equipo_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section x-show="mode === 'nuevo'" x-cloak class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Alta del equipo</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Crear equipo nuevo</h2>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="marca" class="mb-2 block text-sm font-medium text-slate-700">Marca</label>
                            <input id="marca" name="marca" type="text" value="{{ old('marca', $recepcionTecnica->marca) }}" class="app-input">
                            @error('marca')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="modelo" class="mb-2 block text-sm font-medium text-slate-700">Modelo</label>
                            <input id="modelo" name="modelo" type="text" value="{{ old('modelo', $recepcionTecnica->modelo) }}" class="app-input">
                            @error('modelo')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="numero_serie" class="mb-2 block text-sm font-medium text-slate-700">Numero de serie</label>
                            <input id="numero_serie" name="numero_serie" type="text" value="{{ old('numero_serie', $recepcionTecnica->numero_serie) }}" class="app-input">
                            @error('numero_serie')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="bien_patrimonial" class="mb-2 block text-sm font-medium text-slate-700">Bien patrimonial</label>
                            <input id="bien_patrimonial" name="bien_patrimonial" type="text" value="{{ old('bien_patrimonial', $recepcionTecnica->bien_patrimonial) }}" class="app-input">
                            @error('bien_patrimonial')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div
                            @autocomplete-selected.window="if ($event.detail.name === 'institution_id') { handleDestinoInstitutionSelected($event.detail.value); }"
                            @autocomplete-cleared.window="if ($event.detail.name === 'institution_id') { handleDestinoInstitutionSelected(''); }"
                        >
                            <label for="institution_id" class="mb-2 block text-sm font-medium text-slate-700">Institucion destino</label>
                            <x-autocomplete name="institution_id" endpoint="/api/search/institutions" placeholder="Buscar institucion..." :value="old('institution_id', $recepcionTecnica->institution_id)" :label="old('institution_id_label')" />
                            <input type="hidden" name="institution_id" x-model="destino.institutionId">
                            @error('institution_id')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                @autocomplete-selected.window="if ($event.detail.name === 'service_id') { handleDestinoServiceSelected($event.detail.value); }"
                                @autocomplete-cleared.window="if ($event.detail.name === 'service_id') { handleDestinoServiceSelected(''); }"
                            >
                                <label for="service_id" class="mb-2 block text-sm font-medium text-slate-700">Servicio destino</label>
                                <x-autocomplete name="service_id" endpoint="/api/search/services" placeholder="Buscar servicio..." :value="old('service_id')" :label="old('service_id_label')" :params="['institution_id' => old('institution_id', $recepcionTecnica->institution_id)]" />
                                <input type="hidden" name="service_id" x-model="destino.serviceId">
                                @error('service_id')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <div
                                @autocomplete-selected.window="if ($event.detail.name === 'oficina_id') { destino.officeId = String($event.detail.value); }"
                                @autocomplete-cleared.window="if ($event.detail.name === 'oficina_id') { destino.officeId = ''; }"
                            >
                                <label for="oficina_id" class="mb-2 block text-sm font-medium text-slate-700">Oficina destino</label>
                                <x-autocomplete name="oficina_id" endpoint="/api/search/offices" placeholder="Buscar oficina..." :value="old('office_id', old('oficina_id'))" :label="old('office_id_label', old('oficina_id_label'))" :params="['service_id' => old('service_id'), 'institution_id' => old('institution_id', $recepcionTecnica->institution_id)]" />
                                <input type="hidden" name="office_id" x-model="destino.officeId">
                                @if ($errors->has('office_id'))
                                    <p class="form-error mt-2">{{ $errors->first('office_id') }}</p>
                                @elseif ($errors->has('oficina_id'))
                                    <p class="form-error mt-2">{{ $errors->first('oficina_id') }}</p>
                                @endif
                            </div>
                        </div>

                        <div
                            @autocomplete-selected.window="if ($event.detail.name === 'tipo_equipo_id') { destino.tipoEquipoId = String($event.detail.value); }"
                            @autocomplete-cleared.window="if ($event.detail.name === 'tipo_equipo_id') { destino.tipoEquipoId = ''; }"
                        >
                            <label for="tipo_equipo_id" class="mb-2 block text-sm font-medium text-slate-700">Tipo de equipo</label>
                            <x-autocomplete name="tipo_equipo_id" endpoint="/api/search/tipos-equipos" placeholder="Buscar tipo de equipo..." :value="old('tipo_equipo_id')" :label="old('tipo_equipo_id_label')" />
                            <input type="hidden" name="tipo_equipo_id" x-model="destino.tipoEquipoId">
                            @error('tipo_equipo_id')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado inicial</label>
                                <select id="estado" name="estado" class="app-input">
                                    <option value="">Seleccione un estado</option>
                                    @foreach ($equipmentStates as $state)
                                        <option value="{{ $state }}" @selected(old('estado') === $state)>{{ strtoupper(str_replace('_', ' ', $state)) }}</option>
                                    @endforeach
                                </select>
                                @error('estado')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="fecha_ingreso" class="mb-2 block text-sm font-medium text-slate-700">Fecha de ingreso</label>
                                <input id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ old('fecha_ingreso', $recepcionTecnica->fecha_recepcion?->format('Y-m-d')) }}" class="app-input">
                                @error('fecha_ingreso')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', $recepcionTecnica) }}" class="btn btn-neutral">Cancelar</a>
                    <button type="submit" class="btn btn-primary gap-2">
                        <x-icon name="check-circle-2" class="h-4 w-4" />
                        <span x-text="mode === 'existente' ? 'Vincular equipo existente' : 'Crear equipo y vincular'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function recepcionTecnicaIncorporate(config) {
            return {
                mode: config.mode || 'nuevo',
                selectedEquipo: config.selectedEquipo || null,
                activeInstitutionId: config.activeInstitutionId,
                operatesGlobally: Boolean(config.operatesGlobally),
                destino: {
                    institutionId: String(config.destino?.institutionId ?? ''),
                    serviceId: String(config.destino?.serviceId ?? ''),
                    officeId: String(config.destino?.officeId ?? ''),
                    tipoEquipoId: String(config.destino?.tipoEquipoId ?? ''),
                },
                search: {
                    query: '',
                    results: [],
                    loading: false,
                    message: 'Escriba un identificador visible para comenzar.',
                    controller: null,
                },
                endpoints: config.endpoints,

                init() {
                    this.dispatchAutocompleteParams();
                },

                selectEquipo(item) {
                    this.selectedEquipo = item;
                    this.search.results = [];
                    this.search.message = 'Equipo listo para continuar.';
                },

                async searchEquipos() {
                    const query = String(this.search.query || '').trim();

                    if (query === '') {
                        this.search.results = [];
                        this.search.message = 'Escriba un identificador visible para comenzar.';
                        return;
                    }

                    if (this.search.controller) {
                        this.search.controller.abort();
                    }

                    this.search.loading = true;
                    this.search.controller = new AbortController();

                    try {
                        const url = new URL(this.endpoints.equipos, window.location.origin);
                        url.searchParams.set('q', query);
                        url.searchParams.set('acta_context', '1');

                        if (!this.operatesGlobally && this.activeInstitutionId) {
                            url.searchParams.set('institution_id', String(this.activeInstitutionId));
                        }

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: this.search.controller.signal,
                        });

                        if (!response.ok) {
                            this.search.results = [];
                            this.search.message = 'No se pudo completar la busqueda en este momento.';
                            return;
                        }

                        const payload = await response.json();
                        const items = Array.isArray(payload) ? payload : (payload.items ?? []);
                        this.search.results = items;
                        this.search.message = items.length > 0 ? 'Seleccione un equipo.' : 'No se encontraron equipos con ese criterio.';
                    } catch (error) {
                        if (error?.name !== 'AbortError') {
                            this.search.results = [];
                            this.search.message = 'No se pudo realizar la busqueda. Intente nuevamente.';
                        }
                    } finally {
                        this.search.loading = false;
                    }
                },

                dispatchAutocompleteParams() {
                    window.dispatchEvent(new CustomEvent('autocomplete-set-params', {
                        detail: { name: 'service_id', params: { institution_id: this.destino.institutionId } },
                    }));
                    window.dispatchEvent(new CustomEvent('autocomplete-set-params', {
                        detail: {
                            name: 'oficina_id',
                            params: {
                                institution_id: this.destino.institutionId,
                                service_id: this.destino.serviceId,
                            },
                        },
                    }));
                },

                handleDestinoInstitutionSelected(value) {
                    this.destino.institutionId = String(value || '');
                    this.destino.serviceId = '';
                    this.destino.officeId = '';
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'service_id' } }));
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'oficina_id' } }));
                    this.dispatchAutocompleteParams();
                },

                handleDestinoServiceSelected(value) {
                    this.destino.serviceId = String(value || '');
                    this.destino.officeId = '';
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'oficina_id' } }));
                    this.dispatchAutocompleteParams();
                },
            };
        }
    </script>
@endsection
