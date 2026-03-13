@extends('layouts.app')

@section('title', 'Detalle equipo')
@section('header', 'Detalle equipo')

@section('content')
<div class="space-y-6 bg-slate-50/70 p-1" x-data="{ activeTab: 'informacion', tipo: @js(old('tipo', 'interno')), showMantenimientoForm: {{ old('fecha') || old('titulo') || old('detalle') ? 'true' : 'false' }}, showDocumentoForm: {{ old('note') || old('file') ? 'true' : 'false' }} }">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
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
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Nro Serie: {{ $equipo->numero_serie }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Bien patrimonial: {{ $equipo->bien_patrimonial }}
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

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
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
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">
                            <div class="flex items-center gap-3">
                                <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="sm" class="rounded-lg" />
                                <span>{{ $equipo->tipo }}</span>
                            </div>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->marca }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->modelo }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado actual</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->equipoStatus?->name ?? ucfirst($equipo->estado) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha ingreso</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->fecha_ingreso?->format('d/m/Y') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ubicacion</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}</dd>
                    </div>
                </dl>
            </section>

            <section x-show="activeTab === 'mantenimiento'" x-cloak class="space-y-5">
                @can('create', \App\Models\Mantenimiento::class)
                    <div>
                        <button type="button" @click="showMantenimientoForm = !showMantenimientoForm" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <x-icon name="plus" class="h-4 w-4" />
                            Registrar mantenimiento
                        </button>
                    </div>

                    <div x-show="showMantenimientoForm" class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <form method="POST" action="{{ route('equipos.mantenimientos.store', $equipo) }}" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            <div>
                                <label class="text-sm font-medium text-slate-700">Fecha</label>
                                <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700">Tipo</label>
                                <select name="tipo" x-model="tipo" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach (\App\Models\Mantenimiento::TIPOS as $tipo)
                                        <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ ucfirst($tipo) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-slate-700">Titulo</label>
                                <input type="text" name="titulo" value="{{ old('titulo') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-slate-700">Detalle</label>
                                <textarea name="detalle" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>{{ old('detalle') }}</textarea>
                            </div>
                            <div x-show="tipo === 'externo'" x-cloak>
                                <label class="text-sm font-medium text-slate-700">Proveedor</label>
                                <input type="text" name="proveedor" value="{{ old('proveedor') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div x-show="tipo === 'externo'" x-cloak>
                                <label class="text-sm font-medium text-slate-700">Fecha ingreso ST</label>
                                <input type="date" name="fecha_ingreso_st" value="{{ old('fecha_ingreso_st', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div x-show="tipo === 'alta'" x-cloak>
                                <label class="text-sm font-medium text-slate-700">Fecha egreso ST</label>
                                <input type="date" name="fecha_egreso_st" value="{{ old('fecha_egreso_st', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Registrar mantenimiento</button>
                            </div>
                        </form>
                    </div>
                @endcan

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Titulo</th>
                                <th class="px-4 py-3">Detalle</th>
                                <th class="px-4 py-3">Proveedor</th>
                                <th class="px-4 py-3">Estado resultante</th>
                                <th class="px-4 py-3">Duracion</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($mantenimientos as $mantenimiento)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->fecha?->format('d/m/Y') }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ ucfirst($mantenimiento->tipo) }}</td>
                                <td class="px-4 py-4 font-medium text-slate-900">{{ $mantenimiento->titulo }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->detalle }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->proveedor ?: '-' }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->estadoResultante?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->dias_en_servicio !== null ? $mantenimiento->dias_en_servicio.' dias' : '-' }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        @can('update', $mantenimiento)
                                            <a href="{{ route('mantenimientos.edit', $mantenimiento) }}" class="inline-flex items-center rounded p-1 text-amber-600 hover:bg-amber-50" title="Editar">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                                <span class="sr-only">Editar</span>
                                            </a>
                                        @endcan
                                        @can('delete', $mantenimiento)
                                            <form method="POST" action="{{ route('mantenimientos.destroy', $mantenimiento) }}" class="inline" onsubmit="return confirm('Eliminar mantenimiento?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="inline-flex items-center rounded p-1 text-rose-600 hover:bg-rose-50" title="Eliminar">
                                                    <x-icon name="trash-2" class="h-4 w-4" />
                                                    <span class="sr-only">Eliminar</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Sin mantenimientos registrados.</td>
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

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
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
                        <tbody class="divide-y divide-slate-100 bg-white">
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
                                <tr class="transition hover:bg-slate-50/80">
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
                                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
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
                                            <form method="POST" action="{{ route('movimientos.documents.store', $movimiento) }}" enctype="multipart/form-data" x-data="{ selectedFileName: '' }" class="mt-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                                @csrf
                                                <div class="flex flex-wrap items-end gap-3">
                                                    <div class="min-w-[11rem]">
                                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo documento</label>
                                                        <select name="type" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-xs" required>
                                                            @foreach(\App\Models\Document::TYPES as $type)
                                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="min-w-[14rem] flex-1">
                                                        <input id="movimiento-file-{{ $movimiento->id }}" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" required @change="selectedFileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                        <label for="movimiento-file-{{ $movimiento->id }}" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-indigo-300 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                                            <x-icon name="paperclip" class="h-4 w-4" />
                                                            <span x-text="selectedFileName || '{{ $movimiento->documents->isNotEmpty() ? 'Reemplazar archivo' : 'Subir archivo' }}'"></span>
                                                        </label>
                                                        <p class="mt-1 text-[11px] text-slate-500">Formatos permitidos: PDF, JPG, PNG</p>
                                                    </div>
                                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">
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

                    <div x-show="showDocumentoForm" class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <form method="POST" action="{{ route('equipos.documents.store', $equipo) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-4">
                            @csrf
                            <select name="type" class="rounded border px-3 py-2" required>
                                <option value="">Tipo...</option>
                                @foreach(\App\Models\Document::TYPES as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            <input name="note" placeholder="Nota" class="rounded border px-3 py-2">
                            <input type="file" name="file" required class="rounded border px-3 py-2">
                            <button class="inline-flex items-center justify-center gap-1 rounded bg-indigo-600 px-3 py-2 text-white">
                                <x-icon name="upload" class="h-4 w-4" />
                                Subir
                            </button>
                        </form>
                    </div>
                @endif

                <div class="space-y-3">
                    @forelse($equipo->documents as $document)
                        <article class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-rose-50 p-2 text-rose-600">
                                    <x-icon name="file-text" class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="max-w-sm truncate text-sm font-semibold text-slate-900" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $document->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50" href="{{ route('documents.download', $document) }}">
                                    <x-icon name="download" class="h-4 w-4" />
                                    Descargar
                                </a>
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
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">No hay documentos del equipo.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
