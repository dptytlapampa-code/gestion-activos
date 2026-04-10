@extends('layouts.app')

@section('title', 'Recibir para reparacion')
@section('header', 'Recibir para reparacion')

@section('content')
    @include('mesa-tecnica.partials.module-styles')

    @php
        $operatesGlobally = (bool) ($authInstitutionContext['operatesGlobally'] ?? false);
        $currentMode = old('modo_equipo', 'nuevo');
        $incorporateRequested = (bool) old('incorporar_equipo', false);
        $selectedEquipoResolved = $restoredSelectedEquipo !== null;
        $ticketTimestampValue = old('fecha_hora_ingreso', $defaultReceptionTimestamp);
        $sectorReceptorValue = old('sector_receptor', 'Mesa Tecnica / Nivel Central');

        $existingEquipmentErrors = $errors->has('equipo_id');
        $quickIdentificationErrors = collect([
            'referencia_equipo',
            'tipo_equipo_texto',
            'numero_serie',
            'bien_patrimonial',
        ])->contains(fn (string $field): bool => $errors->has($field));
        $equipmentDetailsErrors = collect([
            'marca',
            'modelo',
            'fecha_hora_ingreso',
            'sector_receptor',
            'institution_id',
            'service_id',
            'office_id',
            'oficina_id',
            'tipo_equipo_id',
            'estado',
            'fecha_ingreso',
            'incorporar_equipo',
        ])->contains(fn (string $field): bool => $errors->has($field));
        $deliveryErrors = collect([
            'persona_nombre',
            'persona_documento',
            'persona_telefono',
            'persona_relacion_equipo',
            'persona_area',
            'persona_institucion',
        ])->contains(fn (string $field): bool => $errors->has($field));
        $sourceErrors = collect([
            'procedencia_institution_id',
            'procedencia_service_id',
            'procedencia_office_id',
            'procedencia_hospital',
            'procedencia_libre',
        ])->contains(fn (string $field): bool => $errors->has($field));
        $failureErrors = collect([
            'falla_motivo',
            'descripcion_falla',
        ])->contains(fn (string $field): bool => $errors->has($field));
        $notesErrors = collect([
            'accesorios_entregados',
            'estado_fisico_inicial',
            'observaciones_recepcion',
            'observaciones_internas',
        ])->contains(fn (string $field): bool => $errors->has($field));

        $quickIdentificationFilled = $currentMode === 'existente'
            ? $selectedEquipoResolved || filled(old('equipo_id'))
            : collect([
                old('referencia_equipo'),
                old('tipo_equipo_texto'),
                old('numero_serie'),
                old('bien_patrimonial'),
            ])->map(fn (mixed $value): string => trim((string) $value))->filter()->isNotEmpty();

        $inventoryComplete = collect([
            'institution_id',
            'service_id',
            'office_id',
            'tipo_equipo_id',
            'estado',
            'fecha_ingreso',
            'marca',
            'modelo',
        ])->every(fn (string $field): bool => filled(old($field)));

        $datosEquipoComplete = filled($ticketTimestampValue)
            && filled($sectorReceptorValue)
            && (! ($currentMode === 'nuevo' && $incorporateRequested) || $inventoryComplete);
        $deliveryComplete = filled(old('persona_nombre'));
        $sourceComplete = collect([
            'procedencia_institution_id',
            'procedencia_service_id',
            'procedencia_office_id',
            'procedencia_hospital',
            'procedencia_libre',
        ])->contains(fn (string $field): bool => filled(old($field)));
        $notesComplete = collect([
            'accesorios_entregados',
            'estado_fisico_inicial',
            'observaciones_recepcion',
            'observaciones_internas',
        ])->contains(fn (string $field): bool => filled(old($field)));

        $badgeClasses = [
            'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'neutral' => 'border-slate-200 bg-slate-100 text-slate-700',
        ];

        $resolveSectionStatus = function (bool $hasErrors, bool $isComplete, bool $isRequired, string $pendingHint, string $optionalHint = 'Completar solo si aporta contexto.') use ($badgeClasses): array {
            if ($hasErrors) {
                return ['label' => 'Revisar', 'class' => $badgeClasses['danger'], 'hint' => 'Hay datos pendientes o invalidos.'];
            }

            if ($isComplete) {
                return ['label' => 'Listo', 'class' => $badgeClasses['success'], 'hint' => $isRequired ? 'No requiere mas carga inmediata.' : 'Ya contiene informacion.'];
            }

            if ($isRequired) {
                return ['label' => 'Incompleto', 'class' => $badgeClasses['warning'], 'hint' => $pendingHint];
            }

            return ['label' => 'Opcional', 'class' => $badgeClasses['neutral'], 'hint' => $optionalHint];
        };

        $datosEquipoStatus = $resolveSectionStatus(
            $equipmentDetailsErrors,
            $datosEquipoComplete,
            true,
            $currentMode === 'nuevo' && $incorporateRequested
                ? 'Faltan datos de recepcion o de alta en inventario.'
                : 'Revise la recepcion tecnica y el detalle del equipo.'
        );
        $deliveryStatus = $resolveSectionStatus($deliveryErrors, $deliveryComplete, true, 'Falta al menos el nombre de quien entrega el equipo.');
        $sourceStatus = $resolveSectionStatus($sourceErrors, $sourceComplete, false, '', 'Use esta seccion solo si necesita mas trazabilidad.');
        $notesStatus = $resolveSectionStatus($notesErrors, $notesComplete, false, '', 'Accesorios, estado fisico y notas para el comprobante.');

        $firstNecessarySection = collect([
            ['key' => 'datos-equipo', 'needs' => ! $datosEquipoComplete],
            ['key' => 'quien-lo-trae', 'needs' => ! $deliveryComplete],
        ])->first(fn (array $section): bool => $section['needs'])['key'] ?? null;
    @endphp

    <div
        x-data="recepcionTecnicaCreate({
            mode: @js($currentMode),
            incorporate: @js($incorporateRequested),
            selectedEquipo: @js($restoredSelectedEquipo),
            activeInstitutionId: @js($authInstitutionContext['activeInstitutionId'] ?? null),
            operatesGlobally: @js($operatesGlobally),
            endpoints: { equipos: @js(route('api.search.equipos')) },
            procedencia: {
                institutionId: @js((string) old('procedencia_institution_id', '')),
                serviceId: @js((string) old('procedencia_service_id', '')),
                officeId: @js((string) old('procedencia_office_id', '')),
            },
            destino: {
                institutionId: @js((string) old('institution_id', '')),
                serviceId: @js((string) old('service_id', '')),
                officeId: @js((string) old('office_id', old('oficina_id', ''))),
                tipoEquipoId: @js((string) old('tipo_equipo_id', '')),
            },
        })"
        x-init="init()"
        class="mt-operational-shell space-y-4 lg:space-y-5"
    >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="app-badge bg-indigo-50 px-3 text-indigo-700">Ingreso tecnico temporal</span>
                    <span class="app-badge bg-slate-100 px-3 text-slate-700">Sin cambios patrimoniales</span>
                </div>
                <div>
                    <h3 class="text-xl font-semibold tracking-tight text-slate-950">Recibir para reparacion</h3>
                    <p class="mt-1 text-sm text-slate-600">Operacion rapida: identifique el equipo, registre la falla y despliegue solo lo que necesite completar.</p>
                </div>
            </div>

            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="btn btn-slate w-full sm:w-auto">
                <x-icon name="x" class="h-4 w-4" />
                Volver
            </a>
        </div>

        <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.store') }}" class="space-y-4 pb-24">
            @csrf

            <section class="app-panel mt-panel mt-operational-panel rounded-[1.75rem] px-4 py-4 sm:px-5">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <span class="mt-inline-note">
                                <x-icon name="shield-check" class="h-4 w-4" />
                                Custodia temporal en Mesa Tecnica
                            </span>
                            <div>
                                <p class="mt-form-kicker">Modo de ingreso</p>
                                <h2 class="mt-form-title">Defina como entra el equipo</h2>
                                <p class="mt-form-muted">El ticket se registra primero. El alta patrimonial solo aparece si realmente hace falta.</p>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                                Revise los campos marcados antes de registrar el ticket.
                            </div>
                        @endif
                    </div>

                    <div class="mt-mode-grid">
                        <button type="button" @click="setMode('existente')" class="mt-mode-card" :class="mode === 'existente' ? 'mt-mode-card-active' : ''">
                            <span class="mt-mode-card-kicker">Equipo existente</span>
                            <span class="mt-2 block text-base font-semibold">Vincular un equipo ya cargado</span>
                            <span class="mt-mode-card-note">Buscar por codigo, serie, patrimonial o QR y abrir el ticket sin duplicar inventario.</span>
                        </button>

                        <button type="button" @click="setMode('nuevo')" class="mt-mode-card" :class="mode === 'nuevo' ? 'mt-mode-card-active' : ''">
                            <span class="mt-mode-card-kicker">Equipo nuevo</span>
                            <span class="mt-2 block text-base font-semibold">Recibir aunque todavia no este en sistema</span>
                            <span class="mt-mode-card-note">Se carga por referencia visible y solo se muestra el alta patrimonial si la necesita.</span>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="modo_equipo" :value="mode">
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                <div class="app-panel mt-panel mt-operational-panel rounded-[1.75rem] px-4 py-4 sm:px-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="mt-form-kicker">Identificacion rapida</p>
                            <h2 class="mt-form-title" x-text="mode === 'existente' ? 'Ubicar equipo cargado' : 'Describir equipo recibido'"></h2>
                            <p class="mt-form-muted">Con una referencia clara ya puede registrar el ingreso tecnico.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $currentMode === 'existente' ? ($existingEquipmentErrors ? $badgeClasses['danger'] : ($quickIdentificationFilled ? $badgeClasses['success'] : $badgeClasses['warning'])) : ($quickIdentificationErrors ? $badgeClasses['danger'] : ($quickIdentificationFilled ? $badgeClasses['success'] : $badgeClasses['warning'])) }}">
                            {{ $quickIdentificationFilled ? 'Listo' : 'Pendiente' }}
                        </span>
                    </div>

                    <div x-show="mode === 'existente'" x-cloak class="mt-4 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <div>
                                <label for="equipo-search" class="mb-2 block text-sm font-medium text-slate-700">Identificador</label>
                                <input id="equipo-search" type="text" x-model="search.query" @input.debounce.350ms="searchEquipos()" @keydown.enter.prevent="searchEquipos()" class="app-input" placeholder="Codigo, serie, patrimonial o QR" autocomplete="off">
                            </div>

                            <button type="button" class="btn btn-slate self-end" @click="searchEquipos()">
                                <x-icon name="search" class="h-4 w-4" />
                                Buscar
                            </button>
                        </div>

                        <p class="text-sm text-slate-500" x-text="search.loading ? 'Buscando equipo...' : search.message"></p>

                        <div x-show="search.results.length > 0" x-cloak class="mt-search-results grid gap-3 md:grid-cols-2">
                            <template x-for="item in search.results" :key="`equipo-${item.id}`">
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/85 p-4">
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950" x-text="item.label || item.tipo"></p>
                                            <p class="mt-1 text-sm text-slate-600" x-text="item.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.codigo_interno || 'Sin codigo interno'"></span>
                                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.numero_serie || 'Sin serie'"></span>
                                        </div>
                                        <button type="button" class="btn btn-neutral" @click="selectEquipo(item)">Seleccionar</button>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div x-show="selectedEquipo" x-cloak class="rounded-[1.4rem] border border-emerald-200 bg-emerald-50 px-4 py-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Equipo seleccionado</p>
                                    <p class="text-base font-semibold text-emerald-950" x-text="selectedEquipo?.label || selectedEquipo?.tipo"></p>
                                    <p class="text-sm text-emerald-900" x-text="selectedEquipo?.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="app-badge bg-white px-3 text-emerald-700" x-text="selectedEquipo?.codigo_interno || 'Sin codigo interno'"></span>
                                        <span class="app-badge bg-white px-3 text-emerald-700" x-text="selectedEquipo?.numero_serie || 'Sin serie'"></span>
                                        <span class="app-badge bg-white px-3 text-emerald-700" x-text="selectedEquipo?.bien_patrimonial || 'Sin patrimonial'"></span>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-neutral" @click="clearSelectedEquipo()">
                                    <x-icon name="x" class="h-4 w-4" />
                                    Cambiar
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="equipo_id" :value="selectedEquipo?.id || ''">
                        @error('equipo_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="mode === 'nuevo'" x-cloak class="mt-4 space-y-4">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="referencia_equipo" class="mb-2 block text-sm font-medium text-slate-700">Referencia visible</label>
                                <input id="referencia_equipo" name="referencia_equipo" type="text" value="{{ old('referencia_equipo') }}" class="app-input" placeholder="Ej.: notebook blanca de admision, monitor de terapia">
                                @error('referencia_equipo')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tipo_equipo_texto" class="mb-2 block text-sm font-medium text-slate-700">Tipo visible</label>
                                <input id="tipo_equipo_texto" name="tipo_equipo_texto" type="text" value="{{ old('tipo_equipo_texto') }}" class="app-input" placeholder="Notebook, monitor, impresora">
                                @error('tipo_equipo_texto')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="numero_serie" class="mb-2 block text-sm font-medium text-slate-700">Serie</label>
                                <input id="numero_serie" name="numero_serie" type="text" value="{{ old('numero_serie') }}" class="app-input" placeholder="Serie visible">
                                @error('numero_serie')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bien_patrimonial" class="mb-2 block text-sm font-medium text-slate-700">Patrimonial</label>
                                <input id="bien_patrimonial" name="bien_patrimonial" type="text" value="{{ old('bien_patrimonial') }}" class="app-input" placeholder="Codigo patrimonial">
                                @error('bien_patrimonial')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p class="text-xs font-medium text-slate-500">Si todavia no existe en inventario, use la referencia visible y complete el resto luego en <strong>Datos del equipo</strong>.</p>
                    </div>
                </div>

                <div class="app-panel mt-panel mt-operational-panel rounded-[1.75rem] px-4 py-4 sm:px-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="mt-form-kicker">Falla reportada</p>
                            <h2 class="mt-form-title">Motivo y detalle inicial</h2>
                            <p class="mt-form-muted">Mantenga visible solo lo necesario para abrir el ticket rapido.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $failureErrors ? $badgeClasses['danger'] : (filled(old('falla_motivo')) ? $badgeClasses['success'] : $badgeClasses['warning']) }}">
                            {{ filled(old('falla_motivo')) ? 'Listo' : 'Pendiente' }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="falla_motivo" class="mb-2 block text-sm font-medium text-slate-700">Motivo principal</label>
                            <input id="falla_motivo" name="falla_motivo" type="text" value="{{ old('falla_motivo') }}" class="app-input" placeholder="Ej.: no enciende, pantalla rota, sin red">
                            @error('falla_motivo')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="descripcion_falla" class="mb-2 block text-sm font-medium text-slate-700">Detalle breve</label>
                            <textarea id="descripcion_falla" name="descripcion_falla" rows="5" class="app-input" placeholder="Descripcion tecnica corta o referencia del problema">{{ old('descripcion_falla') }}</textarea>
                            @error('descripcion_falla')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            <div class="space-y-3">
                <x-collapsible-panel
                    title="Datos del equipo"
                    eyebrow="Recepcion"
                    icon="monitor"
                    summary="Recepcion tecnica, detalle complementario y alta en inventario si corresponde."
                    :force-open="$equipmentDetailsErrors || $firstNecessarySection === 'datos-equipo'"
                    persist-key="mesa-tecnica-create-v2.datos-equipo"
                    class="mt-operational-panel rounded-[1.5rem]"
                    :status-label="$datosEquipoStatus['label']"
                    :status-class="$datosEquipoStatus['class']"
                    :status-hint="$datosEquipoStatus['hint']"
                >
                    <div class="space-y-5">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="fecha_hora_ingreso" class="mb-2 block text-sm font-medium text-slate-700">Fecha y hora de ingreso</label>
                                <input id="fecha_hora_ingreso" name="fecha_hora_ingreso" type="datetime-local" value="{{ $ticketTimestampValue }}" class="app-input">
                                @error('fecha_hora_ingreso')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="sector_receptor" class="mb-2 block text-sm font-medium text-slate-700">Sector receptor</label>
                                <input id="sector_receptor" name="sector_receptor" type="text" value="{{ $sectorReceptorValue }}" class="app-input" placeholder="Mesa Tecnica / Nivel Central">
                                @error('sector_receptor')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div x-show="mode === 'nuevo'" x-cloak class="mt-form-divider space-y-5">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Detalle complementario</p>
                                <p class="mt-1 text-sm text-slate-500">Solo aparece cuando el equipo entra como nuevo y ayuda a identificarlo mejor.</p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label for="marca" class="mb-2 block text-sm font-medium text-slate-700">Marca</label>
                                    <input id="marca" name="marca" type="text" value="{{ old('marca') }}" class="app-input" placeholder="Marca visible">
                                    @error('marca')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="modelo" class="mb-2 block text-sm font-medium text-slate-700">Modelo</label>
                                    <input id="modelo" name="modelo" type="text" value="{{ old('modelo') }}" class="app-input" placeholder="Modelo visible">
                                    @error('modelo')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="rounded-[1.3rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" name="incorporar_equipo" value="1" x-model="incorporate" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Dar de alta en inventario desde este ticket</span>
                                        <span class="mt-1 block text-sm text-slate-600">Despliega solo los datos patrimoniales obligatorios para incorporarlo al sistema.</span>
                                    </span>
                                </label>
                                @error('incorporar_equipo')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-show="incorporate" x-cloak class="space-y-5 rounded-[1.35rem] border border-indigo-200 bg-indigo-50/75 p-4">
                                <div>
                                    <p class="text-sm font-semibold text-indigo-950">Alta en sistema</p>
                                    <p class="mt-1 text-sm text-indigo-900">Estos datos solo son obligatorios cuando el equipo todavia no existe en inventario.</p>
                                </div>

                                <div
                                    @autocomplete-selected.window="if ($event.detail.name === 'institution_id') { handleDestinoInstitutionSelected($event.detail.value); }"
                                    @autocomplete-cleared.window="if ($event.detail.name === 'institution_id') { handleDestinoInstitutionSelected(''); }"
                                >
                                    <label for="institution_id" class="mb-2 block text-sm font-medium text-slate-700">Institucion</label>
                                    <x-autocomplete
                                        name="institution_id"
                                        endpoint="/api/search/institutions"
                                        placeholder="Buscar institucion"
                                        :value="old('institution_id')"
                                        :label="old('institution_id_label')"
                                    />
                                    <input type="hidden" :value="destino.institutionId" aria-hidden="true">
                                    @error('institution_id')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div
                                        @autocomplete-selected.window="if ($event.detail.name === 'service_id') { handleDestinoServiceSelected($event.detail.value); }"
                                        @autocomplete-cleared.window="if ($event.detail.name === 'service_id') { handleDestinoServiceSelected(''); }"
                                    >
                                        <label for="service_id" class="mb-2 block text-sm font-medium text-slate-700">Servicio</label>
                                        <x-autocomplete
                                            name="service_id"
                                            endpoint="/api/search/services"
                                            placeholder="Buscar servicio"
                                            :value="old('service_id')"
                                            :label="old('service_id_label')"
                                            :params="['institution_id' => old('institution_id')]"
                                        />
                                        <input type="hidden" :value="destino.serviceId" aria-hidden="true">
                                        @error('service_id')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div
                                        @autocomplete-selected.window="if ($event.detail.name === 'oficina_id') { destino.officeId = String($event.detail.value); }"
                                        @autocomplete-cleared.window="if ($event.detail.name === 'oficina_id') { destino.officeId = ''; }"
                                    >
                                        <label for="oficina_id" class="mb-2 block text-sm font-medium text-slate-700">Oficina</label>
                                        <x-autocomplete
                                            name="oficina_id"
                                            endpoint="/api/search/offices"
                                            placeholder="Buscar oficina"
                                            :value="old('office_id', old('oficina_id'))"
                                            :label="old('office_id_label', old('oficina_id_label'))"
                                            :params="['service_id' => old('service_id'), 'institution_id' => old('institution_id')]"
                                        />
                                        <input type="hidden" :value="destino.officeId" aria-hidden="true">
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
                                    <label for="tipo_equipo_id" class="mb-2 block text-sm font-medium text-slate-700">Tipo en inventario</label>
                                    <x-autocomplete
                                        name="tipo_equipo_id"
                                        endpoint="/api/search/tipos-equipos"
                                        placeholder="Buscar tipo"
                                        :value="old('tipo_equipo_id')"
                                        :label="old('tipo_equipo_id_label')"
                                    />
                                    <input type="hidden" :value="destino.tipoEquipoId" aria-hidden="true">
                                    @error('tipo_equipo_id')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
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
                                        <label for="fecha_ingreso" class="mb-2 block text-sm font-medium text-slate-700">Fecha de alta</label>
                                        <input id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ old('fecha_ingreso', now()->toDateString()) }}" class="app-input">
                                        @error('fecha_ingreso')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-collapsible-panel>

                <x-collapsible-panel
                    title="Quien lo trae"
                    eyebrow="Entrega"
                    icon="users"
                    summary="Nombre obligatorio. El resto es opcional para contacto y trazabilidad."
                    :force-open="$deliveryErrors || $firstNecessarySection === 'quien-lo-trae'"
                    persist-key="mesa-tecnica-create-v2.quien-lo-trae"
                    class="mt-operational-panel rounded-[1.5rem]"
                    :status-label="$deliveryStatus['label']"
                    :status-class="$deliveryStatus['class']"
                    :status-hint="$deliveryStatus['hint']"
                >
                    <div class="space-y-4">
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/90 px-4 py-3 text-sm text-slate-600">
                            Complete al menos el nombre para registrar el ticket. Los demas datos solo ayudan a contacto y trazabilidad.
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="persona_nombre" class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                                <input id="persona_nombre" name="persona_nombre" type="text" value="{{ old('persona_nombre') }}" class="app-input" placeholder="Nombre completo">
                                @error('persona_nombre')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="persona_documento" class="mb-2 block text-sm font-medium text-slate-700">Documento</label>
                                <input id="persona_documento" name="persona_documento" type="text" value="{{ old('persona_documento') }}" class="app-input" placeholder="Documento">
                                @error('persona_documento')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="persona_telefono" class="mb-2 block text-sm font-medium text-slate-700">Telefono</label>
                                <input id="persona_telefono" name="persona_telefono" type="text" value="{{ old('persona_telefono') }}" class="app-input" placeholder="Telefono de contacto">
                                @error('persona_telefono')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="persona_relacion_equipo" class="mb-2 block text-sm font-medium text-slate-700">Relacion con el equipo</label>
                                <input id="persona_relacion_equipo" name="persona_relacion_equipo" type="text" value="{{ old('persona_relacion_equipo') }}" class="app-input" placeholder="Usuario, tecnico, mensajeria">
                                @error('persona_relacion_equipo')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="persona_area" class="mb-2 block text-sm font-medium text-slate-700">Area o servicio</label>
                                <input id="persona_area" name="persona_area" type="text" value="{{ old('persona_area') }}" class="app-input" placeholder="Area o servicio">
                                @error('persona_area')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="persona_institucion" class="mb-2 block text-sm font-medium text-slate-700">Institucion</label>
                                <input id="persona_institucion" name="persona_institucion" type="text" value="{{ old('persona_institucion') }}" class="app-input" placeholder="Hospital o institucion">
                                @error('persona_institucion')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-collapsible-panel>

                <x-collapsible-panel
                    title="Procedencia"
                    eyebrow="Origen"
                    icon="building-2"
                    summary="Contexto de procedencia para trazabilidad cuando haga falta."
                    :force-open="$sourceErrors"
                    persist-key="mesa-tecnica-create-v2.procedencia"
                    class="mt-operational-panel rounded-[1.5rem]"
                    :status-label="$sourceStatus['label']"
                    :status-class="$sourceStatus['class']"
                    :status-hint="$sourceStatus['hint']"
                >
                    <div class="space-y-4">
                        <div
                            @autocomplete-selected.window="if ($event.detail.name === 'procedencia_institution_id') { handleProcedenciaInstitutionSelected($event.detail.value); }"
                            @autocomplete-cleared.window="if ($event.detail.name === 'procedencia_institution_id') { handleProcedenciaInstitutionSelected(''); }"
                        >
                            <label for="procedencia_institution_id" class="mb-2 block text-sm font-medium text-slate-700">Institucion</label>
                            <x-autocomplete
                                name="procedencia_institution_id"
                                endpoint="/api/search/institutions"
                                placeholder="Buscar institucion"
                                :value="old('procedencia_institution_id')"
                                :label="old('procedencia_institution_id_label')"
                            />
                            <input type="hidden" :value="procedencia.institutionId" aria-hidden="true">
                            @error('procedencia_institution_id')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div
                                @autocomplete-selected.window="if ($event.detail.name === 'procedencia_service_id') { handleProcedenciaServiceSelected($event.detail.value); }"
                                @autocomplete-cleared.window="if ($event.detail.name === 'procedencia_service_id') { handleProcedenciaServiceSelected(''); }"
                            >
                                <label for="procedencia_service_id" class="mb-2 block text-sm font-medium text-slate-700">Servicio</label>
                                <x-autocomplete
                                    name="procedencia_service_id"
                                    endpoint="/api/search/services"
                                    placeholder="Buscar servicio"
                                    :value="old('procedencia_service_id')"
                                    :label="old('procedencia_service_id_label')"
                                    :params="['institution_id' => old('procedencia_institution_id')]"
                                />
                                <input type="hidden" :value="procedencia.serviceId" aria-hidden="true">
                                @error('procedencia_service_id')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div
                                @autocomplete-selected.window="if ($event.detail.name === 'procedencia_office_id') { procedencia.officeId = String($event.detail.value); }"
                                @autocomplete-cleared.window="if ($event.detail.name === 'procedencia_office_id') { procedencia.officeId = ''; }"
                            >
                                <label for="procedencia_office_id" class="mb-2 block text-sm font-medium text-slate-700">Oficina</label>
                                <x-autocomplete
                                    name="procedencia_office_id"
                                    endpoint="/api/search/offices"
                                    placeholder="Buscar oficina"
                                    :value="old('procedencia_office_id')"
                                    :label="old('procedencia_office_id_label')"
                                    :params="['service_id' => old('procedencia_service_id'), 'institution_id' => old('procedencia_institution_id')]"
                                />
                                <input type="hidden" :value="procedencia.officeId" aria-hidden="true">
                                @error('procedencia_office_id')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="procedencia_hospital" class="mb-2 block text-sm font-medium text-slate-700">Institucion libre</label>
                                <input id="procedencia_hospital" name="procedencia_hospital" type="text" value="{{ old('procedencia_hospital') }}" class="app-input" placeholder="Use si no aplica la estructura institucional">
                                @error('procedencia_hospital')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="procedencia_libre" class="mb-2 block text-sm font-medium text-slate-700">Referencia libre</label>
                                <input id="procedencia_libre" name="procedencia_libre" type="text" value="{{ old('procedencia_libre') }}" class="app-input" placeholder="Servicio, ambulancia, mensajeria u otra referencia">
                                @error('procedencia_libre')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-collapsible-panel>

                <x-collapsible-panel
                    title="Observaciones / accesorios"
                    eyebrow="Notas"
                    icon="clipboard-list"
                    summary="Accesorios entregados, estado fisico y observaciones para comprobante o uso interno."
                    :force-open="$notesErrors"
                    persist-key="mesa-tecnica-create-v2.observaciones"
                    class="mt-operational-panel rounded-[1.5rem]"
                    :status-label="$notesStatus['label']"
                    :status-class="$notesStatus['class']"
                    :status-hint="$notesStatus['hint']"
                >
                    <div class="space-y-4">
                        <div>
                            <label for="accesorios_entregados" class="mb-2 block text-sm font-medium text-slate-700">Accesorios entregados</label>
                            <textarea id="accesorios_entregados" name="accesorios_entregados" rows="3" class="app-input" placeholder="Cables, fuente, bateria, mouse, sonda, etc.">{{ old('accesorios_entregados') }}</textarea>
                            @error('accesorios_entregados')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="estado_fisico_inicial" class="mb-2 block text-sm font-medium text-slate-700">Estado fisico inicial</label>
                            <textarea id="estado_fisico_inicial" name="estado_fisico_inicial" rows="3" class="app-input" placeholder="Golpes, faltantes, carcasa, roturas, etc.">{{ old('estado_fisico_inicial') }}</textarea>
                            @error('estado_fisico_inicial')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="observaciones_recepcion" class="mb-2 block text-sm font-medium text-slate-700">Obs. comprobante</label>
                                <textarea id="observaciones_recepcion" name="observaciones_recepcion" rows="3" class="app-input" placeholder="Dato visible para la impresion">{{ old('observaciones_recepcion') }}</textarea>
                                @error('observaciones_recepcion')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="observaciones_internas" class="mb-2 block text-sm font-medium text-slate-700">Obs. internas</label>
                                <textarea id="observaciones_internas" name="observaciones_internas" rows="3" class="app-input" placeholder="Notas internas">{{ old('observaciones_internas') }}</textarea>
                                @error('observaciones_internas')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-collapsible-panel>
            </div>

            <div class="mt-sticky-submit">
                <div class="mt-sticky-submit-bar">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Accion principal</p>
                            <p class="mt-1 text-sm font-semibold text-slate-950" x-text="submitSummary()"></p>
                            <p class="mt-1 text-xs text-slate-500" x-text="mode === 'existente' ? 'El ticket se vincula al equipo seleccionado sin duplicar inventario.' : (incorporate ? 'Se registra el ticket y el alta patrimonial en una sola pasada.' : 'El ticket queda temporal sin alterar la ubicacion patrimonial.')"></p>
                        </div>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row">
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="btn btn-slate">Cancelar</a>
                            <button type="submit" class="btn btn-indigo mt-primary-action gap-2">
                                <x-icon name="check-circle-2" class="h-4 w-4" />
                                <span x-text="submitLabel()"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function recepcionTecnicaCreate(config) {
            return {
                mode: config.mode || 'nuevo',
                incorporate: Boolean(config.incorporate),
                selectedEquipo: config.selectedEquipo || null,
                activeInstitutionId: config.activeInstitutionId,
                operatesGlobally: Boolean(config.operatesGlobally),
                procedencia: {
                    institutionId: String(config.procedencia?.institutionId ?? ''),
                    serviceId: String(config.procedencia?.serviceId ?? ''),
                    officeId: String(config.procedencia?.officeId ?? ''),
                },
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
                    message: 'Escriba un identificador para buscar.',
                    controller: null,
                },
                endpoints: config.endpoints,

                init() {
                    this.dispatchAutocompleteParams();
                },

                setMode(nextMode) {
                    this.mode = nextMode;

                    if (nextMode === 'existente') {
                        this.incorporate = false;

                        return;
                    }

                    this.clearSelectedEquipo();
                },

                clearSelectedEquipo() {
                    this.selectedEquipo = null;
                },

                selectEquipo(item) {
                    this.selectedEquipo = item;
                    this.search.results = [];
                    this.search.message = 'Equipo listo.';
                },

                submitLabel() {
                    return this.mode === 'nuevo' && this.incorporate
                        ? 'Registrar ingreso + alta'
                        : 'Registrar ticket tecnico';
                },

                submitSummary() {
                    if (this.mode === 'existente') {
                        return 'Registrar ticket tecnico para un equipo existente';
                    }

                    return this.incorporate
                        ? 'Registrar ticket tecnico y alta en inventario'
                        : 'Registrar ticket tecnico para un equipo nuevo';
                },

                async searchEquipos() {
                    const query = this.normalizeEquipmentQuery(this.search.query);

                    if (query === '') {
                        this.search.results = [];
                        this.search.message = 'Escriba un identificador para buscar.';
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
                        const scopedInstitutionId = this.scopeInstitutionId();
                        if (scopedInstitutionId) {
                            url.searchParams.set('institution_id', String(scopedInstitutionId));
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
                        if (error?.name === 'AbortError') {
                            return;
                        }

                        this.search.results = [];
                        this.search.message = 'No se pudo realizar la busqueda. Intente nuevamente.';
                    } finally {
                        this.search.loading = false;
                    }
                },

                normalizeEquipmentQuery(value) {
                    const text = String(value || '').trim();

                    if (text === '') {
                        return '';
                    }

                    const uuidMatch = text.match(/[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/i);

                    return uuidMatch ? uuidMatch[0] : text;
                },

                scopeInstitutionId() {
                    if (this.operatesGlobally) {
                        return null;
                    }

                    return this.activeInstitutionId;
                },

                dispatchAutocompleteParams() {
                    window.dispatchEvent(new CustomEvent('autocomplete-set-params', {
                        detail: { name: 'procedencia_service_id', params: { institution_id: this.procedencia.institutionId } },
                    }));
                    window.dispatchEvent(new CustomEvent('autocomplete-set-params', {
                        detail: {
                            name: 'procedencia_office_id',
                            params: {
                                institution_id: this.procedencia.institutionId,
                                service_id: this.procedencia.serviceId,
                            },
                        },
                    }));
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

                handleProcedenciaInstitutionSelected(value) {
                    const nextValue = String(value || '');
                    if (nextValue === this.procedencia.institutionId) return;
                    this.procedencia.institutionId = nextValue;
                    this.procedencia.serviceId = '';
                    this.procedencia.officeId = '';
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'procedencia_service_id' } }));
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'procedencia_office_id' } }));
                    this.dispatchAutocompleteParams();
                },

                handleProcedenciaServiceSelected(value) {
                    const nextValue = String(value || '');
                    if (nextValue === this.procedencia.serviceId) return;
                    this.procedencia.serviceId = nextValue;
                    this.procedencia.officeId = '';
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'procedencia_office_id' } }));
                    this.dispatchAutocompleteParams();
                },

                handleDestinoInstitutionSelected(value) {
                    const nextValue = String(value || '');
                    if (nextValue === this.destino.institutionId) return;
                    this.destino.institutionId = nextValue;
                    this.destino.serviceId = '';
                    this.destino.officeId = '';
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'service_id' } }));
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'oficina_id' } }));
                    this.dispatchAutocompleteParams();
                },

                handleDestinoServiceSelected(value) {
                    const nextValue = String(value || '');
                    if (nextValue === this.destino.serviceId) return;
                    this.destino.serviceId = nextValue;
                    this.destino.officeId = '';
                    window.dispatchEvent(new CustomEvent('autocomplete-reset', { detail: { name: 'oficina_id' } }));
                    this.dispatchAutocompleteParams();
                },
            };
        }
    </script>
@endsection
