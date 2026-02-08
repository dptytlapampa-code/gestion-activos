@extends('layouts.app')

@section('title', 'Equipos')
@section('header', 'Equipos')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-semibold text-surface-900">Gestión de equipos</h3>
            <p class="text-sm text-surface-500">Administre los activos con trazabilidad por institución, servicio y oficina.</p>
        </div>
        @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TECNICO))
            <a href="{{ route('equipos.create') }}" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                Nuevo equipo
            </a>
        @endif
    </div>

    <div
        class="mt-6 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm"
        x-data="{
            institutions: @json($institutions),
            services: @json($services),
            offices: @json($offices),
            filters: {
                institution_id: '{{ request('institution_id') }}',
                service_id: '{{ request('service_id') }}',
                office_id: '{{ request('office_id') }}',
            },
            filteredServices() {
                if (!this.filters.institution_id) {
                    return this.services;
                }
                return this.services.filter(service => String(service.institution_id) === String(this.filters.institution_id));
            },
            filteredOffices() {
                if (!this.filters.service_id) {
                    return this.offices;
                }
                return this.offices.filter(office => String(office.service_id) === String(this.filters.service_id));
            },
            handleInstitutionChange() {
                this.filters.service_id = '';
                this.filters.office_id = '';
            },
            handleServiceChange() {
                this.filters.office_id = '';
            },
        }"
    >
        <form method="GET" action="{{ route('equipos.index') }}" class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
            <div>
                <label for="institution_id" class="text-xs font-semibold uppercase text-surface-500">Institución</label>
                <select
                    id="institution_id"
                    name="institution_id"
                    x-model="filters.institution_id"
                    @change="handleInstitutionChange()"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-3 py-2 text-sm text-surface-900"
                >
                    <option value="">Todas</option>
                    <template x-for="institution in institutions" :key="institution.id">
                        <option :value="institution.id" x-text="institution.nombre"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="service_id" class="text-xs font-semibold uppercase text-surface-500">Servicio</label>
                <select
                    id="service_id"
                    name="service_id"
                    x-model="filters.service_id"
                    @change="handleServiceChange()"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-3 py-2 text-sm text-surface-900"
                >
                    <option value="">Todos</option>
                    <template x-for="service in filteredServices()" :key="service.id">
                        <option :value="service.id" x-text="service.nombre"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="office_id" class="text-xs font-semibold uppercase text-surface-500">Oficina</label>
                <select
                    id="office_id"
                    name="office_id"
                    x-model="filters.office_id"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-3 py-2 text-sm text-surface-900"
                >
                    <option value="">Todas</option>
                    <template x-for="office in filteredOffices()" :key="office.id">
                        <option :value="office.id" x-text="office.nombre"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="tipo_equipo" class="text-xs font-semibold uppercase text-surface-500">Tipo</label>
                <select
                    id="tipo_equipo"
                    name="tipo_equipo"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-3 py-2 text-sm text-surface-900"
                >
                    <option value="">Todos</option>
                    @foreach ($tiposEquipos as $tipo)
                        <option value="{{ $tipo }}" @selected(request('tipo_equipo') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estado" class="text-xs font-semibold uppercase text-surface-500">Estado</label>
                <select
                    id="estado"
                    name="estado"
                    class="mt-2 w-full rounded-xl border border-surface-200 px-3 py-2 text-sm text-surface-900"
                >
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}" @selected(request('estado') === $estado)>
                            {{ ucfirst(str_replace('_', ' ', $estado)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 md:col-span-3 lg:col-span-5">
                <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    Aplicar filtros
                </button>
                <a href="{{ route('equipos.index') }}" class="rounded-xl border border-surface-200 px-4 py-2 text-sm text-surface-600 transition hover:border-surface-300 hover:text-surface-900">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Equipo</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Identificadores</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Estado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-500">Ubicación</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-surface-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($equipos as $equipo)
                    <tr>
                        <td class="px-6 py-4 text-sm text-surface-700">
                            <div class="font-semibold text-surface-900">{{ $equipo->tipo_equipo }}</div>
                            <div class="text-xs text-surface-500">{{ $equipo->marca }} {{ $equipo->modelo }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-surface-700">
                            <div class="text-xs text-surface-500">Serie</div>
                            <div class="font-medium text-surface-900">{{ $equipo->numero_serie }}</div>
                            <div class="mt-2 text-xs text-surface-500">Bien patrimonial</div>
                            <div class="font-medium text-surface-900">{{ $equipo->bien_patrimonial }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-surface-700">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                @if ($equipo->estado === \App\Models\Equipo::ESTADO_OPERATIVO) bg-emerald-50 text-emerald-700 @endif
                                @if ($equipo->estado === \App\Models\Equipo::ESTADO_EN_REPARACION) bg-amber-50 text-amber-700 @endif
                                @if ($equipo->estado === \App\Models\Equipo::ESTADO_BAJA) bg-rose-50 text-rose-700 @endif
                            ">
                                {{ ucfirst(str_replace('_', ' ', $equipo->estado)) }}
                            </span>
                            <div class="mt-2 text-xs text-surface-500">Ingreso</div>
                            <div class="text-sm text-surface-700">{{ $equipo->fecha_ingreso?->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-surface-700">
                            <div class="font-medium text-surface-900">{{ $equipo->office?->nombre }}</div>
                            <div class="text-xs text-surface-500">{{ $equipo->service?->nombre }}</div>
                            <div class="text-xs text-surface-500">{{ $equipo->institution?->nombre }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-surface-500">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('equipos.edit', $equipo) }}" class="rounded-lg border border-surface-200 px-3 py-1 text-xs font-semibold text-surface-700 transition hover:border-surface-300 hover:text-surface-900">
                                    Editar
                                </a>
                                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN, \App\Models\User::ROLE_ADMIN))
                                    <form method="POST" action="{{ route('equipos.destroy', $equipo) }}" onsubmit="return confirm('¿Desea eliminar este equipo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-700">
                                            Eliminar
                                        </button>
                                    </form>
                                @else
                                    <span class="rounded-lg border border-surface-200 px-3 py-1 text-xs text-surface-400">Sin eliminación</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-surface-500">
                            No hay equipos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $equipos->links() }}
    </div>
@endsection
