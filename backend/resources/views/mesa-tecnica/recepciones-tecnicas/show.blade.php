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
                        @if ($equipo)
                            <span class="app-badge bg-indigo-50 px-3 text-indigo-700">Patrimonio sin cambios</span>
                        @endif
                    </div>

                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $recepcionTecnica->equipmentReference() }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Ingreso del {{ $recepcionTecnica->ingresado_at?->format('d/m/Y H:i') ?: '-' }}
                            recibido por {{ $recepcionTecnica->recibidoPor?->name ?? $recepcionTecnica->creator?->name ?? 'Usuario no disponible' }}.
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
                            Vincular equipo
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(21rem,0.9fr)]">
            <div class="space-y-6">
                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Custodia temporal</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Resumen del ingreso</h3>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Procedencia patrimonial</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->procedenciaResumen() }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Sector receptor</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->institution?->nombre ?: 'Sin institucion' }} / {{ $recepcionTecnica->sector_receptor }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Entrega</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->receptorResumen() }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ collect([$recepcionTecnica->persona_area, $recepcionTecnica->persona_institucion])->filter()->implode(' / ') ?: '-' }}</p>
                        </div>
                        <div class="app-subcard p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Ticket publico</p>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-sm font-medium text-indigo-700 hover:text-indigo-800">
                                {{ $publicUrl }}
                            </a>
                        </div>
                    </div>
                </section>

                <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Equipo</p>
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
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Falla reportada</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Recepcion</h3>
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

                @if ($recepcionTecnica->isClosed() || $recepcionTecnica->isCancelled())
                    <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                        <div class="border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cierre</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Resultado final</h3>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
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
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcionTecnica->cerradoPor?->name ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
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
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observaciones finales</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $recepcionTecnica->observaciones_cierre ?: '-' }}</p>
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                @if ($equipo)
                    <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                        <div class="border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Patrimonio</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Ubicacion real</h3>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Situacion temporal</p>
                                <p class="mt-2 text-sm font-semibold text-amber-950">Ingreso tecnico abierto</p>
                                <p class="mt-1 text-sm text-amber-900">Mesa Tecnica / Nivel Central / {{ $recepcionTecnica->statusLabel() }}</p>
                            </div>
                            <div class="app-subcard p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Institucion / servicio / oficina patrimonial</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">
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
                @else
                    <section class="rounded-[2rem] border border-indigo-200 bg-indigo-50 px-5 py-5 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">Pendiente</p>
                        <h3 class="mt-2 text-lg font-semibold text-indigo-950">Aun no esta vinculado a un equipo</h3>
                        <p class="mt-2 text-sm text-indigo-900">
                            Antes de cerrar el ingreso tecnico debe vincular el equipo existente o darlo de alta en inventario.
                        </p>
                        @if ($recepcionTecnica->canBeIncorporated())
                            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.incorporate.create', $recepcionTecnica) }}" class="btn btn-indigo mt-4 gap-2">
                                <x-icon name="plus" class="h-4 w-4" />
                                Vincular equipo
                            </a>
                        @endif
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

                @if (! $recepcionTecnica->isClosed() && ! $recepcionTecnica->isCancelled())
                    <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                        <div class="border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Seguimiento</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Actualizar estado tecnico</h3>
                        </div>

                        <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.status.update', $recepcionTecnica) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                                <select id="estado" name="estado" class="app-input">
                                    @foreach ($trackingStatusOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('estado', $recepcionTecnica->estado) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('estado')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="observaciones_internas" class="mb-2 block text-sm font-medium text-slate-700">Observaciones internas</label>
                                <textarea id="observaciones_internas" name="observaciones_internas" rows="3" class="app-input" placeholder="Notas de seguimiento">{{ old('observaciones_internas', $recepcionTecnica->observaciones_internas) }}</textarea>
                            </div>

                            <div>
                                <label for="motivo_anulacion" class="mb-2 block text-sm font-medium text-slate-700">Motivo de cancelacion</label>
                                <textarea id="motivo_anulacion" name="motivo_anulacion" rows="3" class="app-input" placeholder="Solo si cancela el ticket">{{ old('motivo_anulacion', $recepcionTecnica->motivo_anulacion) }}</textarea>
                                @error('motivo_anulacion')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-indigo w-full gap-2">
                                <x-icon name="check-circle-2" class="h-4 w-4" />
                                Guardar seguimiento
                            </button>
                        </form>
                    </section>

                    <section class="app-panel rounded-[2rem] px-5 py-5 sm:px-6">
                        <div class="border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cierre</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Entregar y cerrar</h3>
                        </div>

                        <form method="POST" action="{{ route('mesa-tecnica.recepciones-tecnicas.close', $recepcionTecnica) }}" class="mt-4 space-y-4">
                            @csrf

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
                                <input id="persona_retiro_cargo" name="persona_retiro_cargo" type="text" value="{{ old('persona_retiro_cargo') }}" class="app-input" placeholder="Chofer, tecnico local, referente, etc.">
                            </div>

                            <div>
                                <label for="diagnostico" class="mb-2 block text-sm font-medium text-slate-700">Diagnostico</label>
                                <textarea id="diagnostico" name="diagnostico" rows="3" class="app-input">{{ old('diagnostico', $recepcionTecnica->diagnostico) }}</textarea>
                                @error('diagnostico')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="accion_realizada" class="mb-2 block text-sm font-medium text-slate-700">Accion realizada</label>
                                <textarea id="accion_realizada" name="accion_realizada" rows="3" class="app-input">{{ old('accion_realizada', $recepcionTecnica->accion_realizada) }}</textarea>
                                @error('accion_realizada')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="solucion_aplicada" class="mb-2 block text-sm font-medium text-slate-700">Solucion aplicada</label>
                                <textarea id="solucion_aplicada" name="solucion_aplicada" rows="3" class="app-input">{{ old('solucion_aplicada', $recepcionTecnica->solucion_aplicada) }}</textarea>
                                @error('solucion_aplicada')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="informe_tecnico" class="mb-2 block text-sm font-medium text-slate-700">Informe tecnico breve</label>
                                <textarea id="informe_tecnico" name="informe_tecnico" rows="3" class="app-input">{{ old('informe_tecnico', $recepcionTecnica->informe_tecnico) }}</textarea>
                                @error('informe_tecnico')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="observaciones_finales" class="mb-2 block text-sm font-medium text-slate-700">Observaciones finales</label>
                                <textarea id="observaciones_finales" name="observaciones_finales" rows="3" class="app-input">{{ old('observaciones_finales') }}</textarea>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                Si el equipo debe quedar patrimonialmente en otra institucion, servicio u oficina, este cierre no alcanza: primero use el modulo de Actas.
                            </div>

                            <button type="submit" class="btn btn-indigo w-full gap-2">
                                <x-icon name="check-circle-2" class="h-4 w-4" />
                                Cerrar ingreso tecnico
                            </button>
                        </form>
                    </section>
                @endif
            </aside>
        </section>
    </div>
@endsection
