@extends('layouts.app')

@section('title', 'Ingreso tecnico '.$recepcionTecnica->codigo)
@section('header', 'Ingreso tecnico')

@section('content')
    @php($equipo = $recepcionTecnica->resolvedEquipo())

    <div class="space-y-5 lg:space-y-6">
        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-slate-900 px-3 text-white">{{ $recepcionTecnica->codigo }}</span>
                        @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcionTecnica->estado, 'label' => $recepcionTecnica->statusLabel()])
                    </div>

                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $recepcionTecnica->equipmentReference() }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Ingreso del {{ $recepcionTecnica->fecha_recepcion?->format('d/m/Y') ?: '-' }}
                            por {{ $recepcionTecnica->creator?->name ?? 'Usuario no disponible' }}.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.index') }}" class="btn btn-slate">
                        <x-icon name="x" class="h-4 w-4" />
                        Volver
                    </a>
                    <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcionTecnica) }}" target="_blank" rel="noopener noreferrer" class="btn btn-amber">
                        <x-icon name="printer" class="h-4 w-4" />
                        {{ (int) $recepcionTecnica->print_count > 0 ? 'Reimprimir' : 'Imprimir' }}
                    </a>
                    @if ($equipo)
                        <a href="{{ route('equipos.show', $equipo) }}" class="btn btn-slate">
                            <x-icon name="eye" class="h-4 w-4" />
                            Ver equipo
                        </a>
                    @elseif ($recepcionTecnica->canBeIncorporated())
                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.create', $recepcionTecnica) }}" class="btn btn-indigo">
                            <x-icon name="plus" class="h-4 w-4" />
                            Dar de alta
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(21rem,0.8fr)]">
            <div class="space-y-6">
                <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Equipo</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Resumen</h3>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Referencia</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->referencia_equipo ?: $recepcionTecnica->equipmentReference() }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Codigo</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->codigo_interno_equipo ?: 'Todavia no vinculado' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Tipo</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->tipo_equipo_texto ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Marca / modelo</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ trim(implode(' / ', array_filter([$recepcionTecnica->marca, $recepcionTecnica->modelo]))) ?: '-' }}</p>
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
                </div>

                <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Entrega</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Quien lo trajo</h3>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $recepcionTecnica->persona_nombre }}</p>
                            <p class="text-sm text-slate-600">{{ $recepcionTecnica->persona_relacion_equipo ?: 'Relacion no informada' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Doc.</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $recepcionTecnica->persona_documento ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telefono</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $recepcionTecnica->persona_telefono ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Area / institucion</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ collect([$recepcionTecnica->persona_area, $recepcionTecnica->persona_institucion])->filter()->implode(' / ') ?: '-' }}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Procedencia</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $recepcionTecnica->procedenciaResumen() }}</p>
                        </div>
                    </div>
                </div>

                <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Falla</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Detalle</h3>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Motivo</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $recepcionTecnica->falla_motivo ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripcion</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->descripcion_falla ?: '-' }}</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Accesorios</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->accesorios_entregados ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado fisico</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->estado_fisico_inicial ?: '-' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obs. comprobante</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_recepcion ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obs. internas</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_internas ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Seguimiento</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Estado actual</h3>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Sector</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $recepcionTecnica->institution?->nombre ?: 'Sin institucion' }} / {{ $recepcionTecnica->sector_receptor }}
                            </p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">URL publica</p>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-sm font-medium text-indigo-700 hover:text-indigo-800">
                                {{ $publicUrl }}
                            </a>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="app-subcard p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Impresiones</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->print_count }}</p>
                            </div>
                            <div class="app-subcard p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Ultima impresion</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->last_printed_at?->format('d/m/Y H:i') ?: '-' }}</p>
                            </div>
                        </div>

                        @if ($recepcionTecnica->motivo_anulacion)
                            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Motivo de anulacion</p>
                                <p class="mt-2 text-sm text-red-900">{{ $recepcionTecnica->motivo_anulacion }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Gestion</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Actualizar estado</h3>
                    </div>

                    <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.status.update', $recepcionTecnica) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                            <select id="estado" name="estado" class="app-input">
                                @foreach ($statusOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('estado', $recepcionTecnica->estado) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('estado')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="motivo_anulacion" class="mb-2 block text-sm font-medium text-slate-700">Motivo anulacion</label>
                            <textarea id="motivo_anulacion" name="motivo_anulacion" rows="3" class="app-input" placeholder="Solo si anula el ticket">{{ old('motivo_anulacion') }}</textarea>
                            @error('motivo_anulacion')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-indigo w-full gap-2">
                            <x-icon name="check-circle-2" class="h-4 w-4" />
                            Guardar
                        </button>
                    </form>
                </div>

                @if ($recepcionTecnica->canBeIncorporated())
                    <div class="rounded-[2rem] border border-indigo-200 bg-indigo-50 px-5 py-5 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">Pendiente</p>
                        <h3 class="mt-2 text-lg font-semibold text-indigo-950">Aun no esta en Equipos</h3>
                        <p class="mt-2 text-sm text-indigo-900">
                            Puede vincular uno existente o darlo de alta sin perder trazabilidad.
                        </p>
                        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.create', $recepcionTecnica) }}" class="btn btn-indigo mt-4 gap-2">
                            <x-icon name="plus" class="h-4 w-4" />
                            Dar de alta
                        </a>
                    </div>
                @endif
            </aside>
        </section>
    </div>
@endsection
