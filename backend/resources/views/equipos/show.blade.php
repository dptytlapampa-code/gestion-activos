@extends('layouts.app')

@section('title', 'Detalle equipo')
@section('header', 'Detalle equipo')

@section('content')
@php
    use App\Models\Equipo;
    use App\Models\EquipoDocumento;
    use App\Models\Mantenimiento;

    $erroresMantenimiento = collect([
        'fecha',
        'tipo',
        'titulo',
        'detalle',
        'proveedor',
        'fecha_ingreso_st',
        'fecha_egreso_st',
        'mantenimiento',
    ])->contains(fn (string $campo): bool => $errors->has($campo));

    $erroresDocumentos = collect(['note', 'file'])->contains(fn (string $campo): bool => $errors->has($campo));
    $documentContext = (string) old('document_context');
    $tabConErrores = match (true) {
        $erroresMantenimiento => 'mantenimiento',
        $erroresDocumentos && str_starts_with($documentContext, 'movimiento:') => 'movimientos',
        $erroresDocumentos && str_starts_with($documentContext, 'mantenimiento:') => 'mantenimiento',
        $erroresDocumentos => 'documentos',
        default => 'informacion',
    };

    $tipoInicialMantenimiento = old(
        'tipo',
        $mantenimientoExternoAbierto !== null
            ? Mantenimiento::TIPO_ALTA
            : ($tiposMantenimientoDisponibles[0] ?? Mantenimiento::TIPO_INTERNO)
    );
@endphp

<div
    class="space-y-6"
    x-data="{
        activeTab: @js($tabConErrores),
        tipo: @js($tipoInicialMantenimiento),
        showMantenimientoForm: @js($erroresMantenimiento || $mantenimientoExternoAbierto !== null),
        showDocumentoForm: @js($erroresDocumentos && ! str_starts_with($documentContext, 'movimiento:') && ! str_starts_with($documentContext, 'mantenimiento:')),
    }"
>
    <div class="card">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-3">
                <div class="flex items-start gap-4">
                    <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="lg" class="rounded-xl" />
                    <div>
                        <h2 class="text-2xl font-bold uppercase tracking-wide text-slate-900 md:text-3xl">
                            {{ $equipo->tipo }} {{ $equipo->marca }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        Codigo interno
                        <span class="ml-2 font-mono font-semibold text-slate-900">{{ $equipo->codigo_interno }}</span>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Nro Serie: {{ $equipo->numero_serie ?: '-' }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Bien patrimonial: {{ $equipo->bien_patrimonial ?: '-' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 md:items-end">
                <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold uppercase"
                      @class([
                        'bg-green-100 text-green-800' => $equipo->estado === 'operativo',
                        'bg-blue-100 text-blue-800' => $equipo->estado === 'prestado',
                        'bg-yellow-100 text-yellow-800' => $equipo->estado === 'mantenimiento',
                        'bg-orange-100 text-orange-800' => $equipo->estado === 'fuera_de_servicio',
                        'bg-red-100 text-red-800' => $equipo->estado === 'baja',
                        'bg-slate-100 text-slate-700' => ! in_array($equipo->estado, ['operativo', 'prestado', 'mantenimiento', 'fuera_de_servicio', 'baja']),
                      ])>
                    Estado: {{ strtoupper(str_replace('_', ' ', $equipo->estado)) }}
                </span>

                <a href="{{ route('equipos.edit', $equipo) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    <x-icon name="pencil" class="h-4 w-4" />
                    Editar equipo
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex flex-wrap gap-2 md:gap-6">
                <button type="button" @click="activeTab = 'informacion'" :class="activeTab === 'informacion' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="border-b-2 px-1 pb-3 text-sm font-semibold transition">Informacion</button>
                <button type="button" @click="activeTab = 'mantenimiento'" :class="activeTab === 'mantenimiento' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="border-b-2 px-1 pb-3 text-sm font-semibold transition">Mantenimiento</button>
                <button type="button" @click="activeTab = 'movimientos'" :class="activeTab === 'movimientos' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="border-b-2 px-1 pb-3 text-sm font-semibold transition">Movimientos</button>
                <button type="button" @click="activeTab = 'documentos'" :class="activeTab === 'documentos' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="border-b-2 px-1 pb-3 text-sm font-semibold transition">Documentos</button>
            </nav>
        </div>

        <div class="pt-6">
            <section x-show="activeTab === 'informacion'" x-cloak class="space-y-4">
                <dl class="grid gap-4 md:grid-cols-2">
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Codigo interno</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $equipo->codigo_interno }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">
                            <div class="flex items-center gap-3">
                                <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="sm" class="rounded-lg" />
                                <span>{{ $equipo->tipo }}</span>
                            </div>
                        </dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->marca }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->modelo }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Numero de serie</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->numero_serie ?: '-' }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bien patrimonial</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->bien_patrimonial ?: '-' }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado actual</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->equipoStatus?->name ?? ucfirst($equipo->estado) }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha ingreso</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->fecha_ingreso?->format('d/m/Y') }}</dd>
                    </div>
                    <div class="app-subcard p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ubicacion</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}</dd>
                    </div>
                </dl>
            </section>

            <section x-show="activeTab === 'mantenimiento'" x-cloak class="space-y-5">
                @if ($hayInconsistenciaMantenimiento)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-rose-100 p-2 text-rose-600">
                                <x-icon name="alert-circle" class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-rose-900">Estado tecnico inconsistente</h3>
                                <p class="mt-1 text-sm text-rose-800">
                                    El estado actual del equipo no coincide con su historial tecnico. Revise este caso antes de seguir operando sobre el equipo.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($mantenimientoExternoAbierto)
                    <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Mantenimiento externo abierto</p>
                                <h3 class="text-lg font-semibold text-slate-900">Este equipo se encuentra actualmente en servicio tecnico externo.</h3>
                                <p class="text-sm text-slate-700">
                                    Para cerrar este ciclo use <strong>Alta</strong> si vuelve a estado operativo o <strong>Baja</strong> si no regresa al servicio.
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                Abierto desde {{ ($mantenimientoExternoAbierto->fecha_ingreso_st ?? $mantenimientoExternoAbierto->fecha)?->format('d/m/Y') }}
                            </span>
                        </div>

                        <dl class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-xl bg-white/70 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ingreso al ST</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ ($mantenimientoExternoAbierto->fecha_ingreso_st ?? $mantenimientoExternoAbierto->fecha)?->format('d/m/Y') }}</dd>
                            </div>
                            <div class="rounded-xl bg-white/70 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Proveedor</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $mantenimientoExternoAbierto->proveedor ?: 'Sin proveedor informado' }}</dd>
                            </div>
                            <div class="rounded-xl bg-white/70 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registrado por</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $mantenimientoExternoAbierto->creador?->name ?: 'Sin dato' }}</dd>
                            </div>
                            <div class="rounded-xl bg-white/70 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seguimiento</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ $mantenimientoExternoAbierto->fecha_ingreso_st?->diffForHumans() ?? $mantenimientoExternoAbierto->fecha?->diffForHumans() }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                @else
                    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-emerald-100 p-2 text-emerald-600">
                                <x-icon name="check-circle-2" class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-emerald-900">Sin mantenimiento externo abierto</h3>
                                <p class="mt-1 text-sm text-emerald-800">
                                    Puede registrar un nuevo ingreso a mantenimiento externo desde esta ficha. El equipo no quedara en estado Mantenimiento si falla el registro tecnico.
                                </p>
                            </div>
                        </div>
                    </article>
                @endif

                @can('create', \App\Models\Mantenimiento::class)
                    @if (count($tiposMantenimientoDisponibles) > 0)
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Registrar evento tecnico</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    @if ($mantenimientoExternoAbierto)
                                        El formulario ya esta listo para cerrar el mantenimiento externo o agregar una nota tecnica complementaria.
                                    @else
                                        Ingrese aqui el envio a mantenimiento externo o una nota tecnica interna del equipo.
                                    @endif
                                </p>
                            </div>

                            <button type="button" @click="showMantenimientoForm = !showMantenimientoForm" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                <x-icon name="plus" class="h-4 w-4" />
                                {{ $mantenimientoExternoAbierto ? 'Registrar cierre o nota' : 'Registrar mantenimiento' }}
                            </button>
                        </div>

                        <div x-show="showMantenimientoForm" x-cloak class="app-subcard p-4">
                            <form method="POST" action="{{ route('equipos.mantenimientos.store', $equipo) }}" class="grid gap-4 md:grid-cols-2">
                                @csrf

                                <div>
                                    <label class="text-sm font-medium text-slate-700">Fecha del evento <span class="text-red-600">*</span></label>
                                    <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-slate-700">Tipo <span class="text-red-600">*</span></label>
                                    <select name="tipo" x-model="tipo" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                        @foreach ($tiposMantenimientoDisponibles as $tipoDisponible)
                                            <option value="{{ $tipoDisponible }}" @selected(old('tipo') === $tipoDisponible)>
                                                {{ match ($tipoDisponible) {
                                                    Mantenimiento::TIPO_EXTERNO => 'Externo',
                                                    Mantenimiento::TIPO_ALTA => 'Alta',
                                                    Mantenimiento::TIPO_BAJA => 'Baja',
                                                    Mantenimiento::TIPO_INTERNO => 'Interno',
                                                    default => 'Otro',
                                                } }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-sm font-medium text-slate-700">Titulo <span class="text-red-600">*</span></label>
                                    <input type="text" name="titulo" value="{{ old('titulo') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-sm font-medium text-slate-700">Detalle <span class="text-red-600">*</span></label>
                                    <textarea name="detalle" class="mt-1 w-full rounded-lg border-slate-300 text-sm" rows="4" required>{{ old('detalle') }}</textarea>
                                </div>

                                <div x-show="tipo === 'externo'" x-cloak>
                                    <label class="text-sm font-medium text-slate-700">Proveedor</label>
                                    <input type="text" name="proveedor" value="{{ old('proveedor') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-bind:disabled="tipo !== 'externo'">
                                </div>

                                <div x-show="tipo === 'externo'" x-cloak>
                                    <label class="text-sm font-medium text-slate-700">Fecha ingreso ST <span class="text-red-600">*</span></label>
                                    <input type="date" name="fecha_ingreso_st" value="{{ old('fecha_ingreso_st', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-bind:required="tipo === 'externo'" x-bind:disabled="tipo !== 'externo'">
                                </div>

                                <div x-show="tipo === 'alta' || tipo === 'baja'" x-cloak>
                                    <label class="text-sm font-medium text-slate-700">Fecha egreso ST <span class="text-red-600">*</span></label>
                                    <input type="date" name="fecha_egreso_st" value="{{ old('fecha_egreso_st', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-bind:required="tipo === 'alta' || tipo === 'baja'" x-bind:disabled="!(tipo === 'alta' || tipo === 'baja')">
                                </div>

                                <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                    <p x-show="tipo === 'externo'" x-cloak>
                                        El equipo pasara a estado Mantenimiento solo si el ingreso externo se registra correctamente.
                                    </p>
                                    <p x-show="tipo === 'alta'" x-cloak>
                                        El alta cierra el mantenimiento externo abierto y devuelve el equipo a estado Operativo.
                                    </p>
                                    <p x-show="tipo === 'baja'" x-cloak>
                                        La baja cierra el mantenimiento externo abierto y deja el equipo en estado Baja.
                                    </p>
                                    <p x-show="tipo === 'interno' || tipo === 'otro'" x-cloak>
                                        Esta opcion agrega una nota tecnica al historial sin cambiar el estado actual del equipo.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Guardar evento tecnico
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            Este equipo ya se encuentra en baja y no admite nuevos eventos tecnicos.
                        </div>
                    @endif
                @endcan

                <div class="app-table-panel overflow-x-auto rounded-lg">
                    <table class="app-table text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Detalle tecnico</th>
                                <th class="px-4 py-3">Seguimiento</th>
                                <th class="px-4 py-3">Estado resultante</th>
                                <th class="px-4 py-3">Duracion</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($mantenimientos as $mantenimiento)
                            @php
                                $tipoClase = match ($mantenimiento->tipo) {
                                    Mantenimiento::TIPO_EXTERNO => 'bg-amber-100 text-amber-800',
                                    Mantenimiento::TIPO_ALTA => 'bg-emerald-100 text-emerald-800',
                                    Mantenimiento::TIPO_BAJA => 'bg-rose-100 text-rose-800',
                                    Mantenimiento::TIPO_INTERNO => 'bg-sky-100 text-sky-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr id="mantenimiento-{{ $mantenimiento->id }}">
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->fecha?->format('d/m/Y') }}</td>
                                <td class="px-4 py-4 text-slate-700">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $tipoClase }}">
                                        {{ ucfirst($mantenimiento->tipo) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-slate-900">{{ $mantenimiento->titulo }}</p>
                                    <p class="mt-1 text-slate-700">{{ $mantenimiento->detalle }}</p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        Proveedor: {{ $mantenimiento->proveedor ?: $mantenimiento->mantenimientoExterno?->proveedor ?: 'No informado' }}
                                    </p>
                                    <div class="mt-3 space-y-2">
                                        @forelse($mantenimiento->documents as $documento_mto)
                                            <div class="app-panel flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-xs">
                                                <div class="flex min-w-0 items-center gap-2 text-slate-700">
                                                    <x-icon name="file-text" class="h-4 w-4 shrink-0 text-rose-500" />
                                                    <span class="max-w-56 truncate font-medium text-slate-900" title="{{ $documento_mto->original_name }}">{{ $documento_mto->original_name }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <a href="{{ route('documents.download', $documento_mto) }}" target="_blank" rel="noopener noreferrer" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 transition hover:bg-slate-100">Ver</a>
                                                    <a href="{{ route('documents.download', $documento_mto) }}" class="inline-flex items-center gap-1 rounded-md border border-indigo-200 px-2 py-1 text-indigo-600 transition hover:bg-indigo-50">
                                                        <x-icon name="download" class="h-3.5 w-3.5" />
                                                        Descargar
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-500">Sin documentos adjuntos.</p>
                                        @endforelse
                                    </div>
                                    @if(auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                                        <form method="POST" action="{{ route('mantenimientos.documents.store', $mantenimiento) }}" enctype="multipart/form-data" x-data="{ selectedFileName: '' }" class="mt-3 app-subcard p-3">
                                            @csrf
                                            <input type="hidden" name="document_context" value="mantenimiento:{{ $mantenimiento->id }}">
                                            <div class="grid gap-3 lg:grid-cols-[minmax(10rem,12rem)_minmax(12rem,1fr)_minmax(10rem,1fr)_auto] lg:items-end">
                                                <div>
                                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo documento</label>
                                                    <select name="type" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-xs" required>
                                                        @foreach(\App\Models\Document::TYPES as $type)
                                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observacion</label>
                                                    <input type="text" name="note" value="{{ str_starts_with($documentContext, 'mantenimiento:'.$mantenimiento->id) ? old('note') : '' }}" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-xs" placeholder="Detalle opcional">
                                                </div>
                                                <div>
                                                    <input id="mantenimiento-file-{{ $mantenimiento->id }}" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" required @change="selectedFileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                    <label for="mantenimiento-file-{{ $mantenimiento->id }}" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-indigo-300 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                                        <x-icon name="paperclip" class="h-4 w-4" />
                                                        <span x-text="selectedFileName || '{{ $mantenimiento->documents->isNotEmpty() ? 'Adjuntar otro archivo' : 'Subir archivo' }}'"></span>
                                                    </label>
                                                    <p class="mt-1 text-[11px] text-slate-500">Formatos permitidos: PDF, JPG, PNG</p>
                                                </div>
                                                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                                    <x-icon name="upload" class="h-4 w-4" />
                                                    Guardar
                                                </button>
                                            </div>
                                            <p x-show="selectedFileName" x-cloak class="mt-2 text-xs text-slate-600">
                                                Archivo seleccionado: <span class="font-medium" x-text="selectedFileName"></span>
                                            </p>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-700">
                                    @if ($mantenimiento->tipo === Mantenimiento::TIPO_EXTERNO)
                                        <p>Ingreso: {{ ($mantenimiento->fecha_ingreso_st ?? $mantenimiento->fecha)?->format('d/m/Y') }}</p>
                                        <p class="mt-1">
                                            Egreso:
                                            {{ $mantenimiento->fecha_egreso_st?->format('d/m/Y') ?: 'Abierto' }}
                                        </p>
                                    @elseif ($mantenimiento->mantenimientoExterno)
                                        <p>Relacionado con externo iniciado el {{ ($mantenimiento->mantenimientoExterno->fecha_ingreso_st ?? $mantenimiento->mantenimientoExterno->fecha)?->format('d/m/Y') }}</p>
                                        <p class="mt-1">Egreso registrado: {{ $mantenimiento->fecha_egreso_st?->format('d/m/Y') ?: '-' }}</p>
                                    @else
                                        <p>Nota tecnica sin ciclo externo asociado.</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->estadoResultante?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-slate-700">
                                    @if ($mantenimiento->dias_en_servicio !== null)
                                        {{ $mantenimiento->dias_en_servicio }} dias
                                    @elseif ($mantenimiento->isExternoAbierto())
                                        {{ ($mantenimiento->fecha_ingreso_st ?? $mantenimiento->fecha)?->diffInDays(now()) }} dias abiertos
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($mantenimiento->canBeManuallyChanged())
                                        <div class="flex items-center gap-2">
                                            @can('update', $mantenimiento)
                                                <a href="{{ route('mantenimientos.edit', $mantenimiento) }}" class="inline-flex items-center rounded p-1 text-amber-600 hover:bg-amber-50" title="Editar">
                                                    <x-icon name="pencil" class="h-4 w-4" />
                                                    <span class="sr-only">Editar</span>
                                                </a>
                                            @endcan
                                            @can('delete', $mantenimiento)
                                                <form method="POST" action="{{ route('mantenimientos.destroy', $mantenimiento) }}" class="inline" onsubmit="return confirm('Eliminar nota tecnica?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="inline-flex items-center rounded p-1 text-rose-600 hover:bg-rose-50" title="Eliminar">
                                                        <x-icon name="trash-2" class="h-4 w-4" />
                                                        <span class="sr-only">Eliminar</span>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    @else
                                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bloqueado por trazabilidad</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-500">Sin mantenimientos registrados.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section x-show="activeTab === 'movimientos'" x-cloak class="space-y-5">
                @can('update', $equipo)
                    <div class="flex justify-end">
                        <a href="{{ route('movimientos.create', ['equipo_id' => $equipo->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <x-icon name="plus" class="h-4 w-4" />
                            Movimiento / Transferir
                        </a>
                    </div>
                @endcan

                <div class="app-table-panel overflow-x-auto rounded-lg">
                    <table class="app-table text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Fecha</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Tipo</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Usuario</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Origen</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Destino</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Observacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($equipo->movimientos as $movimiento)
                                @php
                                    $origen = $offices->get($movimiento->oficina_origen_id);
                                    $destino = $offices->get($movimiento->oficina_destino_id);
                                    $origenTexto = $origen
                                        ? $origen->service?->institution?->nombre.' / '.$origen->service?->nombre.' / '.$origen->nombre
                                        : '-';
                                    $destinoTexto = $destino
                                        ? $destino->service?->institution?->nombre.' / '.$destino->service?->nombre.' / '.$destino->nombre
                                        : '-';
                                @endphp
                                <tr id="movimiento-{{ $movimiento->id }}">
                                    <td class="px-4 py-4 text-slate-700">{{ $movimiento->fecha?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 font-medium text-slate-900">{{ ucfirst($movimiento->tipo_movimiento) }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $movimiento->user?->name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700">
                                        <div class="max-w-52 truncate" title="{{ $origenTexto }}">{{ $origenTexto }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        <div class="max-w-52 truncate" title="{{ $destinoTexto }}">{{ $destinoTexto }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        <div class="max-w-64 truncate" title="{{ $movimiento->observacion ?? '-' }}">{{ $movimiento->observacion ?? '-' }}</div>
                                        @if($movimiento->tipo_movimiento === 'prestamo')
                                            <div class="mt-1 text-xs text-slate-600" title="Receptor: {{ $movimiento->receptor_nombre }} | DNI: {{ $movimiento->receptor_dni }} | Cargo: {{ $movimiento->receptor_cargo }}">
                                                Receptor: {{ $movimiento->receptor_nombre }} | DNI: {{ $movimiento->receptor_dni }} | Cargo: {{ $movimiento->receptor_cargo }}
                                            </div>
                                        @endif
                                        <div class="mt-2 space-y-2">
                                            @forelse($movimiento->documents as $documento_mov)
                                                <div class="app-panel flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-xs">
                                                    <div class="flex min-w-0 items-center gap-2 text-slate-700">
                                                        <x-icon name="file-text" class="h-4 w-4 shrink-0 text-rose-500" />
                                                        <span class="max-w-56 truncate font-medium text-slate-900" title="{{ $documento_mov->original_name }}">{{ $documento_mov->original_name }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <a href="{{ route('documents.download', $documento_mov) }}" target="_blank" rel="noopener noreferrer" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 transition hover:bg-slate-100">Ver</a>
                                                        <a href="{{ route('documents.download', $documento_mov) }}" class="inline-flex items-center gap-1 rounded-md border border-indigo-200 px-2 py-1 text-indigo-600 transition hover:bg-indigo-50">
                                                            <x-icon name="download" class="h-3.5 w-3.5" />
                                                            Descargar
                                                        </a>
                                                        @if(auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                                                            <label for="movimiento-file-{{ $movimiento->id }}" class="cursor-pointer rounded-md border border-amber-200 px-2 py-1 text-amber-700 transition hover:bg-amber-50">Reemplazar</label>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-xs text-slate-500">Sin documentos adjuntos.</p>
                                            @endforelse
                                        </div>
                                        @if(auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                                            <form method="POST" action="{{ route('movimientos.documents.store', $movimiento) }}" enctype="multipart/form-data" x-data="{ selectedFileName: '' }" class="mt-3 app-subcard p-3">
                                                @csrf
                                                <input type="hidden" name="document_context" value="movimiento:{{ $movimiento->id }}">
                                                <div class="grid gap-3 lg:grid-cols-[minmax(10rem,12rem)_minmax(12rem,1fr)_minmax(10rem,1fr)_auto] lg:items-end">
                                                    <div>
                                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo documento</label>
                                                        <select name="type" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-xs" required>
                                                            @foreach(\App\Models\Document::TYPES as $type)
                                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observacion</label>
                                                        <input type="text" name="note" value="{{ str_starts_with($documentContext, 'movimiento:'.$movimiento->id) ? old('note') : '' }}" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-xs" placeholder="Detalle opcional">
                                                    </div>
                                                    <div>
                                                        <input id="movimiento-file-{{ $movimiento->id }}" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" required @change="selectedFileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                        <label for="movimiento-file-{{ $movimiento->id }}" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-indigo-300 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                                            <x-icon name="paperclip" class="h-4 w-4" />
                                                            <span x-text="selectedFileName || '{{ $movimiento->documents->isNotEmpty() ? 'Reemplazar archivo' : 'Subir archivo' }}'"></span>
                                                        </label>
                                                        <p class="mt-1 text-[11px] text-slate-500">Formatos permitidos: PDF, JPG, PNG</p>
                                                    </div>
                                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                                        <x-icon name="upload" class="h-4 w-4" />
                                                        Guardar
                                                    </button>
                                                </div>
                                                <p x-show="selectedFileName" x-cloak class="mt-2 text-xs text-slate-600">
                                                    Archivo seleccionado: <span class="font-medium" x-text="selectedFileName"></span>
                                                </p>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay movimientos registrados para este equipo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section x-show="activeTab === 'documentos'" x-cloak class="space-y-5">
                @if(auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                    <div>
                        <button type="button" @click="showDocumentoForm = !showDocumentoForm" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <x-icon name="plus" class="h-4 w-4" />
                            Adjuntar documento
                        </button>
                    </div>

                    <div x-show="showDocumentoForm" class="app-subcard p-4">
                        <form method="POST" action="{{ route('equipos.documents.store', $equipo) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-4">
                            @csrf
                            <input type="hidden" name="document_context" value="equipo:{{ $equipo->id }}">
                            <select name="type" class="rounded border px-3 py-2" required>
                                <option value="">Tipo...</option>
                                @foreach(\App\Models\Document::TYPES as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            <input name="note" value="{{ str_starts_with($documentContext, 'equipo:'.$equipo->id) ? old('note') : '' }}" placeholder="Nota" class="rounded border px-3 py-2">
                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="rounded border px-3 py-2">
                            <button class="inline-flex items-center justify-center gap-1 rounded bg-indigo-600 px-3 py-2 text-white">
                                <x-icon name="upload" class="h-4 w-4" />
                                Subir
                            </button>
                        </form>
                    </div>
                @endif

                <div class="space-y-3">
                    @forelse($equipo->documentosCentralizados as $documentoEquipo)
                        @php
                            $document = $documentoEquipo->document;
                            $origenUrl = match ($documentoEquipo->origen_tipo) {
                                EquipoDocumento::ORIGEN_MOVIMIENTO => route('equipos.show', $equipo).'#movimiento-'.$documentoEquipo->origen_id,
                                EquipoDocumento::ORIGEN_MANTENIMIENTO => route('equipos.show', $equipo).'#mantenimiento-'.$documentoEquipo->origen_id,
                                EquipoDocumento::ORIGEN_ACTA => $documentoEquipo->origen_id ? route('actas.show', $documentoEquipo->origen_id) : null,
                                default => null,
                            };
                        @endphp
                        <article class="app-panel flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-rose-50 p-2 text-rose-600">
                                    <x-icon name="file-text" class="h-6 w-6" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="max-w-sm truncate text-sm font-semibold text-slate-900" title="{{ $documentoEquipo->nombre_original }}">{{ $documentoEquipo->nombre_original }}</p>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-700">
                                            {{ $documentoEquipo->origen_label }}
                                        </span>
                                        <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-700">
                                            {{ ucfirst($documentoEquipo->tipo_documento) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        Fecha documento: {{ $documentoEquipo->fecha_documento?->format('d/m/Y') ?: '-' }}
                                        @if ($documentoEquipo->uploadedBy)
                                            | Cargado por {{ $documentoEquipo->uploadedBy->name }}
                                        @endif
                                    </p>
                                    @if ($documentoEquipo->observacion)
                                        <p class="text-sm text-slate-600">{{ $documentoEquipo->observacion }}</p>
                                    @endif
                                    @if ($origenUrl)
                                        <a href="{{ $origenUrl }}" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                            <x-icon name="external-link" class="h-3.5 w-3.5" />
                                            Ver registro origen
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50" href="{{ route('documents.download', $document) }}">
                                    <x-icon name="download" class="h-4 w-4" />
                                    Descargar
                                </a>
                                @if ($documentoEquipo->origen_tipo !== EquipoDocumento::ORIGEN_ACTA)
                                    @can('delete', $document)
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                            Eliminar
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">No hay documentos en el legajo central del equipo.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection


