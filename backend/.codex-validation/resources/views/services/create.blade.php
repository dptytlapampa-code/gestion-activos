@extends('layouts.app')

@section('title', 'Nuevo servicio')
@section('header', 'Nuevo servicio')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Crear servicio</h3>
            <p class="text-sm text-surface-500">Registre una unidad operativa dentro de una institucion.</p>
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

        <form method="POST" action="{{ route('services.store') }}" class="space-y-6 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="institution_id" class="text-sm font-semibold text-surface-700">Institucion</label>
                <select
                    id="institution_id"
                    name="institution_id"
                    class="form-control @error('institution_id') form-control-error @enderror"
                    required
                >
                    <option value="">Seleccione una institucion</option>
                    @foreach ($institutions as $institution)
                        <option value="{{ $institution->id }}" @selected(old('institution_id') == $institution->id)>
                            {{ $institution->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('institution_id') <p class="form-error">{{ $message }}</p> @enderror
                @if ($institutions->isEmpty())
                    <p class="mt-2 text-xs text-amber-600">Debe crear una institucion antes de registrar servicios.</p>
                @endif
            </div>

            <div>
                <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre') }}"
                    maxlength="255"
                    class="form-control @error('nombre') form-control-error @enderror"
                    required
                />
                @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="descripcion" class="text-sm font-semibold text-surface-700">Descripcion</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                    maxlength="2000"
                    class="form-control @error('descripcion') form-control-error @enderror"
                >{{ old('descripcion') }}</textarea>
                @error('descripcion') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    Guardar servicio
                </button>
                <a href="{{ route('services.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
