@extends('layouts.app')

@section('title', 'Nueva acta de trazabilidad')
@section('header', 'Actas de trazabilidad')

@section('content')
<div
    x-data="actaWorkspace(
        @js([
            'tipo' => old('tipo'),
            'fecha' => old('fecha', now()->toDateString()),
            'institution_destino_id' => old('institution_destino_id', old('institucion_destino')),
            'service_destino_id' => old('service_destino_id', old('servicio_destino')),
            'office_destino_id' => old('office_destino_id', old('oficina_destino')),
            'receptor_nombre' => old('receptor_nombre'),
            'receptor_dni' => old('receptor_dni'),
            'receptor_cargo' => old('receptor_cargo'),
            'receptor_dependencia' => old('receptor_dependencia'),
            'motivo_baja' => old('motivo_baja'),
            'observaciones' => old('observaciones'),
        ]),
        @js([
            'tipoLabels' => $tipoLabels,
            'searchUrl' => $searchEndpoints['actaEquipos'],
            'servicesUrl' => $searchEndpoints['services'],
            'officesUrl' => $searchEndpoints['offices'],
            'originInstitutions' => $originInstitutions,
            'destinationInstitutions' => $destinationInstitutions,
            'activeInstitutionId' => $activeInstitutionId,
            'tipoEquipoOptions' => $tipoEquipoOptions,
            'estadoOptions' => $estadoOptions,
        ]),
        @js($oldSelectedEquipos)
    )"
    class="space-y-6"
>
    <form method="POST" action="{{ route('actas.store') }}" class="space-y-6">
        @csrf

        <section class="card space-y-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700">Trazabilidad de equipos</span>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Configure el acta y agregue los equipos</h2>
                        <p class="mt-1 max-w-3xl text-sm text-slate-600">
                            Primero elija el tipo de acta. Despues busque equipos por codigo interno, serie, patrimonial, UUID, MAC, marca, modelo o ubicacion y agreguelos al listado.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:w-[340px]">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" x-text="tipo === 'prestamo' ? 'Fecha de prestamo' : 'Fecha del acta'"></label>
                        <input type="date" name="fecha" x-model="fecha" class="mt-1 w-full rounded-xl border-slate-300" required>
                        @error('fecha') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seleccionados</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900" x-text="selected.length"></p>
                        <p class="text-sm text-slate-500">equipos listos para el acta</p>
                    </div>
                </div>
            </div>

            <div>
                <p class="mb-3 text-sm font-medium text-slate-700">Tipo de acta</p>
                <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                    @foreach ($tipos as $tipo)
                        <button
                            type="button"
                            @click="selectTipo('{{ $tipo }}')"
                            :class="tipo === '{{ $tipo }}'
                                ? 'border-primary-600 bg-primary-50 text-primary-800 shadow-sm'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-primary-200 hover:bg-slate-50'"
                            class="min-h-[72px] rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Acta</span>
                            <span class="mt-2 block text-sm font-semibold">{{ $tipoLabels[$tipo] ?? strtoupper($tipo) }}</span>
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="tipo" :value="tipo">
                @error('tipo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(340px,0.9fr)]">
            <div class="space-y-6">
                <section class="card space-y-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-1">
                            <h3 class="text-lg font-semibold text-slate-900">Buscar equipos</h3>
                            <p class="text-sm text-slate-600">Escriba y agregue. No se cargan listados masivos hasta que exista un criterio de busqueda.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="filtersOpen = !filtersOpen"
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700"
                            >
                                <span>Filtros avanzados</span>
                                <span
                                    x-show="activeFilterCount()"
                                    x-cloak
                                    class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-slate-100 px-2 text-xs font-bold text-slate-700"
                                    x-text="activeFilterCount()"
                                ></span>
                            </button>
                            <button type="button" class="inline-flex min-h-[44px] items-center rounded-xl border border-dashed border-slate-300 px-4 text-sm font-semibold text-slate-500" disabled>
                                Escaneo QR proximamente
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto]">
                        <div>
                            <label for="buscar-equipo" class="mb-2 block text-sm font-medium text-slate-700">Buscador principal</label>
                            <input
                                id="buscar-equipo"
                                type="text"
                                x-model="query"
                                @input="onQueryInput()"
                                @keydown.enter.prevent="search()"
                                class="min-h-[56px] w-full rounded-2xl border-slate-300 px-4 text-base"
                                placeholder="Codigo interno, serie, patrimonial, UUID, MAC, marca, modelo, tipo o texto relacionado"
                                autocomplete="off"
                            >
                        </div>

                        <button type="button" @click="search()" class="min-h-[56px] rounded-2xl bg-primary-600 px-5 text-sm font-semibold text-white">
                            Buscar
                        </button>

                        <button
                            type="button"
                            @click="clearSearchWorkspace()"
                            class="min-h-[56px] rounded-2xl border border-slate-300 px-5 text-sm font-semibold text-slate-700"
                        >
                            Limpiar
                        </button>
                    </div>

                    <p class="text-sm text-slate-500" x-text="searchStatusMessage()"></p>

                    <div x-show="filtersOpen" x-cloak class="app-subcard p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Filtros avanzados</h4>
                                <p class="text-sm text-slate-500">El origen se inicializa con la institucion activa de la sesion. Use solo los filtros necesarios.</p>
                            </div>
                            <button type="button" @click="clearFilters()" class="text-sm font-semibold text-primary-700">Limpiar filtros</button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Institucion de origen</label>
                                <select
                                    x-model="filters.institution_id"
                                    @change="onOriginInstitutionChange()"
                                    class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300"
                                >
                                    <template x-for="institution in originInstitutions" :key="`origin-inst-${institution.id}`">
                                        <option :value="String(institution.id)" x-text="institution.nombre"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Servicio</label>
                                <select
                                    x-model="filters.service_id"
                                    @change="onOriginServiceChange()"
                                    :disabled="!filters.institution_id || isLoadingOriginServices"
                                    class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300 disabled:bg-slate-100 disabled:text-slate-500"
                                >
                                    <option value="">Todos</option>
                                    <template x-for="service in originServiceOptions" :key="`origin-service-${service.id}`">
                                        <option :value="String(service.id)" x-text="service.label"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Oficina</label>
                                <select
                                    x-model="filters.office_id"
                                    @change="onFilterChange()"
                                    :disabled="!filters.service_id || isLoadingOriginOffices"
                                    class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300 disabled:bg-slate-100 disabled:text-slate-500"
                                >
                                    <option value="">Todas</option>
                                    <template x-for="office in originOfficeOptions" :key="`origin-office-${office.id}`">
                                        <option :value="String(office.id)" x-text="office.label"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Tipo de equipo</label>
                                <select
                                    x-model="filters.tipo_equipo_id"
                                    @change="onFilterChange()"
                                    class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300"
                                >
                                    <option value="">Todos</option>
                                    <template x-for="tipoEquipo in tipoEquipoOptions" :key="`tipo-equipo-${tipoEquipo.id}`">
                                        <option :value="String(tipoEquipo.id)" x-text="tipoEquipo.nombre"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Estado</label>
                                <select
                                    x-model="filters.estado"
                                    @change="onFilterChange()"
                                    class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300"
                                >
                                    <option value="">Todos excepto baja</option>
                                    <template x-for="estado in estadoOptions" :key="`estado-${estado.value}`">
                                        <option :value="estado.value" x-text="estado.label"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div x-show="activeFilterBadges().length" x-cloak class="flex flex-wrap gap-2">
                        <template x-for="badge in activeFilterBadges()" :key="badge">
                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="badge"></span>
                        </template>
                    </div>

                    <div class="app-table-panel">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Resultados</h4>
                                    <p class="text-sm text-slate-500" x-text="resultSummaryText()"></p>
                                </div>
                                <div x-show="isSearching" x-cloak class="text-sm font-medium text-primary-700">Buscando...</div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="app-table min-w-[64rem]">
                                <thead>
                                    <tr>
                                        <th>Equipo</th>
                                        <th>Codigo interno</th>
                                        <th>Serie</th>
                                        <th>Patrimonial</th>
                                        <th>Ubicacion actual</th>
                                        <th>Estado</th>
                                        <th class="text-right">Accion</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    <template x-if="!results.length">
                                        <tr>
                                            <td colspan="7" class="px-4 py-12 text-center">
                                                <div class="mx-auto max-w-lg space-y-2">
                                                    <p class="text-base font-semibold text-slate-900" x-text="emptyStateTitle()"></p>
                                                    <p class="text-sm text-slate-500" x-text="emptyStateDescription()"></p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="item in results" :key="`result-${item.id}`">
                                        <tr class="align-top">
                                            <td>
                                                <div class="min-w-[14rem]">
                                                    <div class="font-semibold text-slate-900" x-text="item.tipo || item.label"></div>
                                                    <div class="mt-1 app-cell-wrap text-slate-600" x-text="joinLabel([item.marca, item.modelo]) || '-'"></div>
                                                </div>
                                            </td>
                                            <td class="app-cell-nowrap font-mono text-xs font-semibold tracking-[0.14em] text-slate-900" x-text="item.codigo_interno || '-'"></td>
                                            <td class="app-cell-nowrap" x-text="item.numero_serie || '-'"></td>
                                            <td class="app-cell-nowrap" x-text="item.bien_patrimonial || '-'"></td>
                                            <td>
                                                <div class="min-w-[16rem]">
                                                    <div class="app-cell-wrap text-slate-900" x-text="item.institucion || '-'"></div>
                                                    <div class="app-cell-wrap text-slate-600" x-text="item.servicio || '-'"></div>
                                                    <div class="app-cell-wrap text-slate-500" x-text="item.oficina || '-'"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.estado_label || humanizeEstado(item.estado)"></span>
                                            </td>
                                            <td class="app-cell-nowrap text-right">
                                                <button
                                                    type="button"
                                                    @click="addSelected(item)"
                                                    :disabled="isSelected(item.id)"
                                                    :class="isSelected(item.id)
                                                        ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                                        : 'border-primary-200 bg-primary-50 text-primary-700 hover:border-primary-300'"
                                                    class="inline-flex min-h-[40px] items-center justify-center whitespace-nowrap rounded-xl border px-4 text-sm font-semibold"
                                                >
                                                    <span x-text="isSelected(item.id) ? 'Agregado' : 'Agregar'"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="searchMeta.has_more" x-cloak class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-center">
                            <button
                                type="button"
                                @click="loadMore()"
                                :disabled="isSearching"
                                class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Cargar mas resultados
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="card space-y-4 xl:sticky xl:top-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Equipos seleccionados</h3>
                            <p class="text-sm text-slate-600">Revise lo agregado sin perder el contexto de busqueda.</p>
                        </div>
                        <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl bg-primary-50 px-3 text-sm font-bold text-primary-700" x-text="selected.length"></span>
                    </div>

                    <div class="app-subcard p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Destino del acta</p>
                        <div class="mt-3 space-y-1 text-sm text-slate-700">
                            <p class="font-medium" x-text="getDestinoPreview().institucion"></p>
                            <p x-text="getDestinoPreview().servicio"></p>
                            <p x-text="getDestinoPreview().oficina"></p>
                        </div>
                    </div>

                    <div x-show="selectedOriginGroups().length" x-cloak class="app-subcard p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Origen de los equipos</p>
                                <p class="text-sm text-slate-500">Cada equipo conserva su ubicacion de origen para trazabilidad.</p>
                            </div>
                            <span
                                x-show="hasMultipleOriginInstitutions()"
                                x-cloak
                                class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                            >
                                Origen multiple
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="group in selectedOriginGroups()" :key="group.name">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    <span x-text="`${group.name} (${group.count})`"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <template x-if="!selected.length">
                        <div class="app-subcard border-dashed px-4 py-6 text-center">
                            <p class="text-base font-semibold text-slate-900">Todavia no agrego equipos</p>
                            <p class="mt-2 text-sm text-slate-500">Busque, verifique la fila y use el boton Agregar. El listado quedara aqui.</p>
                        </div>
                    </template>

                    <div class="space-y-3" x-show="selected.length" x-cloak>
                        <template x-for="(item, index) in selected" :key="`selected-${item.id}`">
                            <article class="app-subcard p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900" x-text="item.label || item.tipo"></p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            Codigo interno:
                                            <span class="font-mono font-semibold tracking-[0.14em] text-slate-900" x-text="item.codigo_interno || '-'"></span>
                                        </p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            Serie:
                                            <span class="font-medium text-slate-900" x-text="item.numero_serie || '-'"></span>
                                        </p>
                                        <p class="text-sm text-slate-600">
                                            Patrimonial:
                                            <span class="font-medium text-slate-900" x-text="item.bien_patrimonial || '-'"></span>
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="removeSelected(index)"
                                        class="inline-flex min-h-[40px] items-center rounded-xl border border-red-200 px-3 text-sm font-semibold text-red-600"
                                    >
                                        Quitar
                                    </button>
                                </div>

                                <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                    <p class="font-medium text-slate-900" x-text="item.institucion || '-'"></p>
                                    <p x-text="item.servicio || '-'"></p>
                                    <p x-text="item.oficina || '-'"></p>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Accesorios / observacion breve</label>
                                    <input type="text" x-model="item.accesorios" class="mt-1 min-h-[44px] w-full rounded-xl border-slate-300" placeholder="Ej.: fuente, mouse, maletin">
                                </div>
                            </article>
                        </template>
                    </div>

                    @error('equipos') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    @error('equipos.*.equipo_id') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    @error('equipos.*.cantidad') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </section>
            </aside>
        </section>

        <section class="card space-y-5">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Datos complementarios del acta</h3>
                <p class="mt-1 text-sm text-slate-600">Complete solo los datos que correspondan al tipo de acta seleccionado.</p>
            </div>

            <div x-show="tipo === 'entrega' || tipo === 'traslado'" x-cloak class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Destino</h4>
                        <p class="text-sm text-slate-500">Seleccione la ubicacion destino comun para los equipos del acta.</p>
                    </div>
                    <span x-show="tipo === 'entrega'" x-cloak class="inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Entrega</span>
                    <span x-show="tipo === 'traslado'" x-cloak class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Traslado</span>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Institucion destino</label>
                        <select
                            name="institution_destino_id"
                            x-model="institution_destino_id"
                            @change="onDestinationInstitutionChange()"
                            class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300"
                        >
                            <option value="">Seleccionar</option>
                            <template x-for="institution in destinationInstitutions" :key="`destination-inst-${institution.id}`">
                                <option :value="String(institution.id)" x-text="institution.nombre"></option>
                            </template>
                        </select>
                        @error('institution_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Servicio destino</label>
                        <select
                            name="service_destino_id"
                            x-model="service_destino_id"
                            @change="onDestinationServiceChange()"
                            :disabled="!institution_destino_id || isLoadingDestinationServices"
                            class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300 disabled:bg-slate-100 disabled:text-slate-500"
                        >
                            <option value="">Seleccionar</option>
                            <template x-for="service in destinationServiceOptions" :key="`destination-service-${service.id}`">
                                <option :value="String(service.id)" x-text="service.label"></option>
                            </template>
                        </select>
                        @error('service_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Oficina destino</label>
                        <select
                            name="office_destino_id"
                            x-model="office_destino_id"
                            :disabled="!service_destino_id || isLoadingDestinationOffices"
                            class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300 disabled:bg-slate-100 disabled:text-slate-500"
                        >
                            <option value="">Seleccionar</option>
                            <template x-for="office in destinationOfficeOptions" :key="`destination-office-${office.id}`">
                                <option :value="String(office.id)" x-text="office.label"></option>
                            </template>
                        </select>
                        @error('office_destino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div x-show="tipo === 'entrega' || tipo === 'prestamo'" x-cloak class="space-y-4">
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">Receptor</h4>
                    <p class="text-sm text-slate-500">Datos de la persona o dependencia que recibe el equipamiento.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nombre y apellido</label>
                        <input type="text" name="receptor_nombre" x-model="receptor_nombre" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300">
                        @error('receptor_nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">DNI</label>
                        <input type="text" name="receptor_dni" x-model="receptor_dni" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300">
                        @error('receptor_dni') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Cargo</label>
                        <input type="text" name="receptor_cargo" x-model="receptor_cargo" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300">
                        @error('receptor_cargo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Dependencia</label>
                        <input type="text" name="receptor_dependencia" x-model="receptor_dependencia" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300">
                        @error('receptor_dependencia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div x-show="tipo === 'baja'" x-cloak>
                <label class="block text-sm font-medium text-slate-700">Motivo de baja</label>
                <input type="text" name="motivo_baja" x-model="motivo_baja" class="mt-1 min-h-[48px] w-full rounded-xl border-slate-300" placeholder="Detalle breve del motivo">
                @error('motivo_baja') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Observaciones</label>
                <textarea name="observaciones" x-model="observaciones" rows="4" class="mt-1 w-full rounded-2xl border-slate-300" placeholder="Informacion adicional para el acta"></textarea>
                @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <template x-for="(item, index) in selected" :key="`hidden-selected-${item.id}`">
            <div>
                <input type="hidden" :name="`equipos[${index}][equipo_id]`" :value="item.id">
                <input type="hidden" :name="`equipos[${index}][cantidad]`" value="1">
                <input type="hidden" :name="`equipos[${index}][accesorios]`" :value="item.accesorios || ''">
            </div>
        </template>

        <section class="card">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Generar acta</h3>
                    <p class="text-sm text-slate-600">Se mantendran la trazabilidad, los permisos por institucion y la logica actual de generacion del PDF.</p>
                </div>

                <button
                    type="submit"
                    :disabled="!tipo || !selected.length"
                    class="min-h-[56px] rounded-2xl bg-primary-600 px-6 text-base font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    Generar acta y PDF
                </button>
            </div>
        </section>
    </form>
</div>

<script>
    function actaWorkspace(initial, config, oldSelected) {
        return {
            tipo: initial.tipo || '',
            fecha: initial.fecha || '',
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
            filtersOpen: false,
            activeInstitutionId: config.activeInstitutionId ? String(config.activeInstitutionId) : '',
            filters: {
                institution_id: config.activeInstitutionId ? String(config.activeInstitutionId) : '',
                service_id: '',
                office_id: '',
                tipo_equipo_id: '',
                estado: '',
            },
            results: [],
            selected: Array.isArray(oldSelected)
                ? oldSelected.map((item) => ({
                    ...item,
                    institucion_id: item.institucion_id ? Number(item.institucion_id) : null,
                    servicio_id: item.servicio_id ? Number(item.servicio_id) : null,
                    oficina_id: item.oficina_id ? Number(item.oficina_id) : null,
                    tipo_equipo_id: item.tipo_equipo_id ? Number(item.tipo_equipo_id) : null,
                    accesorios: item.accesorios || '',
                }))
                : [],
            searchMeta: {
                searched: false,
                message: 'Escriba al menos 3 caracteres o use filtros avanzados para comenzar la busqueda.',
                page: 1,
                per_page: 25,
                has_more: false,
                next_page: null,
            },
            isSearching: false,
            originInstitutions: Array.isArray(config.originInstitutions) ? config.originInstitutions : [],
            destinationInstitutions: Array.isArray(config.destinationInstitutions) ? config.destinationInstitutions : [],
            tipoEquipoOptions: Array.isArray(config.tipoEquipoOptions) ? config.tipoEquipoOptions : [],
            estadoOptions: Array.isArray(config.estadoOptions) ? config.estadoOptions : [],
            tipoLabels: config.tipoLabels || {},
            searchUrl: config.searchUrl,
            servicesUrl: config.servicesUrl,
            officesUrl: config.officesUrl,
            originServiceOptions: [],
            originOfficeOptions: [],
            destinationServiceOptions: [],
            destinationOfficeOptions: [],
            isLoadingOriginServices: false,
            isLoadingOriginOffices: false,
            isLoadingDestinationServices: false,
            isLoadingDestinationOffices: false,
            searchTimer: null,
            searchController: null,

            async init() {
                if (['entrega', 'traslado'].includes(this.tipo) && this.institution_destino_id) {
                    await this.loadServices('destination', this.institution_destino_id, false);

                    if (this.service_destino_id) {
                        const hasService = this.destinationServiceOptions.some((service) => String(service.id) === String(this.service_destino_id));
                        this.service_destino_id = hasService ? String(this.service_destino_id) : '';
                    }

                    if (this.institution_destino_id && this.service_destino_id && this.office_destino_id) {
                        await this.loadOffices('destination', this.institution_destino_id, this.service_destino_id, false);
                        const hasOffice = this.destinationOfficeOptions.some((office) => String(office.id) === String(this.office_destino_id));
                        this.office_destino_id = hasOffice ? String(this.office_destino_id) : '';
                    }
                }

                if (this.filters.institution_id) {
                    await this.loadServices('origin', this.filters.institution_id, false);
                }
            },

            selectTipo(tipo) {
                this.tipo = tipo;

                if (!['entrega', 'traslado'].includes(tipo)) {
                    this.institution_destino_id = '';
                    this.service_destino_id = '';
                    this.office_destino_id = '';
                    this.destinationServiceOptions = [];
                    this.destinationOfficeOptions = [];
                }
            },

            onQueryInput() {
                const trimmed = this.query.trim();

                if (trimmed.length === 0 && !this.hasActiveFilters()) {
                    this.resetSearch('Escriba al menos 3 caracteres o use filtros avanzados para comenzar la busqueda.');
                    return;
                }

                if (trimmed.length > 0 && trimmed.length < 3 && !this.hasActiveFilters()) {
                    this.resetSearch('Escriba al menos 3 caracteres para buscar por texto.');
                    return;
                }

                this.queueSearch();
            },

            onFilterChange() {
                if (!this.isCriteriaReady()) {
                    this.resetSearch('Escriba al menos 3 caracteres o use filtros avanzados para comenzar la busqueda.');
                    return;
                }

                this.queueSearch();
            },

            async onOriginInstitutionChange() {
                this.filters.service_id = '';
                this.filters.office_id = '';
                this.originServiceOptions = [];
                this.originOfficeOptions = [];

                if (this.filters.institution_id) {
                    await this.loadServices('origin', this.filters.institution_id, true);
                }

                this.onFilterChange();
            },

            async onOriginServiceChange() {
                this.filters.office_id = '';
                this.originOfficeOptions = [];

                if (this.filters.institution_id && this.filters.service_id) {
                    await this.loadOffices('origin', this.filters.institution_id, this.filters.service_id, true);
                }

                this.onFilterChange();
            },

            async onDestinationInstitutionChange() {
                this.service_destino_id = '';
                this.office_destino_id = '';
                this.destinationServiceOptions = [];
                this.destinationOfficeOptions = [];

                if (this.institution_destino_id) {
                    await this.loadServices('destination', this.institution_destino_id, true);
                }
            },

            async onDestinationServiceChange() {
                this.office_destino_id = '';
                this.destinationOfficeOptions = [];

                if (this.institution_destino_id && this.service_destino_id) {
                    await this.loadOffices('destination', this.institution_destino_id, this.service_destino_id, true);
                }
            },

            async loadServices(scope, institutionId, clearOnError) {
                if (!institutionId) {
                    return;
                }

                const params = new URLSearchParams({
                    q: '...',
                    institution_id: institutionId,
                    acta_context: '1',
                });

                try {
                    if (scope === 'origin') {
                        this.isLoadingOriginServices = true;
                    } else {
                        this.isLoadingDestinationServices = true;
                    }

                    const response = await fetch(`${this.servicesUrl}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    if (!response.ok) {
                        throw new Error(`services_${response.status}`);
                    }

                    const payload = await response.json();
                    const options = Array.isArray(payload) ? payload : [];

                    if (scope === 'origin') {
                        this.originServiceOptions = options;
                    } else {
                        this.destinationServiceOptions = options;
                    }
                } catch (error) {
                    if (scope === 'origin') {
                        this.originServiceOptions = [];
                    } else {
                        this.destinationServiceOptions = [];
                    }

                    if (clearOnError) {
                        this.searchMeta.message = 'No fue posible cargar los servicios para la institucion seleccionada.';
                    }
                } finally {
                    if (scope === 'origin') {
                        this.isLoadingOriginServices = false;
                    } else {
                        this.isLoadingDestinationServices = false;
                    }
                }
            },

            async loadOffices(scope, institutionId, serviceId, clearOnError) {
                if (!institutionId || !serviceId) {
                    return;
                }

                const params = new URLSearchParams({
                    q: '...',
                    institution_id: institutionId,
                    service_id: serviceId,
                    acta_context: '1',
                });

                try {
                    if (scope === 'origin') {
                        this.isLoadingOriginOffices = true;
                    } else {
                        this.isLoadingDestinationOffices = true;
                    }

                    const response = await fetch(`${this.officesUrl}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    if (!response.ok) {
                        throw new Error(`offices_${response.status}`);
                    }

                    const payload = await response.json();
                    const options = Array.isArray(payload) ? payload : [];

                    if (scope === 'origin') {
                        this.originOfficeOptions = options;
                    } else {
                        this.destinationOfficeOptions = options;
                    }
                } catch (error) {
                    if (scope === 'origin') {
                        this.originOfficeOptions = [];
                    } else {
                        this.destinationOfficeOptions = [];
                    }

                    if (clearOnError) {
                        this.searchMeta.message = 'No fue posible cargar las oficinas para el servicio seleccionado.';
                    }
                } finally {
                    if (scope === 'origin') {
                        this.isLoadingOriginOffices = false;
                    } else {
                        this.isLoadingDestinationOffices = false;
                    }
                }
            },

            queueSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => this.search(), 250);
            },

            async search(options = {}) {
                const append = options.append === true;

                if (!this.isCriteriaReady()) {
                    this.resetSearch('Escriba al menos 3 caracteres o use filtros avanzados para comenzar la busqueda.');
                    return;
                }

                const page = append && this.searchMeta.next_page ? this.searchMeta.next_page : 1;
                const params = new URLSearchParams({ page: String(page) });
                const trimmed = this.query.trim();

                if (trimmed !== '') {
                    params.set('q', trimmed);
                }

                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) {
                        params.set(key, value);
                    }
                });

                this.abortCurrentSearch();
                this.isSearching = true;

                if (!append) {
                    this.results = [];
                }

                const controller = new AbortController();
                this.searchController = controller;

                try {
                    const response = await fetch(`${this.searchUrl}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                        signal: controller.signal,
                    });

                    if (response.status === 422) {
                        const payload = await response.json();
                        const firstError = Object.values(payload.errors || {}).reduce((carry, value) => carry.concat(value), [])[0]
                            || 'Revise los filtros seleccionados.';
                        this.results = append ? this.results : [];
                        this.searchMeta = {
                            searched: true,
                            message: firstError,
                            page,
                            per_page: 25,
                            has_more: false,
                            next_page: null,
                        };
                        return;
                    }

                    if (!response.ok) {
                        let backendMessage = 'Ocurrio un error al buscar equipos. Intente nuevamente.';

                        try {
                            const payload = await response.json();
                            backendMessage = payload.message || backendMessage;
                        } catch (parseError) {
                            backendMessage = response.status >= 500
                                ? 'Ocurrio un error al buscar equipos. Intente nuevamente.'
                                : 'No fue posible completar la busqueda.';
                        }

                        throw new Error(backendMessage);
                    }

                    const payload = await response.json();
                    const isLegacyArray = Array.isArray(payload);
                    const items = isLegacyArray
                        ? payload
                        : (Array.isArray(payload.items) ? payload.items : []);
                    const meta = isLegacyArray
                        ? {
                            searched: true,
                            message: items.length ? null : 'No encontramos equipos con los criterios indicados.',
                            page,
                            per_page: items.length,
                            has_more: false,
                            next_page: null,
                        }
                        : (payload.meta || {});

                    this.searchMeta = {
                        searched: Boolean(meta.searched),
                        message: meta.message || null,
                        page: Number(meta.page || page),
                        per_page: Number(meta.per_page || 25),
                        has_more: Boolean(meta.has_more),
                        next_page: meta.next_page ? Number(meta.next_page) : null,
                    };

                    if (append) {
                        this.mergeResults(items);
                    } else {
                        this.results = items;
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    if (!append) {
                        this.results = [];
                    }

                    this.searchMeta = {
                        searched: true,
                        message: error.message || 'No fue posible buscar equipos en este momento.',
                        page,
                        per_page: 25,
                        has_more: false,
                        next_page: null,
                    };
                } finally {
                    if (this.searchController === controller) {
                        this.searchController = null;
                    }

                    this.isSearching = false;
                }
            },

            loadMore() {
                if (!this.searchMeta.has_more || this.isSearching) {
                    return;
                }

                this.search({ append: true });
            },

            mergeResults(items) {
                const existingIds = new Set(this.results.map((item) => Number(item.id)));

                items.forEach((item) => {
                    const id = Number(item.id);

                    if (!existingIds.has(id)) {
                        existingIds.add(id);
                        this.results.push(item);
                    }
                });
            },

            addSelected(item) {
                if (this.isSelected(item.id)) {
                    return;
                }

                this.selected.unshift({
                    ...item,
                    institucion_id: item.institucion_id ? Number(item.institucion_id) : null,
                    servicio_id: item.servicio_id ? Number(item.servicio_id) : null,
                    oficina_id: item.oficina_id ? Number(item.oficina_id) : null,
                    tipo_equipo_id: item.tipo_equipo_id ? Number(item.tipo_equipo_id) : null,
                    accesorios: item.accesorios || '',
                });
            },

            removeSelected(index) {
                this.selected.splice(index, 1);
            },

            isSelected(id) {
                return this.selected.some((item) => Number(item.id) === Number(id));
            },

            async clearFilters() {
                this.filters = {
                    institution_id: this.activeInstitutionId,
                    service_id: '',
                    office_id: '',
                    tipo_equipo_id: '',
                    estado: '',
                };
                this.originServiceOptions = [];
                this.originOfficeOptions = [];

                if (this.filters.institution_id) {
                    await this.loadServices('origin', this.filters.institution_id, false);
                }

                this.onFilterChange();
            },

            clearSearchWorkspace() {
                this.query = '';
                this.clearFilters();
                this.resetSearch('Escriba al menos 3 caracteres o use filtros avanzados para comenzar la busqueda.');
            },

            resetSearch(message) {
                this.abortCurrentSearch();
                clearTimeout(this.searchTimer);
                this.isSearching = false;
                this.results = [];
                this.searchMeta = {
                    searched: false,
                    message,
                    page: 1,
                    per_page: 25,
                    has_more: false,
                    next_page: null,
                };
            },

            abortCurrentSearch() {
                if (this.searchController) {
                    this.searchController.abort();
                    this.searchController = null;
                }
            },

            isCriteriaReady() {
                return this.query.trim().length >= 3 || this.hasActiveFilters();
            },

            hasActiveFilters() {
                return Object.values(this.filters).some((value) => String(value || '').trim() !== '');
            },

            activeFilterCount() {
                return Object.values(this.filters).filter((value) => String(value || '').trim() !== '').length;
            },

            activeFilterBadges() {
                const badges = [];

                if (this.filters.institution_id) {
                    badges.push(this.findLabel(this.originInstitutions, this.filters.institution_id, 'nombre', 'id'));
                }

                if (this.filters.service_id) {
                    badges.push(this.findLabel(this.originServiceOptions, this.filters.service_id, 'label', 'id'));
                }

                if (this.filters.office_id) {
                    badges.push(this.findLabel(this.originOfficeOptions, this.filters.office_id, 'label', 'id'));
                }

                if (this.filters.tipo_equipo_id) {
                    badges.push(this.findLabel(this.tipoEquipoOptions, this.filters.tipo_equipo_id, 'nombre', 'id'));
                }

                if (this.filters.estado) {
                    badges.push(this.findLabel(this.estadoOptions, this.filters.estado, 'label', 'value'));
                }

                return badges.filter(Boolean);
            },

            searchStatusMessage() {
                if (this.isSearching) {
                    return 'Buscando equipos...';
                }

                return this.searchMeta.message || 'Use el buscador principal o los filtros avanzados.';
            },

            resultSummaryText() {
                if (!this.searchMeta.searched && !this.results.length) {
                    return 'La busqueda se ejecutara cuando escriba o aplique filtros.';
                }

                if (this.results.length === 0) {
                    return 'Sin resultados para mostrar.';
                }

                const base = this.results.length === 1
                    ? '1 equipo encontrado'
                    : `${this.results.length} equipos cargados`;

                return this.searchMeta.has_more ? `${base}. Hay mas coincidencias disponibles.` : base;
            },

            emptyStateTitle() {
                if (this.isSearching) {
                    return 'Buscando equipos';
                }

                return this.searchMeta.searched
                    ? 'No hay coincidencias'
                    : 'Ingrese un criterio para comenzar';
            },

            emptyStateDescription() {
                if (this.isSearching) {
                    return 'Espere un momento mientras consultamos el inventario.';
                }

                return this.searchMeta.message || 'Escriba al menos 3 caracteres o aplique filtros avanzados.';
            },

            selectedOriginGroups() {
                const groups = {};

                this.selected.forEach((item) => {
                    const name = item.institucion || 'Institucion sin identificar';

                    if (!groups[name]) {
                        groups[name] = { name, count: 0 };
                    }

                    groups[name].count += 1;
                });

                return Object.values(groups);
            },

            hasMultipleOriginInstitutions() {
                return this.selectedOriginGroups().length > 1;
            },

            getDestinoPreview() {
                if (!['entrega', 'traslado'].includes(this.tipo)) {
                    return {
                        institucion: 'Sin destino comun para este tipo de acta',
                        servicio: '-',
                        oficina: '-',
                    };
                }

                return {
                    institucion: this.findLabel(this.destinationInstitutions, this.institution_destino_id, 'nombre', 'id') || 'Institucion destino pendiente',
                    servicio: this.findLabel(this.destinationServiceOptions, this.service_destino_id, 'label', 'id') || 'Servicio destino pendiente',
                    oficina: this.findLabel(this.destinationOfficeOptions, this.office_destino_id, 'label', 'id') || 'Oficina destino pendiente',
                };
            },

            findLabel(items, value, labelField, valueField) {
                if (!value || !Array.isArray(items)) {
                    return '';
                }

                const found = items.find((item) => String(item[valueField]) === String(value));
                return found ? found[labelField] : '';
            },

            joinLabel(parts) {
                return Array.isArray(parts)
                    ? parts.filter((part) => String(part || '').trim() !== '').join(' / ')
                    : '';
            },

            humanizeEstado(estado) {
                return estado
                    ? String(estado).replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
                    : '-';
            },
        };
    }
</script>
@endsection

