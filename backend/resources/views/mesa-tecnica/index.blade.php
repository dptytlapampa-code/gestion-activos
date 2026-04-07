@extends('layouts.app')

@section('title', 'Mesa tecnica')
@section('header', 'Mesa tecnica')

@section('content')
    <div class="space-y-5 lg:space-y-6">
        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-slate-900 px-3 text-white">Mesa tecnica</span>
                        <span class="app-badge bg-indigo-50 px-3 text-indigo-700">Ingreso tecnico temporal</span>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">
                            Recibir para reparacion, dar seguimiento y cerrar sin mover patrimonio.
                        </h2>
                        <p class="max-w-3xl text-sm leading-6 text-slate-600">
                            Este circuito registra la custodia temporal en taller. Si el equipo cambia de destino patrimonial, use el modulo de Actas.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="rounded-[1.75rem] border border-indigo-200 bg-indigo-50 p-5 transition hover:border-indigo-300 hover:bg-indigo-100/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700">Accion principal</p>
                            <h3 class="mt-2 text-lg font-semibold text-indigo-950">Recibir para reparacion</h3>
                            <p class="mt-2 text-sm text-indigo-900">Busca por serie, codigo interno, bien patrimonial o QR y genera el ticket.</p>
                        </a>

                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="rounded-[1.75rem] border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:bg-slate-50">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Seguimiento</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-950">Ingresos tecnicos</h3>
                            <p class="mt-2 text-sm text-slate-700">Estados claros, ticket visible, cierre y reimpresion desde un solo listado.</p>
                        </a>

                        <a href="{{ route('actas.index') }}" class="rounded-[1.75rem] border border-amber-200 bg-amber-50 p-5 transition hover:border-amber-300 hover:bg-amber-100/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Separacion patrimonial</p>
                            <h3 class="mt-2 text-lg font-semibold text-amber-950">Actas y movimientos</h3>
                            <p class="mt-2 text-sm text-amber-900">Entrega, prestamo, traslado, devolucion patrimonial y baja se gestionan fuera de este circuito.</p>
                        </a>
                    </div>
                </div>

                <aside class="space-y-3 rounded-[1.75rem] border border-slate-200 bg-slate-50/90 p-4 sm:p-5">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Abiertos</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $openCount }}</p>
                        <p class="mt-1 text-sm text-slate-600">Ingresos tecnicos aun no cerrados.</p>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Listos para entregar</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-emerald-950">{{ $readyCount }}</p>
                        <p class="mt-1 text-sm text-emerald-900">Equipos con trabajo tecnico finalizado.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-600">
                        El patrimonio del equipo sigue perteneciendo a su institucion, servicio y oficina de origen mientras el ticket tecnico este abierto.
                    </div>
                </aside>
            </div>
        </section>

        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Actividad reciente</p>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Ultimos tickets</h3>
                </div>

                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="btn btn-slate">
                    <x-icon name="eye" class="h-4 w-4" />
                    Ver todos
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($recentIngresos as $ingreso)
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="app-badge bg-slate-900 px-3 text-white">{{ $ingreso['codigo'] }}</span>
                                    @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $ingreso['estado'], 'label' => $ingreso['estado_label']])
                                </div>
                                <div>
                                    <p class="text-base font-semibold text-slate-950">{{ $ingreso['equipo'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $ingreso['fecha'] }} | {{ $ingreso['persona_entrega'] ?: 'Sin persona informada' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">Registrado por {{ $ingreso['creator'] }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', $ingreso['id']) }}" class="btn btn-slate">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Ver ticket
                                </a>
                                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $ingreso['id']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-amber">
                                    <x-icon name="printer" class="h-4 w-4" />
                                    Imprimir
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                        No hay ingresos tecnicos visibles en el alcance actual.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
