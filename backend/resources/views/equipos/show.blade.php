@extends('layouts.app')

@section('title', 'Detalle equipo')
@section('header', 'Detalle equipo')

@section('content')
<div class="space-y-6 bg-slate-50/70 p-1" x-data="{ activeTab: 'informacion', tipo: @js(old('tipo', 'interno')), showMantenimientoForm: {{ old('fecha') || old('titulo') || old('detalle') ? 'true' : 'false' }}, showDocumentoForm: {{ old('note') || old('file') ? 'true' : 'false' }} }">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-3">
                <div>
                    <h2 class="text-2xl font-bold uppercase tracking-wide text-slate-900 md:text-3xl">
                        {{ $equipo->tipo }} {{ $equipo->marca }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        N° Serie: {{ $equipo->numero_serie }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Bien Patrimonial: {{ $equipo->bien_patrimonial }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 md:items-end">
                <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold uppercase"
                      @class([
                        'bg-green-100 text-green-800' => $equipo->estado === 'operativo',
                        'bg-yellow-100 text-yellow-800' => $equipo->estado === 'mantenimiento',
                        'bg-red-100 text-red-800' => $equipo->estado === 'baja',
                        'bg-slate-100 text-slate-700' => ! in_array($equipo->estado, ['operativo', 'mantenimiento', 'baja']),
                      ])>
                    Estado: {{ ucfirst($equipo->estado) }}
                </span>

                <a href="{{ route('equipos.edit', $equipo) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    Editar equipo
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex flex-wrap gap-2 md:gap-6">
                <button type="button" @click="activeTab = 'informacion'" :class="activeTab === 'informacion' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="border-b-2 px-1 pb-3 text-sm font-semibold transition">Información</button>
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
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->tipo }}</dd>
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
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ubicación</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-900">{{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}</dd>
                    </div>
                </dl>
            </section>

            <section x-show="activeTab === 'mantenimiento'" x-cloak class="space-y-5">
                @can('create', \App\Models\Mantenimiento::class)
                    <div>
                        <button type="button" @click="showMantenimientoForm = !showMantenimientoForm" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <span class="text-lg leading-none">+</span>
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
                                <label class="text-sm font-medium text-slate-700">Título</label>
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
                                <th class="px-4 py-3">Título</th>
                                <th class="px-4 py-3">Detalle</th>
                                <th class="px-4 py-3">Proveedor</th>
                                <th class="px-4 py-3">Estado resultante</th>
                                <th class="px-4 py-3">Duración</th>
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
                                <td class="px-4 py-4 text-slate-700">{{ $mantenimiento->dias_en_servicio !== null ? $mantenimiento->dias_en_servicio.' días' : '-' }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        @can('update', $mantenimiento)
                                            <a href="{{ route('mantenimientos.edit', $mantenimiento) }}" class="inline-flex items-center rounded p-1 text-amber-600 hover:bg-amber-50" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM16.5 7.125 18.875 9.5" /></svg>
                                                <span class="sr-only">Editar</span>
                                            </a>
                                        @endcan
                                        @can('delete', $mantenimiento)
                                            <form method="POST" action="{{ route('mantenimientos.destroy', $mantenimiento) }}" class="inline" onsubmit="return confirm('¿Eliminar mantenimiento?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="inline-flex items-center rounded p-1 text-rose-600 hover:bg-rose-50" title="Eliminar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
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
                        <a href="{{ route('movimientos.create', ['equipo_id' => $equipo->id]) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Observación</th>
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
                                        <div class="mt-2 space-y-1">
                                            @foreach($movimiento->documents as $documento_mov)
                                                <div class="text-xs">
                                                    <a class="inline-flex items-center gap-1 text-indigo-600" href="{{ route('documents.download', $documento_mov) }}" title="{{ $documento_mov->original_name }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9-3-3m3 3 3-3M3 15.75V17.25A2.25 2.25 0 0 0 5.25 19.5h13.5A2.25 2.25 0 0 0 21 17.25v-1.5" /></svg>
                                                        <span class="max-w-52 truncate">{{ $documento_mov->original_name }}</span>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if(auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
                                            <form method="POST" action="{{ route('movimientos.documents.store', $movimiento) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                                                @csrf
                                                <select name="type" class="rounded border-slate-300 px-2 py-1 text-xs" required>
                                                    @foreach(\App\Models\Document::TYPES as $type)
                                                        <option value="{{ $type }}">{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="file" name="file" class="text-xs" required>
                                                <button class="inline-flex items-center rounded p-1 text-indigo-600 hover:bg-indigo-50" title="Adjuntar documento">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l9.193-9.193a3 3 0 0 1 4.243 4.243l-9.194 9.193a1.5 1.5 0 0 1-2.121-2.121l7.425-7.425" /></svg>
                                                    <span class="sr-only">Adjuntar</span>
                                                </button>
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
                            <span class="text-lg leading-none">+</span>
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
                            <button class="rounded bg-indigo-600 px-3 py-2 text-white">Subir</button>
                        </form>
                    </div>
                @endif

                <div class="space-y-3">
                    @forelse($equipo->documents as $document)
                        <article class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-rose-50 p-2 text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15A2.25 2.25 0 0 0 6.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25V14.25Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 2.25V6a2.25 2.25 0 0 0 2.25 2.25H19.5" /></svg>
                                </div>
                                <div>
                                    <p class="max-w-sm truncate text-sm font-semibold text-slate-900" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $document->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a class="rounded-lg border border-indigo-200 px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50" href="{{ route('documents.download', $document) }}">Descargar</a>
                                @can('delete', $document)
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50">Eliminar</button>
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
