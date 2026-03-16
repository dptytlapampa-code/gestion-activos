@extends('layouts.app')

@section('title', 'Editar tipo de equipo')
@section('header', 'Editar tipo de equipo')

@section('content')
    @php
        $hasCurrentImage = $tipo_equipo->image_url !== null;
    @endphp

    <div class="max-w-3xl" x-data="{ previewUrl: null, removeImage: @js((bool) old('remove_imagen_png', false)) }">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Editar tipo de equipo</h3>
            <p class="text-sm text-surface-500">Actualice los datos de la categoria seleccionada.</p>
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

        <form method="POST" action="{{ route('tipos-equipos.update', $tipo_equipo) }}" enctype="multipart/form-data" class="card space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $tipo_equipo->nombre) }}" maxlength="100" class="form-control @error('nombre') form-control-error @enderror" required>
                @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="descripcion" class="text-sm font-semibold text-surface-700">Descripcion</label>
                <textarea id="descripcion" name="descripcion" rows="4" class="form-control @error('descripcion') form-control-error @enderror">{{ old('descripcion', $tipo_equipo->descripcion) }}</textarea>
                @error('descripcion') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-4 rounded-xl border border-surface-200 p-4">
                <div>
                    <h4 class="text-sm font-semibold text-surface-700">Imagen PNG asociada</h4>
                    <p class="text-xs text-surface-500">Puede reemplazarla o eliminarla. Formato permitido: PNG (maximo 2 MB).</p>
                </div>

                <div class="flex items-start gap-4">
                    <div class="h-24 w-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img
                            x-show="previewUrl"
                            x-cloak
                            :src="previewUrl"
                            alt="Vista previa"
                            class="h-full w-full bg-white object-contain p-1"
                        >

                        @if ($hasCurrentImage)
                            <img
                                x-show="!previewUrl && !removeImage"
                                src="{{ $tipo_equipo->image_url }}"
                                alt="Imagen actual"
                                class="h-full w-full bg-white object-contain p-1"
                            >
                        @endif

                        <div x-show="!previewUrl && (removeImage || {{ $hasCurrentImage ? 'false' : 'true' }})" class="flex h-full w-full items-center justify-center text-slate-400" @if (! $hasCurrentImage) style="display:flex;" @endif>
                            <x-icon name="image" class="h-6 w-6" />
                        </div>
                    </div>

                    <div class="flex-1 space-y-2">
                        <input
                            type="file"
                            id="imagen_png"
                            name="imagen_png"
                            accept="image/png"
                            class="form-control @error('imagen_png') form-control-error @enderror"
                            @change="
                                const file = $event.target.files[0];
                                previewUrl = file ? URL.createObjectURL(file) : null;
                                if (file) {
                                    removeImage = false;
                                }
                            "
                        >

                        @if ($hasCurrentImage)
                            <label class="inline-flex items-center gap-2 text-sm text-surface-700">
                                <input type="checkbox" name="remove_imagen_png" value="1" x-model="removeImage" class="rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                                Eliminar imagen actual
                            </label>
                        @endif

                        @error('imagen_png') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    Guardar cambios
                </button>
                <a href="{{ route('tipos-equipos.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    <x-icon name="x" class="h-4 w-4" />
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection

