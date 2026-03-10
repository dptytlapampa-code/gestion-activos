@extends('layouts.app')

@section('title', 'Configuracion general')
@section('header', 'Configuracion general')

@section('content')
    @php
        $primaryColorValue = old('primary_color', $settings['primary_color'] ?? '#4F46E5');
        $sidebarColorValue = old('sidebar_color', $settings['sidebar_color'] ?? '#4338CA');

        if (! preg_match('/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $primaryColorValue)) {
            $primaryColorValue = '#4F46E5';
        }

        if (! preg_match('/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $sidebarColorValue)) {
            $sidebarColorValue = '#4338CA';
        }
    @endphp

    <section class="card">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">General</h3>
            <p class="mt-1 text-sm text-slate-600">
                Define la identidad visual institucional del sistema. Estos cambios afectan el layout, sidebar y pantalla de ingreso.
            </p>
        </div>

        <form class="mt-6 space-y-6" method="POST" action="{{ route('admin.configuracion.general.update') }}" enctype="multipart/form-data"
              x-data="{ primaryColor: '{{ $primaryColorValue }}', sidebarColor: '{{ $sidebarColorValue }}' }">
            @csrf
            @method('PUT')

            <div>
                <label for="site_name" class="text-sm font-medium text-slate-700">Nombre del sistema</label>
                <input
                    id="site_name"
                    name="site_name"
                    type="text"
                    value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                    class="form-control @error('site_name') form-control-error @enderror"
                    maxlength="120"
                    required
                >
                @error('site_name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="primary_color" class="text-sm font-medium text-slate-700">Color primario</label>
                    <div class="mt-2 flex items-center gap-3">
                        <input type="color" x-model="primaryColor" class="h-10 w-14 cursor-pointer rounded border border-slate-300 bg-white">
                        <input
                            id="primary_color"
                            name="primary_color"
                            type="text"
                            x-model="primaryColor"
                            class="form-control mt-0 @error('primary_color') form-control-error @enderror"
                            placeholder="#4F46E5"
                            required
                        >
                    </div>
                    @error('primary_color')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sidebar_color" class="text-sm font-medium text-slate-700">Color del sidebar</label>
                    <div class="mt-2 flex items-center gap-3">
                        <input type="color" x-model="sidebarColor" class="h-10 w-14 cursor-pointer rounded border border-slate-300 bg-white">
                        <input
                            id="sidebar_color"
                            name="sidebar_color"
                            type="text"
                            x-model="sidebarColor"
                            class="form-control mt-0 @error('sidebar_color') form-control-error @enderror"
                            placeholder="#4338CA"
                            required
                        >
                    </div>
                    @error('sidebar_color')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="logo" class="text-sm font-medium text-slate-700">Logo institucional</label>
                <input
                    id="logo"
                    name="logo"
                    type="file"
                    accept="image/*"
                    class="form-control @error('logo') form-control-error @enderror"
                >
                <p class="mt-2 text-xs text-slate-500">Formatos permitidos: JPG, PNG, WEBP o SVG. Tamano maximo: 2 MB.</p>
                @error('logo')
                    <p class="form-error">{{ $message }}</p>
                @enderror

                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-700">Logo actual</p>
                    @if (! empty($settings['logo_url']))
                        <img src="{{ $settings['logo_url'] }}" alt="Logo actual" class="mt-3 h-16 w-auto rounded bg-white p-2 shadow-sm">
                    @else
                        <p class="mt-2 text-sm text-slate-500">Todavia no hay un logo configurado.</p>
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Guardar configuracion</button>
            </div>
        </form>
    </section>
@endsection
