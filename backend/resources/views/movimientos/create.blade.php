@extends('layouts.app')

@section('title', 'Registrar movimiento')
@section('header', 'Registrar movimiento')

@section('content')
<div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900">Nuevo movimiento manual</h3>
        <p class="mt-1 text-sm text-slate-500">Equipo: {{ $equipo->tipo }} - {{ $equipo->marca }} {{ $equipo->modelo }} (Serie: {{ $equipo->numero_serie }})</p>
    </div>

    <form method="POST" action="{{ route('equipos.movimientos.store', $equipo) }}" class="space-y-5">
        @csrf

        <div>
            <label for="tipo_movimiento" class="mb-1 block text-sm font-medium text-slate-700">Tipo de movimiento</label>
            <select id="tipo_movimiento" name="tipo_movimiento" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Seleccione una opción</option>
                @foreach ($tipos_movimiento as $tipo_movimiento)
                    <option value="{{ $tipo_movimiento }}" @selected(old('tipo_movimiento') === $tipo_movimiento)>
                        {{ ucfirst($tipo_movimiento) }}
                    </option>
                @endforeach
            </select>
            @error('tipo_movimiento')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="observacion" class="mb-1 block text-sm font-medium text-slate-700">Observación</label>
            <textarea id="observacion" name="observacion" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('observacion') }}</textarea>
            @error('observacion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('equipos.show', $equipo) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">Guardar movimiento</button>
        </div>
    </form>
</div>
@endsection
