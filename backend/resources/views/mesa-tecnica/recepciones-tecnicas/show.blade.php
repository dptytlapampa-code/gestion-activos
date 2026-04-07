@extends('layouts.app')

@section('title', 'Ingreso tecnico '.$recepcionTecnica->codigo)
@section('header', 'Ingreso tecnico')

@section('content')
    @php
        $equipo = $recepcionTecnica->resolvedEquipo();
        $isOperational = ! $recepcionTecnica->isClosed() && ! $recepcionTecnica->isCancelled();
        $operationTone = $recepcionTecnica->isReadyForDelivery()
            ? 'border-emerald-200 bg-emerald-50'
            : ($recepcionTecnica->isCancelled() ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50');
        $operationEyebrowTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-700'
            : ($recepcionTecnica->isCancelled() ? 'text-rose-700' : 'text-slate-500');
        $operationTitleTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-950'
            : 'text-slate-950';
        $operationTextTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-900'
            : 'text-slate-600';
        $inventoSummary = $equipo?->codigo_interno ?: ($recepcionTecnica->codigo_interno_equipo ?: 'Pendiente de vincular');
    @endphp

    <div class="space-y-5 lg:space-y-6">
        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-slate-900 px-3 text-white">{{ $recepcionTecnica->codigo }}</span>
                        @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcionTecnica->estado, 'label' => $recepcionTecnica->statusLabel()])
                        @if ($recepcionTecnica->isReadyForDelivery())
                            <span class="app-badge bg-emerald-50 px-3 text-emerald-700">Prioridad de entrega</span>
                        @endif
                        @if ($equipo)
                            <span class="app-badge bg-indigo-50 px-3 text-indigo-700">Patrimonio sin cambios</span>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ticket tecnico</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">{{ $recepcionTecnica->equipmentReference() }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Ingreso del {{ $recepcionTecnica->ingresado_at?->format('d/m/Y H:i') ?: '-' }}
                            recibido por {{ $recepcionTecnica->recibidoPor?->name ?? $recepcionTecnica->creator?->name ?? 'Usuario no disponible' }}.
                        </p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Procedencia</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->procedenciaResumen() }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Entrega</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->receptorResumen() }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Inventario</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $inventoSummary }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Sector receptor</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->institution?->nombre ?: 'Sin institucion' }} / {{ $recepcionTecnica->sector_receptor }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full max-w-xl space-y-3 xl:max-w-sm">
                    <div class="rounded-[1.75rem] border px-4 py-4 {{ $operationTone }}">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] {{ $operationEyebrowTone }}">Accion principal</p>
                        <h3 class="mt-2 text-lg font-semibold {{ $operationTitleTone }}">{{ $recepcionTecnica->nextActionLabel() }}</h3>
                        <p class="mt-2 text-sm {{ $operationTextTone }}">{{ $recepcionTecnica->nextActionDescription() }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $backToListUrl }}" class="btn btn-slate">
                            <x-icon name="x" class="h-4 w-4" />
                            {{ $backToListLabel }}
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
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.create', ['recepcionTecnica' => $recepcionTecnica, 'return_to' => $returnTo]) }}" class="btn btn-indigo">
                                <x-icon name="plus" class="h-4 w-4" />
                                Vincular equipo
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if ($isOperational)
            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.12fr)_minmax(21rem,0.88fr)]">
                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Panel operativo</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Registrar diagnostico y seguimiento</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Las acciones tecnicas principales quedan arriba para no obligar a recorrer el ticket completo.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.status.update', $recepcionTecnica) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        @if ($returnTo)
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado actual</label>
                                <select id="estado" name="estado" class="app-input">
                                    @foreach ($trackingStatusOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('estado', $recepcionTecnica->estado) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('estado')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Siguiente accion sugerida</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ $recepcionTecnica->nextActionLabel() }}</p>
                                <p class="mt-1">{{ $recepcionTecnica->nextActionDescription() }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="diagnostico" class="mb-2 block text-sm font-medium text-slate-700">Diagnostico tecnico</label>
                                <textarea id="diagnostico" name="diagnostico" rows="4" class="app-input" placeholder="Que se detecto hasta ahora">{{ old('diagnostico', $recepcionTecnica->diagnostico) }}</textarea>
                            </div>

                            <div>
                                <label for="accion_realizada" class="mb-2 block text-sm font-medium text-slate-700">Accion realizada</label>
                                <textarea id="accion_realizada" name="accion_realizada" rows="4" class="app-input" placeholder="Revision, reemplazo, prueba, limpieza, etc.">{{ old('accion_realizada', $recepcionTecnica->accion_realizada) }}</textarea>
                            </div>

                            <div>
                                <label for="solucion_aplicada" class="mb-2 block text-sm font-medium text-slate-700">Solucion aplicada</label>
                                <textarea id="solucion_aplicada" name="solucion_aplicada" rows="4" class="app-input" placeholder="Resultado tecnico concreto">{{ old('solucion_aplicada', $recepcionTecnica->solucion_aplicada) }}</textarea>
                            </div>

                            <div>
                                <label for="informe_tecnico" class="mb-2 block text-sm font-medium text-slate-700">Informe tecnico breve</label>
                                <textarea id="informe_tecnico" name="informe_tecnico" rows="4" class="app-input" placeholder="Resumen entendible para el retiro o para mantenimiento">{{ old('informe_tecnico', $recepcionTecnica->informe_tecnico) }}</textarea>
                            </div>
                        </div>

                        <div>
                            <label for="observaciones_internas" class="mb-2 block text-sm font-medium text-slate-700">Observaciones internas</label>
                            <textarea id="observaciones_internas" name="observaciones_internas" rows="3" class="app-input" placeholder="Notas de seguimiento interno">{{ old('observaciones_internas', $recepcionTecnica->observaciones_internas) }}</textarea>
                        </div>

                        <div>
                            <label for="motivo_anulacion" class="mb-2 block text-sm font-medium text-slate-700">Motivo de cancelacion</label>
                            <textarea id="motivo_anulacion" name="motivo_anulacion" rows="3" class="app-input" placeholder="Solo si cancela el ticket">{{ old('motivo_anulacion', $recepcionTecnica->motivo_anulacion) }}</textarea>
                            @error('motivo_anulacion')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500">
                                Guardar aqui no cierra el ticket. Solo deja el seguimiento tecnico actualizado.
                            </p>

                            <button type="submit" class="btn btn-indigo w-full gap-2 sm:w-auto">
                                <x-icon name="check-circle-2" class="h-4 w-4" />
                                Guardar seguimiento
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-6">
                    @if ($equipo)
                        <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                            <div class="border-b border-slate-200 pb-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cierre</p>
                                <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Entregar y cerrar ticket</h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    El historial tecnico final se genera automaticamente al cerrar.
                                </p>
                            </div>

                            @if ($recepcionTecnica->isReadyForDelivery())
                                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                                    El ticket ya esta marcado como listo para entregar. Este es el siguiente paso natural para sacarlo de la vista operativa.
                                </div>
                            @endif

                            <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.close', $recepcionTecnica) }}" class="mt-4 space-y-4">
                                @csrf

                                @if ($returnTo)
                                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                @endif

                                <div>
                                    <label for="estado_cierre" class="mb-2 block text-sm font-medium text-slate-700">Resultado del cierre</label>
                                    <select id="estado_cierre" name="estado_cierre" class="app-input">
                                        @foreach ($closureStatusOptions as $code => $label)
                                            <option value="{{ $code }}" @selected(old('estado_cierre', \App\Models\RecepcionTecnica::ESTADO_ENTREGADO) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('estado_cierre')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="condicion_egreso" class="mb-2 block text-sm font-medium text-slate-700">Condicion de egreso</label>
                                    <select id="condicion_egreso" name="condicion_egreso" class="app-input">
                                        @foreach ($egressConditionOptions as $code => $label)
                                            <option value="{{ $code }}" @selected(old('condicion_egreso', \App\Models\RecepcionTecnica::CONDICION_REPARADO) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('condicion_egreso')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="fecha_entrega_real" class="mb-2 block text-sm font-medium text-slate-700">Fecha y hora reales de entrega</label>
                                    <input id="fecha_entrega_real" name="fecha_entrega_real" type="datetime-local" value="{{ old('fecha_entrega_real', now()->format('Y-m-d\\TH:i')) }}" class="app-input">
                                    @error('fecha_entrega_real')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="persona_retiro_nombre" class="mb-2 block text-sm font-medium text-slate-700">Quien retira</label>
                                        <input id="persona_retiro_nombre" name="persona_retiro_nombre" type="text" value="{{ old('persona_retiro_nombre') }}" class="app-input" placeholder="Nombre y apellido">
                                        @error('persona_retiro_nombre')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="persona_retiro_documento" class="mb-2 block text-sm font-medium text-slate-700">Documento</label>
                                        <input id="persona_retiro_documento" name="persona_retiro_documento" type="text" value="{{ old('persona_retiro_documento') }}" class="app-input" placeholder="Opcional">
                                    </div>
                                </div>

                                <div>
                                    <label for="persona_retiro_cargo" class="mb-2 block text-sm font-medium text-slate-700">Cargo o referencia</label>
                                    <input id="persona_retiro_cargo" name="persona_retiro_cargo" type="text" value="{{ old('persona_retiro_cargo') }}" class="app-input" placeholder="Chofer, referente, tecnico local, etc.">
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="cierre_diagnostico" class="mb-2 block text-sm font-medium text-slate-700">Diagnostico final</label>
                                        <textarea id="cierre_diagnostico" name="diagnostico" rows="4" class="app-input">{{ old('diagnostico', $recepcionTecnica->diagnostico) }}</textarea>
                                        @error('diagnostico')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cierre_accion_realizada" class="mb-2 block text-sm font-medium text-slate-700">Accion realizada</label>
                                        <textarea id="cierre_accion_realizada" name="accion_realizada" rows="4" class="app-input">{{ old('accion_realizada', $recepcionTecnica->accion_realizada) }}</textarea>
                                        @error('accion_realizada')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cierre_solucion_aplicada" class="mb-2 block text-sm font-medium text-slate-700">Solucion aplicada</label>
                                        <textarea id="cierre_solucion_aplicada" name="solucion_aplicada" rows="4" class="app-input">{{ old('solucion_aplicada', $recepcionTecnica->solucion_aplicada) }}</textarea>
                                        @error('solucion_aplicada')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cierre_informe_tecnico" class="mb-2 block text-sm font-medium text-slate-700">Informe tecnico breve</label>
                                        <textarea id="cierre_informe_tecnico" name="informe_tecnico" rows="4" class="app-input">{{ old('informe_tecnico', $recepcionTecnica->informe_tecnico) }}</textarea>
                                        @error('informe_tecnico')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="observaciones_finales" class="mb-2 block text-sm font-medium text-slate-700">Observaciones finales</label>
                                    <textarea id="observaciones_finales" name="observaciones_finales" rows="3" class="app-input">{{ old('observaciones_finales') }}</textarea>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                    Si el equipo debe quedar patrimonialmente en otra institucion, servicio u oficina, este cierre no alcanza: primero use Actas.
                                </div>

                                <button type="submit" class="btn btn-indigo w-full gap-2">
                                    <x-icon name="check-circle-2" class="h-4 w-4" />
                                    Cerrar ingreso tecnico
                                </button>
                            </form>
                        </section>
                    @else
                        <section class="rounded-[2rem] border border-indigo-200 bg-indigo-50 px-5 py-5 sm:px-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">Pendiente</p>
                            <h3 class="mt-2 text-lg font-semibold text-indigo-950">Primero hay que vincular el equipo</h3>
                            <p class="mt-2 text-sm text-indigo-900">
                                Antes de cerrar el ticket debe asociar un equipo existente o incorporarlo al inventario desde este mismo flujo.
                            </p>
                            @if ($recepcionTecnica->canBeIncorporated())
                                <a href="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.create', ['recepcionTecnica' => $recepcionTecnica, 'return_to' => $returnTo]) }}" class="btn btn-indigo mt-4 gap-2">
                                    <x-icon name="plus" class="h-4 w-4" />
                                    Vincular equipo
                                </a>
                            @endif
                        </section>
                    @endif
                </aside>
            </section>
        @else
            <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resultado final</p>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">
                        {{ $recepcionTecnica->isCancelled() ? 'Ticket cancelado' : 'Ticket cerrado' }}
                    </h3>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Fecha de egreso</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->entregada_at?->format('d/m/Y H:i') ?: '-' }}</p>
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Retira</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->retiroResumen() }}</p>
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Condicion de egreso</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->condicion_egreso ? $recepcionTecnica->egressConditionLabel() : '-' }}</p>
                    </div>
                    <div class="app-subcard p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Cerro</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->cerradoPor?->name ?: ($recepcionTecnica->anuladaPor?->name ?: '-') }}</p>
                    </div>
                </div>

                @if ($recepcionTecnica->isCancelled())
                    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-900">
                        <p class="font-semibold">Motivo de cancelacion</p>
                        <p class="mt-2 whitespace-pre-line">{{ $recepcionTecnica->motivo_anulacion ?: 'No informado.' }}</p>
                    </div>
                @else
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diagnostico</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->diagnostico ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Accion realizada</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->accion_realizada ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solucion aplicada</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->solucion_aplicada ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Informe tecnico</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->informe_tecnico ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observaciones finales</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_cierre ?: '-' }}</p>
                    </div>
                @endif
            </section>
        @endif

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(21rem,0.95fr)]">
            <div class="space-y-6">
                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resumen del ticket</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Contexto operativo</h3>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Ticket publico</p>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-sm font-medium text-indigo-700 hover:text-indigo-800">
                                {{ $publicUrl }}
                            </a>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Area / institucion de entrega</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ collect([$recepcionTecnica->persona_area, $recepcionTecnica->persona_institucion])->filter()->implode(' / ') ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Persona de entrega</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->persona_nombre ?: '-' }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ collect([$recepcionTecnica->persona_documento, $recepcionTecnica->persona_telefono])->filter()->implode(' / ') ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Relacion con el equipo</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->persona_relacion_equipo ?: '-' }}</p>
                        </div>
                    </div>
                </section>

                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Equipo y procedencia</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Identificacion</h3>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Referencia</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->referencia_equipo ?: $recepcionTecnica->equipmentReference() }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Codigo interno</p>
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
                            <p class="text-xs uppercase tracking-wide text-slate-500">Bien patrimonial</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->bien_patrimonial ?: '-' }}</p>
                        </div>
                    </div>
                </section>

                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recepcion reportada</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Falla inicial</h3>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Motivo principal</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $recepcionTecnica->falla_motivo ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripcion</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->descripcion_falla ?: '-' }}</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Accesorios recibidos</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->accesorios_entregados ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado fisico inicial</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->estado_fisico_inicial ?: '-' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observaciones visibles</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_recepcion ?: '-' }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                @if ($equipo)
                    <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                        <div class="border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Patrimonio</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Ubicacion real del equipo</h3>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Situacion temporal</p>
                                <p class="mt-2 text-sm font-semibold text-amber-950">Mesa Tecnica / Nivel Central / {{ $recepcionTecnica->statusLabel() }}</p>
                                <p class="mt-1 text-sm text-amber-900">
                                    {{ collect([$equipo->oficina?->service?->institution?->nombre, $equipo->oficina?->service?->nombre, $equipo->oficina?->nombre])->filter()->implode(' / ') ?: '-' }}
                                </p>
                            </div>

                            <p class="text-sm text-slate-600">
                                Este ticket no cambia la ubicacion patrimonial del equipo. Si al finalizar debe quedar en otro destino, primero genere el acta correspondiente.
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('equipos.show', $equipo) }}" class="btn btn-slate">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Abrir ficha
                                </a>
                                <a href="{{ route('actas.create') }}" class="btn btn-slate">
                                    <x-icon name="clipboard-list" class="h-4 w-4" />
                                    Usar Actas
                                </a>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($recepcionTecnica->maintenanceRecord)
                    <section class="rounded-[2rem] border border-emerald-200 bg-emerald-50 px-5 py-5 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Historial tecnico</p>
                        <h3 class="mt-2 text-lg font-semibold text-emerald-950">Mantenimiento generado</h3>
                        <p class="mt-2 text-sm text-emerald-900">
                            El cierre de este ticket genero automaticamente el registro <strong>{{ $recepcionTecnica->maintenanceRecord->titulo }}</strong>.
                        </p>
                        @if ($equipo)
                            <a href="{{ route('equipos.show', $equipo) }}#mantenimiento-{{ $recepcionTecnica->maintenanceRecord->id }}" class="btn btn-emerald mt-4 gap-2">
                                <x-icon name="wrench" class="h-4 w-4" />
                                Ver en mantenimiento
                            </a>
                        @endif
                    </section>
                @endif

                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Trazabilidad</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Historial del ticket</h3>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Ingresado</span>
                            <span class="text-right font-semibold text-slate-900">{{ $recepcionTecnica->ingresado_at?->format('d/m/Y H:i') ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Ultimo cambio de estado</span>
                            <span class="text-right font-semibold text-slate-900">{{ $recepcionTecnica->status_changed_at?->format('d/m/Y H:i') ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Recibido por</span>
                            <span class="text-right font-semibold text-slate-900">{{ $recepcionTecnica->recibidoPor?->name ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Cerrado por</span>
                            <span class="text-right font-semibold text-slate-900">{{ $recepcionTecnica->cerradoPor?->name ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Anulado por</span>
                            <span class="text-right font-semibold text-slate-900">{{ $recepcionTecnica->anuladaPor?->name ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Impresiones</span>
                            <span class="text-right font-semibold text-slate-900">{{ (int) $recepcionTecnica->print_count }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-500">Ultima impresion</span>
                            <span class="text-right font-semibold text-slate-900">{{ $recepcionTecnica->last_printed_at?->format('d/m/Y H:i') ?: '-' }}</span>
                        </div>
                    </div>
                </section>
            </aside>
        </section>
    </div>
@endsection
