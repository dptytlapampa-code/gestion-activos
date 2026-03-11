@php
    $selectedInstitutionId = old('institution_id', $office?->service?->institution_id);
    $selectedServiceId = old('service_id', $office?->service_id);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm"
    x-data="{
        selectedInstitutionId: @js((string) ($selectedInstitutionId ?? '')),
        selectedServiceId: @js((string) ($selectedServiceId ?? '')),
        services: @js($services->map(fn ($service) => [
            'id' => (string) $service->id,
            'nombre' => $service->nombre,
            'institution_id' => (string) $service->institution_id,
        ])->values()),
        get filteredServices() {
            if (!this.selectedInstitutionId) {
                return [];
            }

            return this.services.filter((service) => service.institution_id === this.selectedInstitutionId);
        },
        get servicePlaceholder() {
            if (!this.selectedInstitutionId) {
                return 'Primero seleccione una institucion';
            }

            if (this.filteredServices.length === 0) {
                return 'No hay servicios disponibles';
            }

            return 'Seleccione un servicio';
        },
        init() {
            if (!this.selectedInstitutionId) {
                this.selectedServiceId = '';
                return;
            }

            const serviceIsValid = this.filteredServices.some((service) => service.id === this.selectedServiceId);

            if (!serviceIsValid) {
                this.selectedServiceId = '';
            }
        },
        onInstitutionChange() {
            this.selectedServiceId = '';
        },
    }"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm text-surface-700">
        Complete en este orden: <span class="font-semibold">Institucion -> Servicio -> Oficina</span>.
    </div>

    <div>
        <label for="institution_id" class="text-sm font-semibold text-surface-700">Institucion</label>
        <select
            id="institution_id"
            name="institution_id"
            x-model="selectedInstitutionId"
            @change="onInstitutionChange"
            class="form-control @error('institution_id') form-control-error @enderror"
            required
        >
            <option value="">Seleccione una institucion</option>
            @foreach ($institutions as $institution)
                <option value="{{ $institution->id }}" @selected((string) $selectedInstitutionId === (string) $institution->id)>{{ $institution->nombre }}</option>
            @endforeach
        </select>
        @error('institution_id') <p class="form-error">{{ $message }}</p> @enderror
        @if ($institutions->isEmpty())
            <p class="mt-2 text-xs text-amber-600">Debe crear una institucion antes de registrar oficinas.</p>
        @endif
    </div>

    <div>
        <label for="service_id" class="text-sm font-semibold text-surface-700">Servicio</label>
        <select
            id="service_id"
            name="service_id"
            x-model="selectedServiceId"
            :disabled="!selectedInstitutionId || filteredServices.length === 0"
            class="form-control @error('service_id') form-control-error @enderror"
            required
        >
            <option value="" x-text="servicePlaceholder">Seleccione un servicio</option>
            <template x-for="service in filteredServices" :key="service.id">
                <option :value="service.id" x-text="service.nombre"></option>
            </template>
        </select>
        @error('service_id') <p class="form-error">{{ $message }}</p> @enderror
        <p x-show="!selectedInstitutionId" class="mt-2 text-xs text-surface-500">Seleccione una institucion para habilitar este campo.</p>
        <p x-show="selectedInstitutionId && filteredServices.length === 0" class="mt-2 text-xs text-amber-600">No hay servicios disponibles para la institucion seleccionada.</p>
        @if ($services->isEmpty())
            <p class="mt-2 text-xs text-amber-600">Debe crear un servicio antes de registrar oficinas.</p>
        @endif
    </div>

    <div>
        <label for="nombre" class="text-sm font-semibold text-surface-700">Nombre</label>
        <input
            type="text"
            id="nombre"
            name="nombre"
            value="{{ old('nombre', $office?->nombre) }}"
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
        >{{ old('descripcion', $office?->descripcion) }}</textarea>
        @error('descripcion') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('offices.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
            Cancelar
        </a>
    </div>
</form>
