@extends('layouts.app')

@section('title', 'Mesa tecnica')
@section('header', 'Mesa tecnica')

@section('content')
    <div class="space-y-5 lg:space-y-6">
        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(18rem,0.85fr)]">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-slate-900 px-3 text-white">Mesa tecnica</span>
                        <span class="app-badge bg-indigo-50 px-3 text-indigo-700">Operacion diaria</span>
                        <span class="app-badge bg-amber-50 px-3 text-amber-700">No altera patrimonio</span>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">
                            Recibir, diagnosticar, reparar y entregar sin perder trazabilidad.
                        </h2>
                        <p class="max-w-3xl text-sm leading-6 text-slate-600">
                            La operacion diaria prioriza tickets activos y deja el historial en un bloque separado. Si el equipo cambia de destino patrimonial, use Actas.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.create') }}" class="rounded-[1.75rem] border border-indigo-200 bg-indigo-50 p-5 transition hover:border-indigo-300 hover:bg-indigo-100/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700">Accion principal</p>
                            <h3 class="mt-2 text-lg font-semibold text-indigo-950">Recibir para reparacion</h3>
                            <p class="mt-2 text-sm text-indigo-900">Genera el ticket tecnico y deja constancia de la custodia temporal.</p>
                        </a>

                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_ACTIVOS]) }}" class="rounded-[1.75rem] border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:bg-slate-50">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Cola operativa</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-950">Tickets activos</h3>
                            <p class="mt-2 text-sm text-slate-700">Muestra lo que todavia requiere accion tecnica y deja afuera los cerrados.</p>
                        </a>

                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_LISTOS]) }}" class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 transition hover:border-emerald-300 hover:bg-emerald-100/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Entrega</p>
                            <h3 class="mt-2 text-lg font-semibold text-emerald-950">Listos para entregar</h3>
                            <p class="mt-2 text-sm text-emerald-900">Va directo a los tickets que ya pueden salir de la cola diaria.</p>
                        </a>
                    </div>
                </div>

                <aside class="space-y-3 rounded-[1.75rem] border border-slate-200 bg-slate-50/90 p-4 sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Activos</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $activeCount }}</p>
                            <p class="mt-1 text-sm text-slate-600">Tickets operativos visibles hoy.</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Listos para entregar</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-emerald-950">{{ $readyCount }}</p>
                            <p class="mt-1 text-sm text-emerald-900">Equipos listos para salir del taller.</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Historial reciente</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $closedCount }}</p>
                            <p class="mt-1 text-sm text-slate-600">Cerrados, no reparables o cancelados.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-600">
                        El patrimonio del equipo sigue perteneciendo a su institucion, servicio y oficina de origen mientras el ticket tecnico este abierto.
                    </div>

                    <a href="{{ route('actas.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                        Ir a Actas y movimientos
                    </a>
                </aside>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
            <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Operacion diaria</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Listos para entregar</h3>
                        <p class="mt-1 text-sm text-slate-600">Estos tickets ya deberian poder cerrarse o entregarse.</p>
                    </div>

                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_LISTOS]) }}" class="btn btn-emerald">
                        <x-icon name="check-circle-2" class="h-4 w-4" />
                        Ver cola de entrega
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($readyTickets as $ticket)
                        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="app-badge bg-slate-900 px-3 text-white">{{ $ticket['codigo'] }}</span>
                                        @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $ticket['estado'], 'label' => $ticket['estado_label']])
                                    </div>

                                    <div>
                                        <p class="text-base font-semibold text-slate-950">{{ $ticket['equipo'] }}</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ $ticket['procedencia'] }}</p>
                                        <p class="mt-1 text-sm text-emerald-900">{{ $ticket['persona_entrega'] ?: 'Sin persona informada' }} | {{ $ticket['fecha'] }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $ticket['id'], 'return_to' => route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_LISTOS])]) }}" class="btn btn-emerald">
                                        <x-icon name="check-circle-2" class="h-4 w-4" />
                                        {{ $ticket['next_action'] }}
                                    </a>
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $ticket['id']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-slate">
                                        <x-icon name="printer" class="h-4 w-4" />
                                        Imprimir
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-emerald-300 px-4 py-8 text-center text-sm text-slate-500">
                            No hay tickets listos para entregar dentro del alcance actual.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Operacion diaria</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Tickets activos</h3>
                        <p class="mt-1 text-sm text-slate-600">La cola operativa deja afuera los cerrados para reducir ruido visual.</p>
                    </div>

                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_ACTIVOS]) }}" class="btn btn-slate">
                        <x-icon name="eye" class="h-4 w-4" />
                        Ver activos
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($activeTickets as $ticket)
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="app-badge bg-slate-900 px-3 text-white">{{ $ticket['codigo'] }}</span>
                                        @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $ticket['estado'], 'label' => $ticket['estado_label']])
                                    </div>

                                    <div>
                                        <p class="text-base font-semibold text-slate-950">{{ $ticket['equipo'] }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $ticket['procedencia'] }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $ticket['persona_entrega'] ?: 'Sin persona informada' }} | {{ $ticket['fecha'] }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2 text-sm text-slate-600 lg:text-right">
                                    <p class="font-semibold text-slate-900">{{ $ticket['next_action'] }}</p>
                                    <p>Registrado por {{ $ticket['creator'] }}</p>
                                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $ticket['id'], 'return_to' => route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_ACTIVOS])]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-700 hover:text-indigo-800">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Abrir ticket
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                            No hay tickets activos visibles en este momento.
                        </div>
                    @endforelse
                </div>
            </section>
        </section>

        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Historial reciente</p>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Tickets cerrados o cancelados</h3>
                    <p class="mt-1 text-sm text-slate-600">El historial sigue accesible, pero separado de la cola operativa diaria.</p>
                </div>

                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_CERRADOS]) }}" class="btn btn-slate">
                    <x-icon name="eye" class="h-4 w-4" />
                    Ver historial
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($recentHistory as $ticket)
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="app-badge bg-slate-900 px-3 text-white">{{ $ticket['codigo'] }}</span>
                                    @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $ticket['estado'], 'label' => $ticket['estado_label']])
                                </div>

                                <div>
                                    <p class="text-base font-semibold text-slate-950">{{ $ticket['equipo'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $ticket['procedencia'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $ticket['fecha'] }} | {{ $ticket['persona_entrega'] ?: 'Sin persona informada' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $ticket['id'], 'return_to' => route('mesa-tecnica.recepciones-tecnicas.index', ['vista' => \App\Models\RecepcionTecnica::VISTA_CERRADOS])]) }}" class="btn btn-slate">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Ver ticket
                                </a>
                                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $ticket['id']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-amber">
                                    <x-icon name="printer" class="h-4 w-4" />
                                    Imprimir
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                        Todavia no hay historial tecnico reciente en el alcance actual.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
