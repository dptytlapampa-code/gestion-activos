@extends('layouts.app')

@section('title', 'Editar institucion')
@section('header', 'Editar institucion')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Editar institucion</h3>
            <p class="text-sm text-surface-500">Actualice la informacion institucional.</p>
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

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="codigo" class="text-sm font-semibold text-surface-700">Codigo institucional *</label>
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
                        <p class="mt-2 text-xs text-surface-500">El codigo es inmutable una vez definido.</p>
                    @endif
                </div>

                <div>
                    <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre *</label>
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
                    <label for="tipo" class="text-sm font-semibold text-surface-700">Tipo de institucion *</label>
                    <select
                        id="tipo"
                        name="tipo"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    >
                        <option value="">Seleccione un tipo</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected(old('tipo', $institution->tipo) === $tipo)>
                                {{ ucfirst(str_replace('_', ' ', $tipo)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="estado" class="text-sm font-semibold text-surface-700">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    >
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $institution->estado ?? 'activo') === $estado)>
                                {{ ucfirst($estado) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="direccion" class="text-sm font-semibold text-surface-700">Direccion</label>
                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        value="{{ old('direccion', $institution->direccion) }}"
                        maxlength="255"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    />
                </div>

                <div>
                    <label for="localidad" class="text-sm font-semibold text-surface-700">Localidad</label>
                    <input
                        type="text"
                        id="localidad"
                        name="localidad"
                        value="{{ old('localidad', $institution->localidad) }}"
                        maxlength="150"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    />
                </div>

                <div>
                    <label for="provincia" class="text-sm font-semibold text-surface-700">Provincia</label>
                    <input
                        type="text"
                        id="provincia"
                        name="provincia"
                        value="{{ old('provincia', $institution->provincia) }}"
                        maxlength="150"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    />
                </div>

                <div>
                    <label for="telefono" class="text-sm font-semibold text-surface-700">Telefono</label>
                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        value="{{ old('telefono', $institution->telefono) }}"
                        maxlength="50"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    />
                </div>

                <div>
                    <label for="email" class="text-sm font-semibold text-surface-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $institution->email) }}"
                        maxlength="255"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    />
                </div>

                <div class="md:col-span-2">
                    <label for="responsable" class="text-sm font-semibold text-surface-700">Responsable</label>
                    <input
                        type="text"
                        id="responsable"
                        name="responsable"
                        value="{{ old('responsable', $institution->responsable) }}"
                        maxlength="255"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    />
                </div>

                <div class="md:col-span-2">
                    <label for="descripcion" class="text-sm font-semibold text-surface-700">Descripcion</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        maxlength="2000"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                    >{{ old('descripcion', $institution->descripcion) }}</textarea>
                </div>
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
