@php
    $institutionSelected = old('institution_id', $equipo?->oficina?->service?->institution_id);
    $serviceSelected = old('service_id', $equipo?->oficina?->service_id);
    $officeSelected = old('oficina_id', $equipo?->oficina_id);
    $institutionLabelSelected = old('institution_id')
        ? optional($instituciones->firstWhere('id', (int) old('institution_id')))->nombre
        : $equipo?->oficina?->service?->institution?->nombre;

    $estadoSeleccionado = old('estado', $equipo?->estado);
    $fechaIngreso = old('fecha_ingreso', $equipo?->fecha_ingreso?->format('Y-m-d'));
@endphp

<div
    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
    x-data="{
        servicios: @js($servicios),
        oficinas: @js($oficinas),
        institution_id: @js((string) $institutionSelected),
        service_id: @js((string) $serviceSelected),
        oficina_id: @js((string) $officeSelected),
        isSubmitting: false,
        get filteredServicios() {
            return this.servicios.filter((servicio) => String(servicio.institution_id) === String(this.institution_id));
        },
        get filteredOficinas() {
            return this.oficinas.filter((oficina) => String(oficina.service_id) === String(this.service_id));
        },
        onInstitutionChange() {
            this.service_id = '';
            this.oficina_id = '';
        },
        onServiceChange() {
            this.oficina_id = '';
        },
    }"
>
    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
        <h3 class="text-lg font-semibold text-slate-900">Datos del equipo</h3>
        <p class="mt-1 text-sm text-slate-600">Complete la información solicitada para registrar el equipo en inventario hospitalario.</p>
    </div>

    <form
        method="POST"
        action="{{ $action }}"
        class="space-y-6 px-6 py-6 sm:px-8"
        @submit="isSubmitting = true"
        novalidate
    >
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4" role="alert" aria-live="assertive">
                <h4 class="text-sm font-semibold text-red-800">No fue posible guardar el equipo.</h4>
                <p class="mt-1 text-sm text-red-700">Revise los campos marcados y vuelva a intentarlo.</p>
            </div>
        @endif

        <section class="space-y-4" aria-labelledby="ubicacion-heading">
            <h4 id="ubicacion-heading" class="text-sm font-semibold uppercase tracking-wide text-slate-700">Ubicación</h4>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    @autocomplete-selected.window="if ($event.detail.name === 'institution_id') { institution_id = String($event.detail.value); onInstitutionChange(); }"
                    @autocomplete-cleared.window="if ($event.detail.name === 'institution_id') { institution_id = ''; onInstitutionChange(); }"
                >
                    <label for="institution_id" class="block text-sm font-medium text-slate-700">Institución <span class="text-red-600" aria-hidden="true">*</span></label>
                    <x-autocomplete
                        name="institution_id"
                        endpoint="/api/search/institutions"
                        placeholder="Buscar institución..."
                        :value="$institutionSelected"
                        :label="$institutionLabelSelected"
                    />
                    @error('institution_id')
                        <p id="institution_id_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="service_id" class="block text-sm font-medium text-slate-700">Servicio <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select
                        id="service_id"
                        name="service_id"
                        x-model="service_id"
                        @change="onServiceChange"
                        class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('service_id') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror"
                        aria-invalid="@error('service_id') true @else false @enderror"
                        aria-describedby="@error('service_id') service_id_error @enderror"
                        required
                    >
                        <option value="">Seleccione un servicio</option>
                        <template x-for="item in filteredServicios" :key="item.id">
                            <option :value="String(item.id)" x-text="item.nombre"></option>
                        </template>
                    </select>
                    @error('service_id')
                        <p id="service_id_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="oficina_id" class="block text-sm font-medium text-slate-700">Oficina <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select
                        id="oficina_id"
                        name="oficina_id"
                        x-model="oficina_id"
                        class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('oficina_id') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror"
                        aria-invalid="@error('oficina_id') true @else false @enderror"
                        aria-describedby="@error('oficina_id') oficina_id_error @enderror"
                        required
                    >
                        <option value="">Seleccione una oficina</option>
                        <template x-for="item in filteredOficinas" :key="item.id">
                            <option :value="String(item.id)" x-text="item.nombre"></option>
                        </template>
                    </select>
                    @error('oficina_id')
                        <p id="oficina_id_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="space-y-4" aria-labelledby="datos-tecnicos-heading">
            <h4 id="datos-tecnicos-heading" class="text-sm font-semibold uppercase tracking-wide text-slate-700">Datos técnicos</h4>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="tipo" class="block text-sm font-medium text-slate-700">Tipo de equipo <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="tipo" name="tipo" type="text" value="{{ old('tipo', $equipo?->tipo) }}" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('tipo') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('tipo') true @else false @enderror" aria-describedby="@error('tipo') tipo_error @enderror" required />
                    @error('tipo')
                        <p id="tipo_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="marca" class="block text-sm font-medium text-slate-700">Marca <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="marca" name="marca" type="text" value="{{ old('marca', $equipo?->marca) }}" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('marca') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('marca') true @else false @enderror" aria-describedby="@error('marca') marca_error @enderror" required />
                    @error('marca')
                        <p id="marca_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="modelo" class="block text-sm font-medium text-slate-700">Modelo <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="modelo" name="modelo" type="text" value="{{ old('modelo', $equipo?->modelo) }}" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('modelo') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('modelo') true @else false @enderror" aria-describedby="@error('modelo') modelo_error @enderror" required />
                    @error('modelo')
                        <p id="modelo_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="numero_serie" class="block text-sm font-medium text-slate-700">Número de serie <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="numero_serie" name="numero_serie" type="text" value="{{ old('numero_serie', $equipo?->numero_serie) }}" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('numero_serie') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('numero_serie') true @else false @enderror" aria-describedby="@error('numero_serie') numero_serie_error @enderror" required />
                    @error('numero_serie')
                        <p id="numero_serie_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bien_patrimonial" class="block text-sm font-medium text-slate-700">Bien patrimonial <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="bien_patrimonial" name="bien_patrimonial" type="text" value="{{ old('bien_patrimonial', $equipo?->bien_patrimonial) }}" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('bien_patrimonial') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('bien_patrimonial') true @else false @enderror" aria-describedby="@error('bien_patrimonial') bien_patrimonial_error @enderror" required />
                    @error('bien_patrimonial')
                        <p id="bien_patrimonial_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="estado" class="block text-sm font-medium text-slate-700">Estado <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="estado" name="estado" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('estado') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('estado') true @else false @enderror" aria-describedby="@error('estado') estado_error @enderror" required>
                        <option value="">Seleccione un estado</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected($estadoSeleccionado === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                    @error('estado')
                        <p id="estado_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_ingreso" class="block text-sm font-medium text-slate-700">Fecha de ingreso <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ $fechaIngreso }}" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('fecha_ingreso') border-red-400 focus:border-red-500 focus:ring-red-100 @else border-slate-300 @enderror" aria-invalid="@error('fecha_ingreso') true @else false @enderror" aria-describedby="@error('fecha_ingreso') fecha_ingreso_error @enderror" required />
                    @error('fecha_ingreso')
                        <p id="fecha_ingreso_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-slate-700">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="Detalle clínico o técnico relevante del equipo.">{{ old('descripcion', $equipo?->descripcion) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Opcional. Utilice este campo para observaciones útiles para el personal hospitalario.</p>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('equipos.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">Cancelar</a>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-70"
                :disabled="isSubmitting"
            >
                <svg x-show="isSubmitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-30"></circle>
                    <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" class="opacity-90"></path>
                </svg>
                <span x-text="isSubmitting ? 'Guardando…' : '{{ $submit_label ?? 'Guardar equipo' }}'"></span>
            </button>
        </div>
    </form>
</div>
