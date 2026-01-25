@extends('layouts.app')

@section('title', 'Editar institución')
@section('header', 'Editar institución')

@section('content')
    @php
        $initialServices = old('services', $institution->services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'offices' => $service->offices->map(function ($office) {
                    return [
                        'id' => $office->id,
                        'name' => $office->name,
                        'floor' => $office->floor,
                    ];
                })->values()->all(),
            ];
        })->values()->all());
    @endphp

    <form method="POST" action="{{ route('institutions.update', $institution->id) }}" class="space-y-8" x-data="institutionEditor({{ \Illuminate\Support\Js::from($initialServices) }})">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700/60 dark:bg-rose-500/10 dark:text-rose-200">
                <p class="font-semibold">Revisá los campos marcados:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-surface-200/70 bg-white p-6 shadow-sm dark:border-surface-700/70 dark:bg-surface-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-surface-800 dark:text-surface-100">Editar institución</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Actualizá la estructura jerárquica sin perder trazabilidad.</p>
                </div>
                <a href="{{ route('institutions.show', $institution->id) }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm font-semibold text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:text-surface-300">
                    Ver detalle
                </a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-surface-700 dark:text-surface-200">Nombre de la institución *</label>
                    <input type="text" name="name" value="{{ old('name', $institution->name) }}" class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-800 dark:text-surface-100" required>
                </div>
                <div>
                    <label class="text-sm font-semibold text-surface-700 dark:text-surface-200">Código interno</label>
                    <input type="text" name="code" value="{{ old('code', $institution->code) }}" class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-800 dark:text-surface-100">
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-surface-200/70 bg-white p-6 shadow-sm dark:border-surface-700/70 dark:bg-surface-900">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-surface-800 dark:text-surface-100">Servicios y oficinas</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Podés agregar nuevos servicios u oficinas y editar nombres existentes.</p>
                </div>
                <button type="button" class="rounded-xl border border-dashed border-primary-300 px-4 py-2 text-xs font-semibold text-primary-600 hover:border-primary-400" @click="addService">
                    + Agregar servicio
                </button>
            </div>

            <div class="mt-6 space-y-6">
                <template x-for="(service, serviceIndex) in services" :key="`service-${serviceIndex}`">
                    <div class="rounded-xl border border-surface-200/70 bg-surface-50/70 p-4 dark:border-surface-700/60 dark:bg-surface-800/60">
                        <input type="hidden" :name="`services[${serviceIndex}][id]`" x-model="service.id">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex-1">
                                <label class="text-xs font-semibold text-surface-500">Servicio *</label>
                                <input type="text" :name="`services[${serviceIndex}][name]`" x-model="service.name" class="mt-1 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100" required>
                            </div>
                            <button type="button" class="text-xs font-semibold text-rose-600 hover:text-rose-700" x-show="!service.id && services.length > 1" @click="removeService(serviceIndex)">
                                Quitar
                            </button>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <p class="text-xs font-semibold text-surface-500">Oficinas</p>
                            <button type="button" class="text-xs font-semibold text-primary-600 hover:text-primary-700" @click="addOffice(serviceIndex)">
                                + Agregar oficina
                            </button>
                        </div>

                        <div class="mt-3 space-y-3">
                            <template x-for="(office, officeIndex) in service.offices" :key="`office-${serviceIndex}-${officeIndex}`">
                                <div class="grid gap-3 md:grid-cols-[2fr_1fr_auto]">
                                    <input type="hidden" :name="`services[${serviceIndex}][offices][${officeIndex}][id]`" x-model="office.id">
                                    <div>
                                        <label class="text-xs font-semibold text-surface-500">Oficina *</label>
                                        <input type="text" :name="`services[${serviceIndex}][offices][${officeIndex}][name]`" x-model="office.name" class="mt-1 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100" required>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-surface-500">Piso</label>
                                        <input type="text" :name="`services[${serviceIndex}][offices][${officeIndex}][floor]`" x-model="office.floor" class="mt-1 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" class="text-xs font-semibold text-rose-600 hover:text-rose-700" x-show="!office.id && service.offices.length > 1" @click="removeOffice(serviceIndex, officeIndex)">
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('institutions.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm font-semibold text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:text-surface-300">
                    Volver
                </a>
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('institutionEditor', (initialServices) => ({
                services: initialServices,
                addService() {
                    this.services.push({ id: null, name: '', offices: [{ id: null, name: '', floor: '' }] });
                },
                removeService(index) {
                    if (this.services.length > 1) {
                        this.services.splice(index, 1);
                    }
                },
                addOffice(serviceIndex) {
                    this.services[serviceIndex].offices.push({ id: null, name: '', floor: '' });
                },
                removeOffice(serviceIndex, officeIndex) {
                    if (this.services[serviceIndex].offices.length > 1) {
                        this.services[serviceIndex].offices.splice(officeIndex, 1);
                    }
                },
            }));
        });
    </script>
@endsection
