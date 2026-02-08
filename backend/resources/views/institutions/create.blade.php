@extends('layouts.app')

@section('title', 'Nueva institución')
@section('header', 'Nueva institución')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Crear institución</h3>
            <p class="text-sm text-surface-500">Registre un hospital u organismo dentro del inventario.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Se encontraron errores en el formulario:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('institutions.store') }}" class="space-y-6 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre') }}"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    required
                />
            </div>

            <div>
                <label for="descripcion" class="text-sm font-semibold text-surface-700">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                >{{ old('descripcion') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    Guardar institución
                </button>
                <a href="{{ route('institutions.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
