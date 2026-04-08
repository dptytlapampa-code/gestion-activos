@extends('layouts.app')

@section('title', 'Ingreso tecnico '.$recepcionTecnica->codigo)
@section('header', 'Ingreso tecnico')

@section('content')
    @include('mesa-tecnica.partials.module-styles')

    @php
        $equipo = $recepcionTecnica->resolvedEquipo();
        $isOperational = ! $recepcionTecnica->isClosed() && ! $recepcionTecnica->isCancelled();
        $operationPanelClass = $recepcionTecnica->isReadyForDelivery()
            ? 'mt-kpi-card mt-kpi-card-ready'
            : ($recepcionTecnica->isCancelled() ? 'mt-kpi-card mt-kpi-card-danger' : 'mt-kpi-card');
        $operationIcon = $recepcionTecnica->isReadyForDelivery()
            ? 'check-circle-2'
            : ($recepcionTecnica->isCancelled() ? 'x' : ($recepcionTecnica->estado === \App\Models\RecepcionTecnica::ESTADO_EN_REPARACION ? 'wrench' : 'clipboard-list'));
        $operationIconTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-700'
            : ($recepcionTecnica->isCancelled() ? 'text-rose-700' : 'text-indigo-700');
        $operationEyebrowTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-700'
            : ($recepcionTecnica->isCancelled() ? 'text-rose-700' : 'text-slate-500');
        $operationTitleTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-950'
            : ($recepcionTecnica->isCancelled() ? 'text-rose-950' : 'text-slate-950');
        $operationTextTone = $recepcionTecnica->isReadyForDelivery()
            ? 'text-emerald-900'
            : ($recepcionTecnica->isCancelled() ? 'text-rose-900' : 'text-slate-600');
        $inventorySummary = $equipo?->codigo_interno ?: ($recepcionTecnica->codigo_interno_equipo ?: 'Pendiente de vincular');
        $heroFacts = [
            ['icon' => 'building-2', 'label' => 'Procedencia', 'value' => $recepcionTecnica->procedenciaResumen()],
            ['icon' => 'users', 'label' => 'Entrega', 'value' => $recepcionTecnica->receptorResumen()],
            ['icon' => 'monitor', 'label' => 'Inventario', 'value' => $inventorySummary],
            ['icon' => 'map-pin', 'label' => 'Sector receptor', 'value' => ($recepcionTecnica->institution?->nombre ?: 'Sin institucion').' / '.$recepcionTecnica->sector_receptor],
        ];
        $finalFacts = [
            ['icon' => 'door-closed', 'label' => 'Fecha de egreso', 'value' => $recepcionTecnica->entregada_at?->format('d/m/Y H:i') ?: '-'],
            ['icon' => 'users', 'label' => 'Retira', 'value' => $recepcionTecnica->retiroResumen()],
            ['icon' => 'shield-check', 'label' => 'Condicion de egreso', 'value' => $recepcionTecnica->condicion_egreso ? $recepcionTecnica->egressConditionLabel() : '-'],
            ['icon' => 'file-text', 'label' => 'Cerro', 'value' => $recepcionTecnica->cerradoPor?->name ?: ($recepcionTecnica->anuladaPor?->name ?: '-')],
        ];
        $contextCards = [
            ['icon' => 'building-2', 'label' => 'Area / institucion de entrega', 'value' => collect([$recepcionTecnica->persona_area, $recepcionTecnica->persona_institucion])->filter()->implode(' / ') ?: '-'],
            ['icon' => 'users', 'label' => 'Persona de entrega', 'value' => $recepcionTecnica->persona_nombre ?: '-', 'meta' => collect([$recepcionTecnica->persona_documento, $recepcionTecnica->persona_telefono])->filter()->implode(' / ') ?: '-'],
            ['icon' => 'shield-check', 'label' => 'Relacion con el equipo', 'value' => $recepcionTecnica->persona_relacion_equipo ?: '-'],
        ];
        $equipmentCards = [
            ['icon' => 'monitor', 'label' => 'Referencia', 'value' => $recepcionTecnica->referencia_equipo ?: $recepcionTecnica->equipmentReference()],
            ['icon' => 'file-text', 'label' => 'Codigo interno', 'value' => $recepcionTecnica->codigo_interno_equipo ?: 'Todavia no vinculado'],
            ['icon' => 'layers', 'label' => 'Tipo', 'value' => $recepcionTecnica->tipo_equipo_texto ?: '-'],
            ['icon' => 'dashboard', 'label' => 'Marca / modelo', 'value' => trim(implode(' / ', array_filter([$recepcionTecnica->marca, $recepcionTecnica->modelo]))) ?: '-'],
            ['icon' => 'info', 'label' => 'Serie', 'value' => $recepcionTecnica->numero_serie ?: '-'],
            ['icon' => 'shield-check', 'label' => 'Bien patrimonial', 'value' => $recepcionTecnica->bien_patrimonial ?: '-'],
        ];
        $traceItems = [
            ['icon' => 'file-text', 'label' => 'Ingresado', 'value' => $recepcionTecnica->ingresado_at?->format('d/m/Y H:i') ?: '-'],
            ['icon' => 'sliders-horizontal', 'label' => 'Ultimo cambio de estado', 'value' => $recepcionTecnica->status_changed_at?->format('d/m/Y H:i') ?: '-'],
            ['icon' => 'users', 'label' => 'Recibido por', 'value' => $recepcionTecnica->recibidoPor?->name ?: '-'],
            ['icon' => 'door-closed', 'label' => 'Cerrado por', 'value' => $recepcionTecnica->cerradoPor?->name ?: '-'],
            ['icon' => 'x', 'label' => 'Anulado por', 'value' => $recepcionTecnica->anuladaPor?->name ?: '-'],
            ['icon' => 'printer', 'label' => 'Impresiones', 'value' => (string) ((int) $recepcionTecnica->print_count)],
            ['icon' => 'printer', 'label' => 'Ultima impresion', 'value' => $recepcionTecnica->last_printed_at?->format('d/m/Y H:i') ?: '-'],
        ];
        $showInitialFailureOpen = $isOperational;
    @endphp

    <div class="space-y-5 lg:space-y-6">
        <section class="app-panel mt-panel mt-panel-soft rounded-[2rem] px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge inline-flex items-center gap-1.5 bg-slate-900 px-3 text-white">
                            <x-icon name="file-text" class="h-3.5 w-3.5" />
                            {{ $recepcionTecnica->codigo }}
                        </span>
                        @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcionTecnica->estado, 'label' => $recepcionTecnica->statusLabel()])
                        @if ($recepcionTecnica->isReadyForDelivery())
                            <span class="app-badge inline-flex items-center gap-1.5 bg-emerald-50 px-3 text-emerald-700">
                                <x-icon name="check-circle-2" class="h-3.5 w-3.5" />
                                Prioridad de entrega
                            </span>
                        @endif
                        @if ($equipo)
                            <span class="app-badge inline-flex items-center gap-1.5 bg-indigo-50 px-3 text-indigo-700">
                                <x-icon name="shield-check" class="h-3.5 w-3.5" />
                                Patrimonio sin cambios
                            </span>
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
                        @foreach ($heroFacts as $fact)
                            <div class="app-subcard mt-card-lift p-4">
                                <p class="mt-inline-meta text-xs uppercase tracking-wide text-slate-500">
                                    <x-icon name="{{ $fact['icon'] }}" class="h-3.5 w-3.5" />
                                    {{ $fact['label'] }}
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $fact['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-full max-w-xl space-y-3 xl:max-w-sm">
                    <div class="{{ $operationPanelClass }}">
                        <div class="flex items-start gap-3">
                            <span class="mt-icon-chip {{ $operationIconTone }}">
                                <x-icon name="{{ $operationIcon }}" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] {{ $operationEyebrowTone }}">Accion principal</p>
                                <h3 class="mt-2 text-lg font-semibold {{ $operationTitleTone }}">{{ $recepcionTecnica->nextActionLabel() }}</h3>
                                <p class="mt-2 text-sm leading-6 {{ $operationTextTone }}">{{ $recepcionTecnica->nextActionDescription() }}</p>
                            </div>
                        </div>
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
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.create', ['recepcionTecnica' => $recepcionTecnica, 'return_to' => $returnTo]) }}" class="btn btn-indigo mt-primary-action">
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
                <section class="app-panel mt-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="flex items-start gap-3 border-b border-slate-200 pb-4">
                        <span class="mt-icon-chip text-indigo-700">
                            <x-icon name="wrench" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Panel operativo</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Registrar diagnostico y seguimiento</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Las acciones tecnicas principales quedan arriba para no obligar a recorrer el ticket completo.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.status.update', $recepcionTecnica) }}" class="mt-4 space-y-5">
                        @csrf
                        @method('PATCH')

                        @if ($returnTo)
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado actual</label>
                                <select id="estado" name="estado" class="app-input mt-input">
                                    @foreach ($trackingStatusOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('estado', $recepcionTecnica->estado) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('estado')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-note-block text-sm text-slate-700">
                                <p class="mt-inline-meta text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    <x-icon name="clipboard-list" class="h-3.5 w-3.5" />
                                    Siguiente accion sugerida
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">{{ $recepcionTecnica->nextActionLabel() }}</p>
                                <p class="mt-1">{{ $recepcionTecnica->nextActionDescription() }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="diagnostico" class="mb-2 block text-sm font-medium text-slate-700">Diagnostico tecnico</label>
                                <textarea id="diagnostico" name="diagnostico" rows="4" class="app-input mt-input" placeholder="Que se detecto hasta ahora">{{ old('diagnostico', $recepcionTecnica->diagnostico) }}</textarea>
                            </div>

                            <div>
                                <label for="accion_realizada" class="mb-2 block text-sm font-medium text-slate-700">Accion realizada</label>
                                <textarea id="accion_realizada" name="accion_realizada" rows="4" class="app-input mt-input" placeholder="Revision, reemplazo, prueba, limpieza, etc.">{{ old('accion_realizada', $recepcionTecnica->accion_realizada) }}</textarea>
                            </div>

                            <div>
                                <label for="solucion_aplicada" class="mb-2 block text-sm font-medium text-slate-700">Solucion aplicada</label>
                                <textarea id="solucion_aplicada" name="solucion_aplicada" rows="4" class="app-input mt-input" placeholder="Resultado tecnico concreto">{{ old('solucion_aplicada', $recepcionTecnica->solucion_aplicada) }}</textarea>
                            </div>

                            <div>
                                <label for="informe_tecnico" class="mb-2 block text-sm font-medium text-slate-700">Informe tecnico breve</label>
                                <textarea id="informe_tecnico" name="informe_tecnico" rows="4" class="app-input mt-input" placeholder="Resumen entendible para el retiro o para mantenimiento">{{ old('informe_tecnico', $recepcionTecnica->informe_tecnico) }}</textarea>
                            </div>
                        </div>

                        <div>
                            <label for="observaciones_internas" class="mb-2 block text-sm font-medium text-slate-700">Observaciones internas</label>
                            <textarea id="observaciones_internas" name="observaciones_internas" rows="3" class="app-input mt-input" placeholder="Notas de seguimiento interno">{{ old('observaciones_internas', $recepcionTecnica->observaciones_internas) }}</textarea>
                        </div>

                        <div>
                            <label for="motivo_anulacion" class="mb-2 block text-sm font-medium text-slate-700">Motivo de cancelacion</label>
                            <textarea id="motivo_anulacion" name="motivo_anulacion" rows="3" class="app-input mt-input" placeholder="Solo si cancela el ticket">{{ old('motivo_anulacion', $recepcionTecnica->motivo_anulacion) }}</textarea>
                            @error('motivo_anulacion')
                                <p class="form-error mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500">
                                Guardar aqui no cierra el ticket. Solo deja el seguimiento tecnico actualizado.
                            </p>

                            <button type="submit" class="btn btn-indigo mt-primary-action w-full gap-2 sm:w-auto">
                                <x-icon name="check-circle-2" class="h-4 w-4" />
                                Guardar seguimiento
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-6">
                    @if ($equipo)
                        <section class="app-panel mt-panel rounded-[2rem] px-5 py-5 sm:px-6">
                            <div class="flex items-start gap-3 border-b border-slate-200 pb-4">
                                <span class="mt-icon-chip text-emerald-700">
                                    <x-icon name="door-closed" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cierre</p>
                                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Entregar y cerrar ticket</h3>
                                    <p class="mt-1 text-sm text-slate-600">
                                        El historial tecnico final se genera automaticamente al cerrar.
                                    </p>
                                </div>
                            </div>

                            @if ($recepcionTecnica->isReadyForDelivery())
                                <div class="mt-note-block mt-state-ready mt-4 text-sm text-emerald-900">
                                    <p class="mt-inline-meta font-semibold text-emerald-800">
                                        <x-icon name="check-circle-2" class="h-4 w-4" />
                                        Ticket listo para entregar
                                    </p>
                                    <p class="mt-2">Este es el siguiente paso natural para sacarlo de la vista operativa.</p>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.close', $recepcionTecnica) }}" class="mt-4 space-y-4">
                                @csrf

                                @if ($returnTo)
                                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                @endif

                                <div>
                                    <label for="estado_cierre" class="mb-2 block text-sm font-medium text-slate-700">Resultado del cierre</label>
                                    <select id="estado_cierre" name="estado_cierre" class="app-input mt-input">
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
                                    <select id="condicion_egreso" name="condicion_egreso" class="app-input mt-input">
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
                                    <input id="fecha_entrega_real" name="fecha_entrega_real" type="datetime-local" value="{{ old('fecha_entrega_real', now()->format('Y-m-d\\TH:i')) }}" class="app-input mt-input">
                                    @error('fecha_entrega_real')
                                        <p class="form-error mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="persona_retiro_nombre" class="mb-2 block text-sm font-medium text-slate-700">Quien retira</label>
                                        <input id="persona_retiro_nombre" name="persona_retiro_nombre" type="text" value="{{ old('persona_retiro_nombre') }}" class="app-input mt-input" placeholder="Nombre y apellido">
                                        @error('persona_retiro_nombre')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="persona_retiro_documento" class="mb-2 block text-sm font-medium text-slate-700">Documento</label>
                                        <input id="persona_retiro_documento" name="persona_retiro_documento" type="text" value="{{ old('persona_retiro_documento') }}" class="app-input mt-input" placeholder="Opcional">
                                    </div>
                                </div>

                                <div>
                                    <label for="persona_retiro_cargo" class="mb-2 block text-sm font-medium text-slate-700">Cargo o referencia</label>
                                    <input id="persona_retiro_cargo" name="persona_retiro_cargo" type="text" value="{{ old('persona_retiro_cargo') }}" class="app-input mt-input" placeholder="Chofer, referente, tecnico local, etc.">
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="cierre_diagnostico" class="mb-2 block text-sm font-medium text-slate-700">Diagnostico final</label>
                                        <textarea id="cierre_diagnostico" name="diagnostico" rows="4" class="app-input mt-input">{{ old('diagnostico', $recepcionTecnica->diagnostico) }}</textarea>
                                        @error('diagnostico')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cierre_accion_realizada" class="mb-2 block text-sm font-medium text-slate-700">Accion realizada</label>
                                        <textarea id="cierre_accion_realizada" name="accion_realizada" rows="4" class="app-input mt-input">{{ old('accion_realizada', $recepcionTecnica->accion_realizada) }}</textarea>
                                        @error('accion_realizada')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cierre_solucion_aplicada" class="mb-2 block text-sm font-medium text-slate-700">Solucion aplicada</label>
                                        <textarea id="cierre_solucion_aplicada" name="solucion_aplicada" rows="4" class="app-input mt-input">{{ old('solucion_aplicada', $recepcionTecnica->solucion_aplicada) }}</textarea>
                                        @error('solucion_aplicada')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cierre_informe_tecnico" class="mb-2 block text-sm font-medium text-slate-700">Informe tecnico breve</label>
                                        <textarea id="cierre_informe_tecnico" name="informe_tecnico" rows="4" class="app-input mt-input">{{ old('informe_tecnico', $recepcionTecnica->informe_tecnico) }}</textarea>
                                        @error('informe_tecnico')
                                            <p class="form-error mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="observaciones_finales" class="mb-2 block text-sm font-medium text-slate-700">Observaciones finales</label>
                                    <textarea id="observaciones_finales" name="observaciones_finales" rows="3" class="app-input mt-input">{{ old('observaciones_finales') }}</textarea>
                                </div>

                                <div class="mt-note-block text-sm text-slate-700">
                                    <p class="mt-inline-meta font-semibold text-slate-700">
                                        <x-icon name="clipboard-list" class="h-4 w-4" />
                                        Validacion patrimonial
                                    </p>
                                    <p class="mt-2">Si el equipo debe quedar patrimonialmente en otra institucion, servicio u oficina, este cierre no alcanza: primero use Actas.</p>
                                </div>

                                <button type="submit" class="btn btn-indigo mt-primary-action w-full gap-2">
                                    <x-icon name="check-circle-2" class="h-4 w-4" />
                                    Cerrar ingreso tecnico
                                </button>
                            </form>
                        </section>
                    @else
                        <section class="app-panel mt-panel rounded-[2rem] border-indigo-200 bg-indigo-50 px-5 py-5 sm:px-6">
                            <div class="flex items-start gap-3">
                                <span class="mt-icon-chip text-indigo-700">
                                    <x-icon name="plus" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">Pendiente</p>
                                    <h3 class="mt-2 text-lg font-semibold text-indigo-950">Primero hay que vincular el equipo</h3>
                                    <p class="mt-2 text-sm text-indigo-900">
                                        Antes de cerrar el ticket debe asociar un equipo existente o incorporarlo al inventario desde este mismo flujo.
                                    </p>
                                </div>
                            </div>
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
            <section @class([
                'app-panel mt-panel rounded-[2rem] px-5 py-5 sm:px-6',
                'mt-panel-danger' => $recepcionTecnica->isCancelled(),
                'mt-panel-warm' => ! $recepcionTecnica->isCancelled(),
            ])>
                <div class="flex items-start gap-3 border-b border-slate-200 pb-4">
                    <span class="mt-icon-chip @if($recepcionTecnica->isCancelled()) text-rose-700 @else text-amber-700 @endif">
                        <x-icon name="{{ $recepcionTecnica->isCancelled() ? 'x' : 'door-closed' }}" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resultado final</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">
                            {{ $recepcionTecnica->isCancelled() ? 'Ticket cancelado' : 'Ticket cerrado' }}
                        </h3>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($finalFacts as $fact)
                        <div class="app-subcard mt-card-lift p-4">
                            <p class="mt-inline-meta text-xs uppercase tracking-wide text-slate-500">
                                <x-icon name="{{ $fact['icon'] }}" class="h-3.5 w-3.5" />
                                {{ $fact['label'] }}
                            </p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $fact['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($recepcionTecnica->isCancelled())
                    <div class="mt-note-block mt-state-cancelled mt-4 text-sm text-rose-900">
                        <p class="mt-inline-meta font-semibold text-rose-800">
                            <x-icon name="alert-circle" class="h-4 w-4" />
                            Motivo de cancelacion
                        </p>
                        <p class="mt-2 whitespace-pre-line">{{ $recepcionTecnica->motivo_anulacion ?: 'No informado.' }}</p>
                    </div>
                @else
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-subcard p-4">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="search" class="h-3.5 w-3.5" />
                                Diagnostico
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->diagnostico ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="wrench" class="h-3.5 w-3.5" />
                                Accion realizada
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->accion_realizada ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="shield-check" class="h-3.5 w-3.5" />
                                Solucion aplicada
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->solucion_aplicada ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="file-text" class="h-3.5 w-3.5" />
                                Informe tecnico
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->informe_tecnico ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-note-block mt-4 text-slate-700">
                        <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <x-icon name="clipboard-list" class="h-3.5 w-3.5" />
                            Observaciones finales
                        </p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_cierre ?: '-' }}</p>
                    </div>
                @endif
            </section>
        @endif

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(21rem,0.95fr)]">
            <div class="space-y-6">
                <x-collapsible-panel
                    title="Contexto operativo"
                    eyebrow="Resumen del ticket"
                    icon="file-text"
                    summary="Procedencia, entrega y seguimiento publico."
                    :default-open="false"
                    :persist-key="'mesa-tecnica-ticket-'.$recepcionTecnica->id.'.contexto'"
                    class="mt-panel"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="app-subcard mt-card-lift p-4">
                            <p class="mt-inline-meta text-xs uppercase tracking-wide text-slate-500">
                                <x-icon name="external-link" class="h-3.5 w-3.5" />
                                Ticket publico
                            </p>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 block break-all text-sm font-medium text-indigo-700 transition hover:text-indigo-800">
                                {{ $publicUrl }}
                            </a>
                        </div>

                        @foreach ($contextCards as $card)
                            <div class="app-subcard mt-card-lift p-4">
                                <p class="mt-inline-meta text-xs uppercase tracking-wide text-slate-500">
                                    <x-icon name="{{ $card['icon'] }}" class="h-3.5 w-3.5" />
                                    {{ $card['label'] }}
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $card['value'] }}</p>
                                @if (! empty($card['meta']))
                                    <p class="mt-1 text-sm text-slate-600">{{ $card['meta'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-collapsible-panel>

                <x-collapsible-panel
                    title="Identificacion"
                    eyebrow="Equipo y procedencia"
                    icon="monitor"
                    summary="Referencia, inventario, tipo, serie y patrimonial."
                    :default-open="false"
                    :persist-key="'mesa-tecnica-ticket-'.$recepcionTecnica->id.'.identificacion'"
                    class="mt-panel"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($equipmentCards as $card)
                            <div class="app-subcard mt-card-lift p-4">
                                <p class="mt-inline-meta text-xs uppercase tracking-wide text-slate-500">
                                    <x-icon name="{{ $card['icon'] }}" class="h-3.5 w-3.5" />
                                    {{ $card['label'] }}
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $card['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-collapsible-panel>

                <x-collapsible-panel
                    title="Falla inicial"
                    eyebrow="Recepcion reportada"
                    icon="alert-circle"
                    summary="Motivo, descripcion, accesorios y estado fisico inicial."
                    :default-open="$showInitialFailureOpen"
                    :persist-key="'mesa-tecnica-ticket-'.$recepcionTecnica->id.'.falla-inicial'"
                    class="mt-panel"
                    icon-class="text-amber-700"
                >
                    <div class="space-y-4">
                        <div class="app-subcard p-4">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="alert-circle" class="h-3.5 w-3.5" />
                                Motivo principal
                            </p>
                            <p class="mt-2 text-sm text-slate-900">{{ $recepcionTecnica->falla_motivo ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="file-text" class="h-3.5 w-3.5" />
                                Descripcion
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->descripcion_falla ?: '-' }}</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="app-subcard p-4">
                                <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <x-icon name="boxes" class="h-3.5 w-3.5" />
                                    Accesorios recibidos
                                </p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->accesorios_entregados ?: '-' }}</p>
                            </div>
                            <div class="app-subcard p-4">
                                <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <x-icon name="info" class="h-3.5 w-3.5" />
                                    Estado fisico inicial
                                </p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->estado_fisico_inicial ?: '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-note-block text-slate-700">
                            <p class="mt-inline-meta text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <x-icon name="clipboard-list" class="h-3.5 w-3.5" />
                                Observaciones visibles
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_recepcion ?: '-' }}</p>
                        </div>
                    </div>
                </x-collapsible-panel>
            </div>

            <aside class="space-y-6">
                @if ($equipo)
                    <x-collapsible-panel
                        title="Ubicacion real del equipo"
                        eyebrow="Patrimonio"
                        icon="shield-check"
                        summary="La custodia temporal no altera el destino patrimonial."
                        :default-open="false"
                        :persist-key="'mesa-tecnica-ticket-'.$recepcionTecnica->id.'.patrimonio'"
                        class="mt-panel"
                        icon-class="text-amber-700"
                    >
                        <div class="space-y-4">
                            <div class="mt-note-block mt-panel-warm text-amber-950">
                                <p class="mt-inline-meta text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">
                                    <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                    Situacion temporal
                                </p>
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
                    </x-collapsible-panel>
                @endif

                @if ($recepcionTecnica->maintenanceRecord)
                    <x-collapsible-panel
                        title="Mantenimiento generado"
                        eyebrow="Historial tecnico"
                        icon="wrench"
                        summary="El cierre del ticket creo un registro tecnico asociado."
                        :default-open="false"
                        :persist-key="'mesa-tecnica-ticket-'.$recepcionTecnica->id.'.mantenimiento-generado'"
                        class="mt-panel mt-panel-ready"
                        icon-class="text-emerald-700"
                        eyebrow-class="text-emerald-700"
                        title-class="text-emerald-950"
                        description-class="text-emerald-900"
                        summary-class="text-emerald-900"
                    >
                        <div class="space-y-4">
                            <p class="text-sm text-emerald-900">
                                El cierre de este ticket genero automaticamente el registro <strong>{{ $recepcionTecnica->maintenanceRecord->titulo }}</strong>.
                            </p>

                            @if ($equipo)
                                <a href="{{ route('equipos.show', $equipo) }}#mantenimiento-{{ $recepcionTecnica->maintenanceRecord->id }}" class="btn btn-emerald gap-2">
                                    <x-icon name="wrench" class="h-4 w-4" />
                                    Ver en mantenimiento
                                </a>
                            @endif
                        </div>
                    </x-collapsible-panel>
                @endif

                <x-collapsible-panel
                    title="Historial del ticket"
                    eyebrow="Trazabilidad"
                    icon="file-text"
                    summary="Fechas clave, impresiones y responsables del circuito."
                    :default-open="false"
                    :persist-key="'mesa-tecnica-ticket-'.$recepcionTecnica->id.'.trazabilidad'"
                    class="mt-panel"
                >
                    <div class="space-y-3 text-sm">
                        @foreach ($traceItems as $item)
                            <div class="mt-trace-item">
                                <span class="mt-inline-meta text-slate-500">
                                    <x-icon name="{{ $item['icon'] }}" class="h-4 w-4" />
                                    {{ $item['label'] }}
                                </span>
                                <span class="text-right font-semibold text-slate-900">{{ $item['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-collapsible-panel>
            </aside>
        </section>
    </div>
@endsection
