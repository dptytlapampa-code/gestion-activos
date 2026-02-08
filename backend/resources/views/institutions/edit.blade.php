@extends('layouts.app')

@section('title', 'Editar institución')
@section('header', 'Editar institución')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Editar institución</h3>
            <p class="text-sm text-surface-500">Actualice la información del hospital u organismo.</p>
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

        <form method="POST" action="{{ route('institutions.update', $institution) }}" class="space-y-6 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label for="codigo" class="text-sm font-semibold text-surface-700">Código institucional</label>
                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    value="{{ old('codigo', $institution->codigo) }}"
                    maxlength="20"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    @if ($institution->codigo)
                        readonly
                    @else
                        required
                    @endif
                />
                @if ($institution->codigo)
                    <p class="mt-2 text-xs text-surface-500">El código es inmutable una vez definido.</p>
                @else
                    <p class="mt-2 text-xs text-surface-500">Debe completar un código único para cerrar la institución.</p>
                @endif
            </div>

            <div>
                <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre', $institution->nombre) }}"
                    maxlength="255"
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
                    maxlength="2000"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                >{{ old('descripcion', $institution->descripcion) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    Guardar cambios
                </button>
                <a href="{{ route('institutions.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
