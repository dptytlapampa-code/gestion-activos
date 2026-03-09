@extends('layouts.app')

@section('title', 'Nueva institucion')
@section('header', 'Nueva institucion')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Crear institucion</h3>
            <p class="text-sm text-surface-500">Registre una institucion del sistema hospitalario.</p>
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

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="codigo_institucional" class="text-sm font-semibold text-surface-700">Codigo institucional *</label>
                    <input
                        type="text"
                        id="codigo_institucional"
                        name="codigo_institucional"
                        value="{{ old('codigo_institucional') }}"
                        maxlength="20"
                        class="form-control @error('codigo_institucional') form-control-error @enderror"
                        required
                    />
                    <p class="mt-2 text-xs text-surface-500">Debe ser unico y no se podra modificar luego.</p>
                    @error('codigo_institucional') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre *</label>
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
                    <label for="tipo" class="text-sm font-semibold text-surface-700">Tipo de institucion *</label>
                    <select
                        id="tipo"
                        name="tipo"
                        class="form-control @error('tipo') form-control-error @enderror"
                        required
                    >
                        <option value="">Seleccione un tipo</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>
                                {{ ucfirst(str_replace('_', ' ', $tipo)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipo') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="estado" class="text-sm font-semibold text-surface-700">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="form-control @error('estado') form-control-error @enderror"
                    >
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', 'activo') === $estado)>
                                {{ ucfirst($estado) }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="direccion" class="text-sm font-semibold text-surface-700">Direccion</label>
                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        value="{{ old('direccion') }}"
                        maxlength="255"
                        class="form-control @error('direccion') form-control-error @enderror"
                    />
                    @error('direccion') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="localidad" class="text-sm font-semibold text-surface-700">Localidad</label>
                    <input
                        type="text"
                        id="localidad"
                        name="localidad"
                        value="{{ old('localidad') }}"
                        maxlength="150"
                        class="form-control @error('localidad') form-control-error @enderror"
                    />
                    @error('localidad') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="provincia" class="text-sm font-semibold text-surface-700">Provincia</label>
                    <input
                        type="text"
                        id="provincia"
                        name="provincia"
                        value="{{ old('provincia') }}"
                        maxlength="150"
                        class="form-control @error('provincia') form-control-error @enderror"
                    />
                    @error('provincia') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telefono" class="text-sm font-semibold text-surface-700">Telefono</label>
                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        value="{{ old('telefono') }}"
                        maxlength="50"
                        class="form-control @error('telefono') form-control-error @enderror"
                    />
                    @error('telefono') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="text-sm font-semibold text-surface-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        maxlength="255"
                        class="form-control @error('email') form-control-error @enderror"
                    />
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="responsable" class="text-sm font-semibold text-surface-700">Responsable</label>
                    <input
                        type="text"
                        id="responsable"
                        name="responsable"
                        value="{{ old('responsable') }}"
                        maxlength="255"
                        class="form-control @error('responsable') form-control-error @enderror"
                    />
                    @error('responsable') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
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
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    Guardar institucion
                </button>
                <a href="{{ route('institutions.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
