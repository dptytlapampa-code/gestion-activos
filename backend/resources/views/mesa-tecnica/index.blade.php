@extends('layouts.app')

@section('title', 'Mesa tecnica')
@section('header', 'Mesa tecnica')

@section('content')
    @include('mesa-tecnica.partials.module-styles')

    @php
        $traySections = [
            'en-mesa' => [
                'label' => 'En mesa',
                'count' => $enMesaCount,
                'icon' => 'wrench',
                'tickets' => $enMesaTickets,
                'empty' => 'No hay equipos en mesa dentro del alcance actual.',
                'list_url' => route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_EN_MESA]),
            ],
            'listos' => [
                'label' => 'Listos para entregar',
                'count' => $readyCount,
                'icon' => 'check-circle-2',
                'tickets' => $readyTickets,
                'empty' => 'No hay equipos listos para entregar en este momento.',
                'list_url' => route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_LISTOS]),
            ],
            'pendientes' => [
                'label' => 'Pendientes',
                'count' => $pendingCount,
                'icon' => 'boxes',
                'tickets' => $pendingTickets,
                'empty' => 'No hay tickets pendientes por espera o bloqueo operativo.',
                'list_url' => route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_PENDIENTES]),
            ],
            'finalizados' => [
                'label' => 'Finalizados',
                'count' => $finalizedCount,
                'icon' => 'door-closed',
                'tickets' => $finalizedTickets,
                'empty' => 'Todavia no hay tickets finalizados dentro del alcance actual.',
                'list_url' => route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_FINALIZADOS]),
            ],
        ];

        $requestedTab = trim((string) request()->query('tab', ''));
        $initialTab = collect(array_keys($traySections))
            ->first(fn (string $key): bool => ($traySections[$key]['count'] ?? 0) > 0)
            ?? 'en-mesa';

        if (array_key_exists($requestedTab, $traySections)) {
            $initialTab = $requestedTab;
        }
    @endphp

    <div x-data="{ activeTab: @js($initialTab) }" class="space-y-4 lg:space-y-5">
        <section class="mt-search-shell space-y-4">
            <div class="space-y-1">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Mesa tecnica</h2>
                <p class="text-sm text-slate-500">Bandeja operativa para recepcion, seguimiento y entrega de equipos.</p>
            </div>

            <form method="GET" action="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="mt-search-grid">
                <input type="hidden" name="bandeja" value="{{ \App\Services\RecepcionTecnicaService::TRAY_TODOS }}">

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <x-icon name="search" class="h-5 w-5" />
                    </span>
                    <input
                        type="search"
                        name="search"
                        class="app-input min-h-[3.5rem] w-full pl-12 text-base"
                        placeholder="Buscar por CI, numero de serie, acta o codigo interno"
                        autocomplete="off"
                    >
                </div>

                <button type="submit" class="btn btn-indigo mt-primary-action min-w-[9rem]">
                    <x-icon name="search" class="h-4 w-4" />
                    Buscar
                </button>
            </form>

            <div class="mt-tray-tabs">
                @foreach ($traySections as $key => $section)
                    <button
                        type="button"
                        class="mt-tray-tab"
                        :class="{ 'mt-tray-tab-active': activeTab === @js($key) }"
                        @click="activeTab = @js($key)"
                    >
                        <x-icon name="{{ $section['icon'] }}" class="h-4 w-4" />
                        <span>{{ $section['label'] }}</span>
                        <span class="mt-tray-tab-count">{{ $section['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        @foreach ($traySections as $key => $section)
            <section x-cloak x-show="activeTab === @js($key)" class="mt-queue-board space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $section['label'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $section['count'] }} ticket(s) visibles en esta bandeja.</p>
                    </div>

                    <a href="{{ $section['list_url'] }}" class="btn btn-slate w-full sm:w-auto">
                        <x-icon name="eye" class="h-4 w-4" />
                        Ver cola completa
                    </a>
                </div>

                <div class="mt-queue-stack">
                    @forelse ($section['tickets'] as $recepcion)
                        @include('mesa-tecnica.partials.queue-ticket-card', [
                            'recepcion' => $recepcion,
                            'returnTo' => route('mesa-tecnica.index', ['tab' => $key]),
                        ])
                    @empty
                        <div class="mt-empty-state">{{ $section['empty'] }}</div>
                    @endforelse
                </div>
            </section>
        @endforeach

        <x-collapsible-panel
            title="Mas opciones"
            eyebrow="Secundario"
            icon="sliders-horizontal"
            summary="Acciones complementarias y accesos que no deben competir con la cola principal."
            persist-key="mesa-tecnica.dashboard.mas-opciones"
            class="mt-operational-panel rounded-[1.5rem]"
        >
            <div class="space-y-4">
                <p class="text-sm text-slate-600">Use estas opciones solo cuando necesite salir de la bandeja principal.</p>

                <div class="mt-secondary-grid">
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="mt-secondary-link">
                        <span class="mt-icon-chip mt-icon-chip-sm text-indigo-700">
                            <x-icon name="plus" class="h-4 w-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Recibir para reparacion</span>
                            <span class="mt-1 block text-sm text-slate-600">Registrar un nuevo ingreso tecnico.</span>
                        </span>
                    </a>

                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['bandeja' => \App\Services\RecepcionTecnicaService::TRAY_TODOS]) }}" class="mt-secondary-link">
                        <span class="mt-icon-chip mt-icon-chip-sm text-slate-700">
                            <x-icon name="layers" class="h-4 w-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Ver todos los ingresos</span>
                            <span class="mt-1 block text-sm text-slate-600">Abrir la bandeja completa con filtros.</span>
                        </span>
                    </a>

                    <a href="{{ route('actas.index') }}" class="mt-secondary-link">
                        <span class="mt-icon-chip mt-icon-chip-sm text-amber-700">
                            <x-icon name="clipboard-list" class="h-4 w-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Actas y movimientos</span>
                            <span class="mt-1 block text-sm text-slate-600">Solo para cambios patrimoniales o entregas formales.</span>
                        </span>
                    </a>
                </div>
            </div>
        </x-collapsible-panel>
    </div>
@endsection
