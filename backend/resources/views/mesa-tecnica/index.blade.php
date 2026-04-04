@extends('layouts.app')

@section('title', 'Mesa tecnica')
@section('header', 'Mesa tecnica')

@section('content')
    @php
        $institutionOptions = $accessibleInstitutions
            ->map(fn ($institution): array => [
                'id' => (int) $institution->id,
                'name' => $institution->nombre,
                'scope_type' => $institution->scope_type,
            ])
            ->values();

        $oldDelivery = [
            'institution_destino_id' => old('institution_destino_id', ''),
            'service_destino_id' => old('service_destino_id', ''),
            'office_destino_id' => old('office_destino_id', ''),
        ];

        $result = is_array($mesaResult ?? null) ? $mesaResult : null;
        $operatesGlobally = (bool) ($authInstitutionContext['operatesGlobally'] ?? false);
        $activeInstitutionName = $authInstitutionContext['activeInstitution']->nombre ?? 'Sin institucion activa';
    @endphp

    <div
        x-data="mesaTecnicaPage({
            institutions: @js($institutionOptions),
            activeInstitutionId: @js($activeInstitutionId),
            operatesGlobally: @js($operatesGlobally),
            initialModal: @js($initialModal),
            restoredSelectedEquipo: @js($restoredSelectedEquipo),
            oldDelivery: @js($oldDelivery),
            endpoints: {
                equipos: @js(route('api.search.equipos')),
                services: @js(route('api.search.services')),
                offices: @js(route('api.search.offices')),
            },
            urls: {
                equiposBase: @js(url('/equipos')),
                mesaEtiquetasBase: @js(url('/mesa-tecnica/equipos')),
            },
        })"
        x-init="init()"
        @keydown.escape.window="closeModal()"
        class="space-y-5 lg:space-y-6"
    >
        <section class="app-panel overflow-hidden rounded-[2rem]">
            <div class="grid gap-5 px-5 py-5 sm:px-6 sm:py-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.75fr)]">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-slate-900 px-3 text-white">Mesa tecnica</span>
                        <span class="app-badge bg-slate-100 px-3 text-slate-700">Operaciones rapidas</span>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">
                            Recibir, entregar y ubicar equipos mas rapido.
                        </h2>
                        <p class="max-w-3xl text-sm leading-6 text-slate-600">
                            Flujo corto para trabajo de mostrador: busqueda por serie, QR o patrimonial, actas al instante y acceso rapido a ingresos tecnicos.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @include('mesa-tecnica.partials.action-card', [
                            'title' => 'Recibir',
                            'description' => 'Devuelve un equipo prestado.',
                            'meta' => 'Acta inmediata',
                            'icon' => 'download',
                            'tone' => 'emerald',
                            'click' => "openModal('recepcion')",
                        ])

                        @include('mesa-tecnica.partials.action-card', [
                            'title' => 'Entregar',
                            'description' => 'Registra destino y receptor.',
                            'meta' => 'Acta de entrega',
                            'icon' => 'upload',
                            'tone' => 'blue',
                            'click' => "openModal('entrega')",
                        ])

                        @include('mesa-tecnica.partials.action-card', [
                            'title' => 'Buscar',
                            'description' => 'Serie, QR o patrimonial.',
                            'meta' => 'Consulta rapida',
                            'icon' => 'search',
                            'tone' => 'slate',
                            'click' => "openModal('busqueda')",
                        ])

                        @include('mesa-tecnica.partials.action-card', [
                            'title' => 'Etiqueta',
                            'description' => 'Imprime o reimprime QR.',
                            'meta' => 'Salida lista',
                            'icon' => 'printer',
                            'tone' => 'amber',
                            'click' => "openModal('etiqueta')",
                        ])

                        @include('mesa-tecnica.partials.action-card', [
                            'title' => 'Ingreso tecnico',
                            'description' => 'Registra ticket y seguimiento.',
                            'meta' => 'Alta opcional',
                            'icon' => 'wrench',
                            'tone' => 'indigo',
                            'click' => "window.location.href='".route('mesa-tecnica.recepciones-tecnicas.index')."'",
                        ])

                        @include('mesa-tecnica.partials.action-card', [
                            'title' => 'Actas',
                            'description' => 'Revisa las ultimas emitidas.',
                            'meta' => 'PDF al instante',
                            'icon' => 'clipboard-list',
                            'tone' => 'slate',
                            'click' => "scrollToSection('mesa-tecnica-actas')",
                        ])
                    </div>
                </div>

                <aside class="space-y-3 rounded-[1.75rem] border border-slate-200 bg-slate-50/90 p-4 sm:p-5">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white">
                            <x-icon name="monitor" class="h-5 w-5" />
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contexto</p>
                            <h3 class="text-lg font-semibold tracking-tight text-slate-950">Turno tecnico</h3>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Institucion activa</p>
                        <p class="mt-2 text-base font-semibold text-slate-950">{{ $activeInstitutionName }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $operatesGlobally ? 'Alcance global habilitado.' : 'Busqueda limitada a esta institucion.' }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Recepcion</p>
                            <p class="mt-1 text-sm font-medium text-emerald-950">Solo para equipos prestados.</p>
                        </div>

                        <div class="rounded-2xl border border-blue-200 bg-blue-50/80 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Entrega</p>
                            <p class="mt-1 text-sm font-medium text-blue-950">Genera acta y destino final.</p>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50/85 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Etiqueta</p>
                            <p class="mt-1 text-sm font-medium text-amber-950">QR estable para imprimir otra vez.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        @if ($result)
            <section class="app-panel rounded-[2rem] border-emerald-200 bg-emerald-50/85 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white">
                            <x-icon name="check-circle-2" class="h-5 w-5" />
                        </div>

                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Ultima operacion</p>
                            <h3 class="text-lg font-semibold tracking-tight text-emerald-950">
                                {{ trim(($result['acta_tipo'] ?? 'Operacion lista').' '.($result['acta_codigo'] ?? '')) }}
                            </h3>
                            <p class="text-sm text-emerald-900">
                                {{ $result['equipo_reference'] ?? 'Equipo identificado correctamente.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if (! empty($result['acta_id']))
                            <a href="{{ route('actas.show', $result['acta_id']) }}" class="btn btn-slate">
                                <x-icon name="eye" class="h-4 w-4" />
                                Ver acta
                            </a>
                            <a href="{{ route('actas.download', $result['acta_id']) }}" class="btn btn-slate">
                                <x-icon name="download" class="h-4 w-4" />
                                PDF
                            </a>
                        @endif

                        @if (! empty($result['equipo_id']))
                            <a href="{{ route('mesa-tecnica.label', $result['equipo_id']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-amber">
                                <x-icon name="printer" class="h-4 w-4" />
                                Etiqueta
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <section id="mesa-tecnica-actas" class="grid gap-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
            <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Documentos</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Actas recientes</h3>
                    </div>

                    <a href="{{ route('actas.index') }}" class="text-sm font-semibold text-indigo-700 transition hover:text-indigo-800">
                        Ver todas
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentActas as $acta)
                        @php
                            $actaTypeClasses = match ($acta['tipo'] ?? '') {
                                \App\Models\Acta::TIPO_DEVOLUCION => 'bg-emerald-100 text-emerald-700',
                                \App\Models\Acta::TIPO_ENTREGA => 'bg-blue-100 text-blue-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp

                        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="app-badge px-3 {{ $actaTypeClasses }}">{{ $acta['tipo_label'] }}</span>
                                        <span class="app-badge bg-white px-3 text-slate-700">{{ strtoupper((string) $acta['status_label']) }}</span>
                                    </div>

                                    <div>
                                        <p class="text-base font-semibold text-slate-950">{{ $acta['codigo'] }}</p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            {{ $acta['institution'] }}
                                            @if ($acta['destination'])
                                                <span class="text-slate-400">-></span> {{ $acta['destination'] }}
                                            @endif
                                        </p>
                                    </div>

                                    <p class="text-xs text-slate-500">
                                        {{ $acta['fecha'] }} | {{ $acta['equipos_count'] }} equipo(s) | {{ $acta['creator'] }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('actas.show', $acta['id']) }}" class="btn btn-slate">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Ver
                                    </a>
                                    <a href="{{ route('actas.download', $acta['id']) }}" class="btn btn-slate">
                                        <x-icon name="download" class="h-4 w-4" />
                                        PDF
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                            No hay actas visibles en el alcance actual.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Actividad</p>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Movimientos recientes</h3>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentMovements as $movement)
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                    <x-icon name="clipboard-list" class="h-5 w-5" />
                                </div>

                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-900">{{ $movement['tipo_label'] }}</p>
                                        @if ($movement['acta_codigo'])
                                            <span class="app-badge bg-indigo-50 px-3 text-indigo-700">{{ $movement['acta_codigo'] }}</span>
                                        @endif
                                    </div>

                                    <p class="text-sm text-slate-800">{{ $movement['equipo_reference'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $movement['fecha'] }} | {{ $movement['usuario'] }}</p>

                                    @if (($movement['observacion'] ?? 'Sin observaciones') !== 'Sin observaciones')
                                        <p class="text-sm text-slate-600">{{ $movement['observacion'] }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-3 pt-1">
                                        @if ($movement['equipo_id'])
                                            <a href="{{ route('equipos.show', $movement['equipo_id']) }}" class="text-sm font-semibold text-indigo-700 transition hover:text-indigo-800">
                                                Ver equipo
                                            </a>
                                        @endif
                                        @if ($movement['acta_id'])
                                            <a href="{{ route('actas.show', $movement['acta_id']) }}" class="text-sm font-semibold text-slate-700 transition hover:text-slate-900">
                                                Ver acta
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                            No hay movimientos recientes.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        @component('mesa-tecnica.partials.modal-shell', [
            'name' => 'busqueda',
            'title' => 'Buscar',
            'subtitle' => 'Serie, patrimonial, codigo interno o QR.',
            'icon' => 'search',
            'tone' => 'slate',
            'maxWidth' => 'max-w-5xl',
        ])
            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                    <div>
                        <label for="mesa-busqueda" class="text-sm font-medium text-slate-700">Identificador</label>
                        <input
                            id="mesa-busqueda"
                            type="text"
                            x-model="lookup.query"
                            @input.debounce.350ms="search('lookup')"
                            @keydown.enter.prevent="search('lookup')"
                            class="form-control"
                            placeholder="GA-EQ-000123, serie, patrimonial o QR"
                            autocomplete="off"
                        >
                    </div>

                    <button type="button" class="btn btn-slate self-end" @click="search('lookup')">
                        <x-icon name="search" class="h-4 w-4" />
                        Buscar
                    </button>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/85 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800">Resultados</p>
                        <p class="text-xs text-slate-500" x-text="lookup.loading ? 'Buscando...' : lookup.message"></p>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <template x-for="item in lookup.results" :key="`lookup-${item.id}`">
                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="app-badge px-3" :class="equipmentStatusClass(item.estado)" x-text="item.estado_label || humanizeEstado(item.estado)"></span>
                                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.codigo_interno || 'Sin codigo'"></span>
                                        </div>

                                        <div>
                                            <p class="text-base font-semibold text-slate-950" x-text="item.label || item.tipo"></p>
                                            <p class="mt-1 text-sm text-slate-600" x-text="item.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                        </div>
                                    </div>

                                    <dl class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                                            <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Serie</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900" x-text="item.numero_serie || '-'"></dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                                            <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Patrimonial</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900" x-text="item.bien_patrimonial || '-'"></dd>
                                        </div>
                                    </dl>

                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-emerald" @click="openModal('recepcion', item)">
                                            <x-icon name="download" class="h-4 w-4" />
                                            Recibir
                                        </button>
                                        <button type="button" class="btn btn-blue" @click="openModal('entrega', item)">
                                            <x-icon name="upload" class="h-4 w-4" />
                                            Entregar
                                        </button>
                                        <a :href="labelUrl(item.id)" target="_blank" rel="noopener noreferrer" class="btn btn-amber">
                                            <x-icon name="printer" class="h-4 w-4" />
                                            Etiqueta
                                        </a>
                                        <a :href="equipoShowUrl(item.id)" class="btn btn-slate">
                                            <x-icon name="eye" class="h-4 w-4" />
                                            Ficha
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>

                    <div x-cloak x-show="!lookup.loading && lookup.results.length === 0" class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                        Escriba un dato visible para empezar.
                    </div>
                </div>
            </div>
        @endcomponent

        @component('mesa-tecnica.partials.modal-shell', [
            'name' => 'recepcion',
            'title' => 'Recibir',
            'subtitle' => 'Devolucion rapida de un equipo prestado.',
            'icon' => 'download',
            'tone' => 'emerald',
        ])
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.04fr)_minmax(20rem,0.96fr)]">
                <section class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/85 p-4">
                        <label for="recepcion-busqueda" class="text-sm font-medium text-slate-700">Buscar equipo</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <input
                                id="recepcion-busqueda"
                                type="text"
                                x-model="receive.query"
                                @input.debounce.350ms="search('receive')"
                                @keydown.enter.prevent="search('receive')"
                                class="form-control !mt-0"
                                placeholder="Codigo, serie, patrimonial o QR"
                                autocomplete="off"
                            >
                            <button type="button" class="btn btn-slate" @click="search('receive')">
                                <x-icon name="search" class="h-4 w-4" />
                                Buscar
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500" x-text="receive.loading ? 'Buscando equipo...' : receive.message"></p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="item in receive.results" :key="`receive-${item.id}`">
                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="app-badge px-3" :class="equipmentStatusClass(item.estado)" x-text="item.estado_label || humanizeEstado(item.estado)"></span>
                                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.codigo_interno || 'Sin codigo'"></span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-950" x-text="item.label || item.tipo"></p>
                                        <p class="text-sm text-slate-600" x-text="item.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                    </div>

                                    <button type="button" class="btn btn-slate" @click="selectEquipo('receive', item)">
                                        Seleccionar
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50/85 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Equipo</p>

                                <template x-if="receive.selected">
                                    <div class="mt-2 space-y-4">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="app-badge bg-white px-3 text-emerald-700" x-text="receive.selected.codigo_interno || 'Sin codigo'"></span>
                                                <span class="app-badge px-3" :class="equipmentStatusClass(receive.selected.estado)" x-text="receive.selected.estado_label || humanizeEstado(receive.selected.estado)"></span>
                                            </div>
                                            <p class="mt-3 text-lg font-semibold text-emerald-950" x-text="receive.selected.label || receive.selected.tipo"></p>
                                            <p class="mt-1 text-sm text-emerald-900" x-text="receive.selected.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                        </div>

                                        <dl class="grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-xl bg-white/85 px-3 py-3">
                                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">Serie</dt>
                                                <dd class="mt-1 text-sm font-medium text-emerald-950" x-text="receive.selected.numero_serie || '-'"></dd>
                                            </div>
                                            <div class="rounded-xl bg-white/85 px-3 py-3">
                                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">Patrimonial</dt>
                                                <dd class="mt-1 text-sm font-medium text-emerald-950" x-text="receive.selected.bien_patrimonial || '-'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </template>

                                <template x-if="!receive.selected">
                                    <p class="mt-2 text-sm text-emerald-900">Seleccione un equipo para continuar.</p>
                                </template>
                            </div>

                            <button
                                type="button"
                                x-cloak
                                x-show="receive.selected"
                                class="inline-flex rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100/70"
                                @click="clearSelected('receive')"
                            >
                                Cambiar
                            </button>
                        </div>
                    </div>

                    <div
                        x-cloak
                        x-show="receive.selected && !canReceive(receive.selected)"
                        class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900"
                    >
                        Solo puede recibirse rapido un equipo en estado Prestado.
                    </div>

                    <form method="POST" action="{{ route('mesa-tecnica.recepciones.store') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        @csrf
                        <input type="hidden" name="mesa_modal" value="recepcion">
                        <input type="hidden" name="equipo_id" :value="receive.selected ? receive.selected.id : ''">

                        <div class="grid gap-4">
                            <div>
                                <label for="recepcion-fecha" class="text-sm font-medium text-slate-700">Fecha</label>
                                <input id="recepcion-fecha" type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="form-control">
                                @error('fecha')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="recepcion-motivo" class="text-sm font-medium text-slate-700">Motivo</label>
                                <input id="recepcion-motivo" type="text" name="motivo" value="{{ old('motivo') }}" class="form-control" placeholder="Devolucion, recambio o referencia">
                                @error('motivo')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="recepcion-observaciones" class="text-sm font-medium text-slate-700">Notas</label>
                                <textarea id="recepcion-observaciones" name="observaciones" rows="3" class="form-control" placeholder="Dato util para el acta">{{ old('observaciones') }}</textarea>
                                @error('observaciones')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            @error('equipo_id')
                                <p class="form-error">{{ $message }}</p>
                            @enderror

                            <div class="flex flex-wrap justify-end gap-2 pt-1">
                                <button type="button" class="btn btn-slate" @click="closeModal()">Cancelar</button>
                                <button
                                    type="submit"
                                    class="btn btn-emerald"
                                    :disabled="!canReceive(receive.selected)"
                                    :class="!canReceive(receive.selected) ? 'cursor-not-allowed opacity-60' : ''"
                                >
                                    <x-icon name="download" class="h-4 w-4" />
                                    Recibir
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        @endcomponent

        @component('mesa-tecnica.partials.modal-shell', [
            'name' => 'entrega',
            'title' => 'Entregar',
            'subtitle' => 'Destino, receptor y acta en un paso.',
            'icon' => 'upload',
            'tone' => 'blue',
        ])
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.04fr)_minmax(20rem,0.96fr)]">
                <section class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/85 p-4">
                        <label for="entrega-busqueda" class="text-sm font-medium text-slate-700">Buscar equipo</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <input
                                id="entrega-busqueda"
                                type="text"
                                x-model="delivery.query"
                                @input.debounce.350ms="search('delivery')"
                                @keydown.enter.prevent="search('delivery')"
                                class="form-control !mt-0"
                                placeholder="Codigo, serie, patrimonial o QR"
                                autocomplete="off"
                            >
                            <button type="button" class="btn btn-slate" @click="search('delivery')">
                                <x-icon name="search" class="h-4 w-4" />
                                Buscar
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500" x-text="delivery.loading ? 'Buscando equipo...' : delivery.message"></p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="item in delivery.results" :key="`delivery-${item.id}`">
                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="app-badge px-3" :class="equipmentStatusClass(item.estado)" x-text="item.estado_label || humanizeEstado(item.estado)"></span>
                                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.codigo_interno || 'Sin codigo'"></span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-950" x-text="item.label || item.tipo"></p>
                                        <p class="text-sm text-slate-600" x-text="item.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                    </div>

                                    <button type="button" class="btn btn-slate" @click="selectEquipo('delivery', item)">
                                        Seleccionar
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-[1.75rem] border border-blue-200 bg-blue-50/85 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Equipo</p>

                                <template x-if="delivery.selected">
                                    <div class="mt-2 space-y-4">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="app-badge bg-white px-3 text-blue-700" x-text="delivery.selected.codigo_interno || 'Sin codigo'"></span>
                                                <span class="app-badge px-3" :class="equipmentStatusClass(delivery.selected.estado)" x-text="delivery.selected.estado_label || humanizeEstado(delivery.selected.estado)"></span>
                                            </div>
                                            <p class="mt-3 text-lg font-semibold text-blue-950" x-text="delivery.selected.label || delivery.selected.tipo"></p>
                                            <p class="mt-1 text-sm text-blue-900" x-text="delivery.selected.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                        </div>

                                        <dl class="grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-xl bg-white/85 px-3 py-3">
                                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700">Serie</dt>
                                                <dd class="mt-1 text-sm font-medium text-blue-950" x-text="delivery.selected.numero_serie || '-'"></dd>
                                            </div>
                                            <div class="rounded-xl bg-white/85 px-3 py-3">
                                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700">Patrimonial</dt>
                                                <dd class="mt-1 text-sm font-medium text-blue-950" x-text="delivery.selected.bien_patrimonial || '-'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </template>

                                <template x-if="!delivery.selected">
                                    <p class="mt-2 text-sm text-blue-900">Seleccione un equipo para generar la entrega.</p>
                                </template>
                            </div>

                            <button
                                type="button"
                                x-cloak
                                x-show="delivery.selected"
                                class="inline-flex rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100/70"
                                @click="clearSelected('delivery')"
                            >
                                Cambiar
                            </button>
                        </div>
                    </div>

                    <div
                        x-cloak
                        x-show="delivery.selected && !canDeliver(delivery.selected)"
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-900"
                    >
                        Un equipo dado de baja no puede entregarse.
                    </div>

                    <form method="POST" action="{{ route('mesa-tecnica.entregas.store') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        @csrf
                        <input type="hidden" name="mesa_modal" value="entrega">
                        <input type="hidden" name="equipo_id" :value="delivery.selected ? delivery.selected.id : ''">

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="entrega-institucion" class="text-sm font-medium text-slate-700">Institucion</label>
                                <select
                                    id="entrega-institucion"
                                    name="institution_destino_id"
                                    x-model="delivery.institutionId"
                                    @change="handleDeliveryInstitutionChange()"
                                    class="form-control"
                                >
                                    <option value="">Seleccione...</option>
                                    <template x-for="institution in institutions" :key="`institution-${institution.id}`">
                                        <option :value="String(institution.id)" x-text="institution.name"></option>
                                    </template>
                                </select>
                                @error('institution_destino_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="entrega-servicio" class="text-sm font-medium text-slate-700">Servicio</label>
                                <select
                                    id="entrega-servicio"
                                    name="service_destino_id"
                                    x-model="delivery.serviceId"
                                    @change="handleDeliveryServiceChange()"
                                    class="form-control"
                                >
                                    <option value="">Seleccione...</option>
                                    <template x-for="service in delivery.services" :key="`service-${service.id}`">
                                        <option :value="String(service.id)" x-text="service.label"></option>
                                    </template>
                                </select>
                                @error('service_destino_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="entrega-oficina" class="text-sm font-medium text-slate-700">Oficina</label>
                                <select
                                    id="entrega-oficina"
                                    name="office_destino_id"
                                    x-model="delivery.officeId"
                                    class="form-control"
                                >
                                    <option value="">Seleccione...</option>
                                    <template x-for="office in delivery.offices" :key="`office-${office.id}`">
                                        <option :value="String(office.id)" x-text="office.label"></option>
                                    </template>
                                </select>
                                @error('office_destino_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="entrega-receptor" class="text-sm font-medium text-slate-700">Recibe</label>
                                <input id="entrega-receptor" type="text" name="receptor_nombre" value="{{ old('receptor_nombre') }}" class="form-control" placeholder="Nombre y apellido">
                                @error('receptor_nombre')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="entrega-dni" class="text-sm font-medium text-slate-700">Doc.</label>
                                <input id="entrega-dni" type="text" name="receptor_dni" value="{{ old('receptor_dni') }}" class="form-control" placeholder="DNI o referencia">
                                @error('receptor_dni')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="entrega-cargo" class="text-sm font-medium text-slate-700">Cargo</label>
                                <input id="entrega-cargo" type="text" name="receptor_cargo" value="{{ old('receptor_cargo') }}" class="form-control" placeholder="Funcion o rol">
                                @error('receptor_cargo')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="entrega-dependencia" class="text-sm font-medium text-slate-700">Sector / ref.</label>
                                <input id="entrega-dependencia" type="text" name="receptor_dependencia" value="{{ old('receptor_dependencia') }}" class="form-control" placeholder="Servicio, oficina o area">
                                @error('receptor_dependencia')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="entrega-observaciones" class="text-sm font-medium text-slate-700">Notas</label>
                                <textarea id="entrega-observaciones" name="observaciones" rows="3" class="form-control" placeholder="Dato util para el acta">{{ old('observaciones') }}</textarea>
                                @error('observaciones')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @error('equipo_id')
                            <p class="form-error mt-3">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap justify-end gap-2 pt-4">
                            <button type="button" class="btn btn-slate" @click="closeModal()">Cancelar</button>
                            <button
                                type="submit"
                                class="btn btn-blue"
                                :disabled="!canDeliver(delivery.selected)"
                                :class="!canDeliver(delivery.selected) ? 'cursor-not-allowed opacity-60' : ''"
                            >
                                <x-icon name="upload" class="h-4 w-4" />
                                Entregar
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        @endcomponent

        @component('mesa-tecnica.partials.modal-shell', [
            'name' => 'etiqueta',
            'title' => 'Etiqueta',
            'subtitle' => 'Abrir e imprimir el QR institucional.',
            'icon' => 'printer',
            'tone' => 'amber',
            'maxWidth' => 'max-w-5xl',
        ])
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(19rem,0.92fr)]">
                <section class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/85 p-4">
                        <label for="etiqueta-busqueda" class="text-sm font-medium text-slate-700">Buscar equipo</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <input
                                id="etiqueta-busqueda"
                                type="text"
                                x-model="labelState.query"
                                @input.debounce.350ms="search('labelState')"
                                @keydown.enter.prevent="search('labelState')"
                                class="form-control !mt-0"
                                placeholder="Codigo, serie, patrimonial o QR"
                                autocomplete="off"
                            >
                            <button type="button" class="btn btn-slate" @click="search('labelState')">
                                <x-icon name="search" class="h-4 w-4" />
                                Buscar
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500" x-text="labelState.loading ? 'Buscando equipo...' : labelState.message"></p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="item in labelState.results" :key="`label-${item.id}`">
                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="app-badge px-3" :class="equipmentStatusClass(item.estado)" x-text="item.estado_label || humanizeEstado(item.estado)"></span>
                                            <span class="app-badge bg-slate-100 px-3 text-slate-700" x-text="item.codigo_interno || 'Sin codigo'"></span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-950" x-text="item.label || item.tipo"></p>
                                        <p class="text-sm text-slate-600" x-text="item.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                    </div>

                                    <button type="button" class="btn btn-slate" @click="selectEquipo('labelState', item)">
                                        Seleccionar
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50/85 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Equipo</p>

                        <template x-if="labelState.selected">
                            <div class="mt-3 space-y-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="app-badge bg-white px-3 text-amber-700" x-text="labelState.selected.codigo_interno || 'Sin codigo'"></span>
                                        <span class="app-badge px-3" :class="equipmentStatusClass(labelState.selected.estado)" x-text="labelState.selected.estado_label || humanizeEstado(labelState.selected.estado)"></span>
                                    </div>
                                    <p class="mt-3 text-lg font-semibold text-amber-950" x-text="labelState.selected.label || labelState.selected.tipo"></p>
                                    <p class="mt-1 text-sm text-amber-900" x-text="labelState.selected.ubicacion_resumida || 'Sin ubicacion visible'"></p>
                                </div>

                                <dl class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl bg-white/85 px-3 py-3">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">Serie</dt>
                                        <dd class="mt-1 text-sm font-medium text-amber-950" x-text="labelState.selected.numero_serie || '-'"></dd>
                                    </div>
                                    <div class="rounded-xl bg-white/85 px-3 py-3">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">Patrimonial</dt>
                                        <dd class="mt-1 text-sm font-medium text-amber-950" x-text="labelState.selected.bien_patrimonial || '-'"></dd>
                                    </div>
                                </dl>

                                <div class="flex flex-wrap gap-2 pt-1">
                                    <a :href="labelUrl(labelState.selected.id)" target="_blank" rel="noopener noreferrer" class="btn btn-amber">
                                        <x-icon name="printer" class="h-4 w-4" />
                                        Abrir etiqueta
                                    </a>
                                    <a :href="equipoShowUrl(labelState.selected.id)" class="btn btn-slate">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Ver ficha
                                    </a>
                                </div>
                            </div>
                        </template>

                        <template x-if="!labelState.selected">
                            <p class="mt-3 text-sm text-amber-900">Seleccione un equipo para imprimir.</p>
                        </template>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-600 shadow-sm">
                        La vista abre en otra ventana con QR estable, codigo interno y datos minimos para impresion.
                    </div>
                </section>
            </div>
        @endcomponent

        <script>
            function mesaTecnicaPage(config) {
                const createSearchState = (selected = null) => ({
                    query: '',
                    results: [],
                    loading: false,
                    message: 'Escriba un identificador visible para comenzar.',
                    selected,
                    controller: null,
                });

                return {
                    institutions: config.institutions ?? [],
                    activeInstitutionId: config.activeInstitutionId,
                    operatesGlobally: Boolean(config.operatesGlobally),
                    endpoints: config.endpoints,
                    urls: config.urls,
                    activeModal: config.initialModal || null,
                    lookup: createSearchState(),
                    receive: createSearchState(config.initialModal === 'recepcion' ? config.restoredSelectedEquipo : null),
                    delivery: {
                        ...createSearchState(config.initialModal === 'entrega' ? config.restoredSelectedEquipo : null),
                        institutionId: String(config.oldDelivery?.institution_destino_id ?? ''),
                        serviceId: String(config.oldDelivery?.service_destino_id ?? ''),
                        officeId: String(config.oldDelivery?.office_destino_id ?? ''),
                        services: [],
                        offices: [],
                    },
                    labelState: createSearchState(config.initialModal === 'etiqueta' ? config.restoredSelectedEquipo : null),

                    init() {
                        if (this.delivery.institutionId) {
                            this.loadDeliveryServices(this.delivery.serviceId);
                        }

                        if (this.delivery.serviceId) {
                            this.loadDeliveryOffices(this.delivery.officeId);
                        }
                    },

                    openModal(name, equipo = null) {
                        this.activeModal = name;

                        if (name === 'recepcion' && equipo) {
                            this.selectEquipo('receive', equipo);
                        }

                        if (name === 'entrega' && equipo) {
                            this.selectEquipo('delivery', equipo);
                        }

                        if (name === 'etiqueta' && equipo) {
                            this.selectEquipo('labelState', equipo);
                        }
                    },

                    closeModal() {
                        this.activeModal = null;
                    },

                    clearSelected(stateName) {
                        this[stateName].selected = null;
                    },

                    selectEquipo(stateName, item) {
                        this[stateName].selected = item;
                        this[stateName].results = [];
                        this[stateName].message = 'Equipo listo para operar.';
                    },

                    canReceive(item) {
                        return Boolean(item) && String(item.estado) === 'prestado';
                    },

                    canDeliver(item) {
                        return Boolean(item) && String(item.estado) !== 'baja';
                    },

                    equipmentStatusClass(state) {
                        switch (String(state || '')) {
                            case 'operativo':
                                return 'bg-emerald-100 text-emerald-700';
                            case 'prestado':
                                return 'bg-blue-100 text-blue-700';
                            case 'mantenimiento':
                                return 'bg-amber-100 text-amber-700';
                            case 'fuera_de_servicio':
                                return 'bg-orange-100 text-orange-700';
                            case 'baja':
                                return 'bg-red-100 text-red-700';
                            default:
                                return 'bg-slate-100 text-slate-700';
                        }
                    },

                    async search(stateName) {
                        const state = this[stateName];
                        const normalizedQuery = this.normalizeEquipmentQuery(state.query);

                        if (normalizedQuery === '') {
                            state.results = [];
                            state.message = 'Escriba un identificador visible para comenzar.';
                            return;
                        }

                        if (state.controller) {
                            state.controller.abort();
                        }

                        state.loading = true;
                        state.controller = new AbortController();

                        try {
                            const url = new URL(this.endpoints.equipos, window.location.origin);
                            url.searchParams.set('q', normalizedQuery);
                            url.searchParams.set('acta_context', '1');

                            const scopedInstitutionId = this.scopeInstitutionId();

                            if (scopedInstitutionId) {
                                url.searchParams.set('institution_id', String(scopedInstitutionId));
                            }

                            const response = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                signal: state.controller.signal,
                            });

                            if (!response.ok) {
                                state.results = [];
                                state.message = 'No se pudo completar la busqueda en este momento.';
                                return;
                            }

                            const payload = await response.json();
                            const items = Array.isArray(payload) ? payload : (payload.items ?? []);
                            const metaMessage = Array.isArray(payload) ? null : (payload.meta?.message ?? null);

                            state.results = items.map((item) => ({
                                ...item,
                                estado_label: item.estado_label || this.humanizeEstado(item.estado),
                            }));
                            state.message = metaMessage || (state.results.length > 0 ? 'Seleccione un equipo.' : 'No se encontraron equipos.');
                        } catch (error) {
                            if (error?.name === 'AbortError') {
                                return;
                            }

                            state.results = [];
                            state.message = 'No se pudo realizar la busqueda. Intente nuevamente.';
                        } finally {
                            state.loading = false;
                        }
                    },

                    async handleDeliveryInstitutionChange() {
                        this.delivery.serviceId = '';
                        this.delivery.officeId = '';
                        this.delivery.offices = [];
                        await this.loadDeliveryServices('');
                    },

                    async handleDeliveryServiceChange() {
                        this.delivery.officeId = '';
                        await this.loadDeliveryOffices('');
                    },

                    async loadDeliveryServices(selectedServiceId = '') {
                        this.delivery.services = [];

                        if (!this.delivery.institutionId) {
                            return;
                        }

                        const url = new URL(this.endpoints.services, window.location.origin);
                        url.searchParams.set('q', '...');
                        url.searchParams.set('acta_context', '1');
                        url.searchParams.set('institution_id', this.delivery.institutionId);

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        this.delivery.services = await response.json();

                        if (selectedServiceId && this.delivery.services.some((service) => String(service.id) === String(selectedServiceId))) {
                            this.delivery.serviceId = String(selectedServiceId);
                        }
                    },

                    async loadDeliveryOffices(selectedOfficeId = '') {
                        this.delivery.offices = [];

                        if (!this.delivery.institutionId || !this.delivery.serviceId) {
                            return;
                        }

                        const url = new URL(this.endpoints.offices, window.location.origin);
                        url.searchParams.set('q', '...');
                        url.searchParams.set('acta_context', '1');
                        url.searchParams.set('institution_id', this.delivery.institutionId);
                        url.searchParams.set('service_id', this.delivery.serviceId);

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        this.delivery.offices = await response.json();

                        if (selectedOfficeId && this.delivery.offices.some((office) => String(office.id) === String(selectedOfficeId))) {
                            this.delivery.officeId = String(selectedOfficeId);
                        }
                    },

                    humanizeEstado(value) {
                        switch (String(value || '')) {
                            case 'operativo':
                                return 'Operativo';
                            case 'prestado':
                                return 'Prestado';
                            case 'mantenimiento':
                                return 'Mantenimiento';
                            case 'fuera_de_servicio':
                                return 'Fuera de servicio';
                            case 'baja':
                                return 'Baja';
                            default:
                                return 'Sin estado';
                        }
                    },

                    normalizeEquipmentQuery(value) {
                        const text = String(value || '').trim();

                        if (text === '') {
                            return '';
                        }

                        const uuidMatch = text.match(/[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/i);

                        if (uuidMatch) {
                            return uuidMatch[0];
                        }

                        return text;
                    },

                    scopeInstitutionId() {
                        if (this.operatesGlobally) {
                            return null;
                        }

                        return this.activeInstitutionId;
                    },

                    equipoShowUrl(equipoId) {
                        return `${this.urls.equiposBase}/${equipoId}`;
                    },

                    labelUrl(equipoId) {
                        return `${this.urls.mesaEtiquetasBase}/${equipoId}/etiqueta`;
                    },

                    scrollToSection(id) {
                        const element = document.getElementById(id);

                        if (!element) {
                            return;
                        }

                        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    },
                };
            }
        </script>
    </div>
@endsection
