@extends('layouts.app')

@section('title', 'Editar nota tecnica')
@section('header', 'Editar nota tecnica')

@section('content')
<div class="card space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-base font-semibold text-slate-900">Edicion limitada por trazabilidad</h3>
        <p class="mt-1 text-sm text-slate-600">
            Solo pueden editarse notas tecnicas informativas. Los ingresos y cierres de mantenimiento externo quedan bloqueados para preservar el historial del equipo.
        </p>
    </div>

    <form method="POST" action="{{ route('mantenimientos.update', $mantenimiento) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')

        <div>
            <label for="fecha" class="text-sm font-medium text-slate-700">Fecha</label>
            <input
                id="fecha"
                type="date"
                name="fecha"
                value="{{ old('fecha', $mantenimiento->fecha?->toDateString()) }}"
                class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                required
            >
        </div>

        <div>
            <label for="proveedor" class="text-sm font-medium text-slate-700">Proveedor</label>
            <input
                id="proveedor"
                type="text"
                name="proveedor"
                value="{{ old('proveedor', $mantenimiento->proveedor) }}"
                class="mt-1 w-full rounded-lg border-slate-300 text-sm"
            >
        </div>

        <div class="md:col-span-2">
            <label for="titulo" class="text-sm font-medium text-slate-700">Titulo</label>
            <input
                id="titulo"
                type="text"
                name="titulo"
                value="{{ old('titulo', $mantenimiento->titulo) }}"
                class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                required
            >
        </div>

        <div class="md:col-span-2">
            <label for="detalle" class="text-sm font-medium text-slate-700">Detalle</label>
            <textarea
                id="detalle"
                name="detalle"
                class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                rows="5"
                required
            >{{ old('detalle', $mantenimiento->detalle) }}</textarea>
        </div>

        <div class="md:col-span-2 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('equipos.show', $mantenimiento->equipo_id) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancelar
            </a>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Guardar nota tecnica
            </button>
        </div>
    </form>
</div>
@endsection
