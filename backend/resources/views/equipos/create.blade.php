@extends('layouts.app')

@section('title', 'Nuevo equipo')
@section('header', 'Nuevo equipo')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Registrar equipo</h3>
            <p class="text-sm text-surface-500">Complete la ficha del activo y seleccione su ubicación jerárquica.</p>
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

        <form
            method="POST"
            action="{{ route('equipos.store') }}"
            class="space-y-6 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm"
            x-data="{
                institutions: @json($institutions),
                services: @json($services),
                offices: @json($offices),
                selectedInstitution: '{{ old('institution_id') }}',
                selectedService: '{{ old('service_id') }}',
                selectedOffice: '{{ old('office_id') }}',
                filteredServices() {
                    if (!this.selectedInstitution) {
                        return this.services;
                    }
                    return this.services.filter(service => String(service.institution_id) === String(this.selectedInstitution));
                },
                filteredOffices() {
                    if (!this.selectedService) {
                        return this.offices;
                    }
                    return this.offices.filter(office => String(office.service_id) === String(this.selectedService));
                },
                handleInstitutionChange() {
                    this.selectedService = '';
                    this.selectedOffice = '';
                },
                handleServiceChange() {
                    this.selectedOffice = '';
                },
            }"
        >
            @csrf

            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label for="institution_id" class="text-sm font-semibold text-surface-700">Institución</label>
                    <select
                        id="institution_id"
                        name="institution_id"
                        x-model="selectedInstitution"
                        @change="handleInstitutionChange()"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    >
                        <option value="">Seleccione una institución</option>
                        <template x-for="institution in institutions" :key="institution.id">
                            <option :value="institution.id" x-text="institution.nombre"></option>
                        </template>
                    </select>
                    @if ($institutions->isEmpty())
                        <p class="mt-2 text-xs text-amber-600">Debe crear una institución antes de registrar equipos.</p>
                    @endif
                </div>

                <div>
                    <label for="service_id" class="text-sm font-semibold text-surface-700">Servicio</label>
                    <select
                        id="service_id"
                        name="service_id"
                        x-model="selectedService"
                        @change="handleServiceChange()"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    >
                        <option value="">Seleccione un servicio</option>
                        <template x-for="service in filteredServices()" :key="service.id">
                            <option :value="service.id" x-text="service.nombre"></option>
                        </template>
                    </select>
                    @if ($services->isEmpty())
                        <p class="mt-2 text-xs text-amber-600">Debe crear servicios antes de registrar equipos.</p>
                    @endif
                </div>

                <div>
                    <label for="office_id" class="text-sm font-semibold text-surface-700">Oficina</label>
                    <select
                        id="office_id"
                        name="office_id"
                        x-model="selectedOffice"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    >
                        <option value="">Seleccione una oficina</option>
                        <template x-for="office in filteredOffices()" :key="office.id">
                            <option :value="office.id" x-text="office.nombre"></option>
                        </template>
                    </select>
                    @if ($offices->isEmpty())
                        <p class="mt-2 text-xs text-amber-600">Debe crear oficinas antes de registrar equipos.</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label for="tipo_equipo" class="text-sm font-semibold text-surface-700">Tipo de equipo</label>
                    <input
                        type="text"
                        id="tipo_equipo"
                        name="tipo_equipo"
                        value="{{ old('tipo_equipo') }}"
                        maxlength="100"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    />
                </div>

                <div>
                    <label for="marca" class="text-sm font-semibold text-surface-700">Marca</label>
                    <input
                        type="text"
                        id="marca"
                        name="marca"
                        value="{{ old('marca') }}"
                        maxlength="100"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    />
                </div>

                <div>
                    <label for="modelo" class="text-sm font-semibold text-surface-700">Modelo</label>
                    <input
                        type="text"
                        id="modelo"
                        name="modelo"
                        value="{{ old('modelo') }}"
                        maxlength="100"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    />
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="numero_serie" class="text-sm font-semibold text-surface-700">Número de serie</label>
                    <input
                        type="text"
                        id="numero_serie"
                        name="numero_serie"
                        value="{{ old('numero_serie') }}"
                        maxlength="255"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    />
                </div>

                <div>
                    <label for="bien_patrimonial" class="text-sm font-semibold text-surface-700">Bien patrimonial</label>
                    <input
                        type="text"
                        id="bien_patrimonial"
                        name="bien_patrimonial"
                        value="{{ old('bien_patrimonial') }}"
                        maxlength="255"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    />
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="estado" class="text-sm font-semibold text-surface-700">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    >
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $estadoDefault) === $estado)>
                                {{ ucfirst(str_replace('_', ' ', $estado)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fecha_ingreso" class="text-sm font-semibold text-surface-700">Fecha de ingreso</label>
                    <input
                        type="date"
                        id="fecha_ingreso"
                        name="fecha_ingreso"
                        value="{{ old('fecha_ingreso') }}"
                        class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        required
                    />
                </div>
            </div>

            <div>
                <label for="descripcion" class="text-sm font-semibold text-surface-700">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                    maxlength="2000"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                >{{ old('descripcion') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    Guardar equipo
                </button>
                <a href="{{ route('equipos.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
