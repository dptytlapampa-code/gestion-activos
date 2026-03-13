@extends('layouts.app')

@section('title', 'Editar mantenimiento')
@section('header', 'Editar mantenimiento')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-6">
    <form method="POST" action="{{ route('mantenimientos.update', $mantenimiento) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')
        <div><label>Fecha</label><input type="date" name="fecha" value="{{ old('fecha', $mantenimiento->fecha?->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
        <div>
            <label>Tipo</label>
            <select name="tipo" class="mt-1 w-full rounded-lg border-slate-300" required>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo }}" @selected(old('tipo', $mantenimiento->tipo) === $tipo)>{{ ucfirst($tipo) }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2"><label>Título</label><input type="text" name="titulo" value="{{ old('titulo', $mantenimiento->titulo) }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
        <div class="md:col-span-2"><label>Detalle</label><textarea name="detalle" class="mt-1 w-full rounded-lg border-slate-300" required>{{ old('detalle', $mantenimiento->detalle) }}</textarea></div>
        <div><label>Proveedor</label><input type="text" name="proveedor" value="{{ old('proveedor', $mantenimiento->proveedor) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div class="md:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Guardar</button></div>
    </form>
</div>
@endsection
