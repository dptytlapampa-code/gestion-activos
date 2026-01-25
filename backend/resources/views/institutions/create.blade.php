@extends('layouts.app')

@section('title', 'Nueva institución')
@section('header', 'Nueva institución')

@section('content')
    @php
        $initialServices = old('services', [
            ['name' => '', 'offices' => [['name' => '', 'floor' => '']]],
        ]);
    @endphp

    <form method="POST" action="{{ route('institutions.store') }}" class="space-y-8" x-data="institutionWizard({{ \Illuminate\Support\Js::from($initialServices) }})">
        @csrf

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
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-surface-800 dark:text-surface-100">Alta de institución</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Completá los tres pasos para registrar la estructura.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-surface-500">
                    <span :class="step >= 1 ? 'text-primary-600' : ''">Institución</span>
                    <span>•</span>
                    <span :class="step >= 2 ? 'text-primary-600' : ''">Servicios</span>
                    <span>•</span>
                    <span :class="step >= 3 ? 'text-primary-600' : ''">Oficinas</span>
                </div>
            </div>

            <div class="mt-6 space-y-6">
                <div x-show="step === 1" class="space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-surface-700 dark:text-surface-200">Nombre de la institución *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-800 dark:text-surface-100" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-surface-700 dark:text-surface-200">Código interno</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-800 dark:text-surface-100">
                    </div>
                </div>

                <div x-show="step === 2" class="space-y-4">
                    <template x-for="(service, serviceIndex) in services" :key="`service-${serviceIndex}`">
                        <div class="rounded-xl border border-surface-200/70 bg-surface-50/70 p-4 dark:border-surface-700/60 dark:bg-surface-800/60">
                            <div class="flex items-center justify-between gap-4">
                                <label class="text-sm font-semibold text-surface-700 dark:text-surface-200">Servicio *</label>
                                <button type="button" class="text-xs font-semibold text-rose-600 hover:text-rose-700" x-show="services.length > 1" @click="removeService(serviceIndex)">
                                    Quitar
                                </button>
                            </div>
                            <input type="text" :name="`services[${serviceIndex}][name]`" x-model="service.name" class="mt-2 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100" required>
                        </div>
                    </template>

                    <button type="button" class="rounded-xl border border-dashed border-primary-300 px-4 py-2 text-xs font-semibold text-primary-600 hover:border-primary-400" @click="addService">
                        + Agregar servicio
                    </button>
                </div>

                <div x-show="step === 3" class="space-y-6">
                    <template x-for="(service, serviceIndex) in services" :key="`offices-${serviceIndex}`">
                        <div class="rounded-xl border border-surface-200/70 bg-surface-50/70 p-4 dark:border-surface-700/60 dark:bg-surface-800/60">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-surface-700 dark:text-surface-200" x-text="service.name || 'Servicio sin nombre'"></p>
                                <button type="button" class="text-xs font-semibold text-primary-600 hover:text-primary-700" @click="addOffice(serviceIndex)">
                                    + Agregar oficina
                                </button>
                            </div>

                            <div class="mt-4 space-y-3">
                                <template x-for="(office, officeIndex) in service.offices" :key="`office-${serviceIndex}-${officeIndex}`">
                                    <div class="grid gap-3 md:grid-cols-[2fr_1fr_auto]">
                                        <div>
                                            <label class="text-xs font-semibold text-surface-500">Oficina *</label>
                                            <input type="text" :name="`services[${serviceIndex}][offices][${officeIndex}][name]`" x-model="office.name" class="mt-1 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-surface-500">Piso</label>
                                            <input type="text" :name="`services[${serviceIndex}][offices][${officeIndex}][floor]`" x-model="office.floor" class="mt-1 w-full rounded-xl border border-surface-200 px-4 py-2 text-sm focus:border-primary-500 focus:outline-none dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" class="text-xs font-semibold text-rose-600 hover:text-rose-700" x-show="service.offices.length > 1" @click="removeOffice(serviceIndex, officeIndex)">
                                                Quitar
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <button type="button" class="text-sm font-semibold text-surface-500 hover:text-surface-700" x-show="step > 1" @click="previousStep">
                    ← Volver
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-xl border border-surface-200 px-4 py-2 text-sm font-semibold text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:text-surface-300" x-show="step < 3" @click="nextStep">
                        Continuar
                    </button>
                    <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700" x-show="step === 3">
                        Guardar institución
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('institutionWizard', (initialServices) => ({
                step: 1,
                services: initialServices,
                nextStep() {
                    if (this.step < 3) {
                        this.step += 1;
                    }
                },
                previousStep() {
                    if (this.step > 1) {
                        this.step -= 1;
                    }
                },
                addService() {
                    this.services.push({ name: '', offices: [{ name: '', floor: '' }] });
                },
                removeService(index) {
                    if (this.services.length > 1) {
                        this.services.splice(index, 1);
                    }
                },
                addOffice(serviceIndex) {
                    this.services[serviceIndex].offices.push({ name: '', floor: '' });
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
