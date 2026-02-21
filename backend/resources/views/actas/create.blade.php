@extends('layouts.app')

@section('title', 'Nueva acta')
@section('header', 'Generar acta PDF')

@section('content')
    <form method="POST" action="{{ route('actas.store') }}" class="space-y-6" x-data="actaForm()">
        @csrf

        <div class="card grid gap-4 md:grid-cols-2">
            @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPERADMIN))
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-surface-700">Institución</label>
                    <select name="institution_id" x-model="institutionId" class="mt-1 w-full rounded-xl border-surface-200" required>
                        <option value="">Seleccionar</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->nombre }}</option>
                        @endforeach
                    </select>
                    @error('institution_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-surface-700">Tipo de acta</label>
                <select name="tipo" class="mt-1 w-full rounded-xl border-surface-200" required>
                    <option value="">Seleccionar</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ ucfirst($tipo) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700">Fecha</label>
                <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-surface-200" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700">Receptor</label>
                <input type="text" name="receptor_nombre" value="{{ old('receptor_nombre') }}" class="mt-1 w-full rounded-xl border-surface-200" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700">DNI</label>
                <input type="text" name="receptor_dni" value="{{ old('receptor_dni') }}" class="mt-1 w-full rounded-xl border-surface-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700">Cargo</label>
                <input type="text" name="receptor_cargo" value="{{ old('receptor_cargo') }}" class="mt-1 w-full rounded-xl border-surface-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700">Dependencia</label>
                <input type="text" name="receptor_dependencia" value="{{ old('receptor_dependencia') }}" class="mt-1 w-full rounded-xl border-surface-200">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-surface-700">Observaciones</label>
                <textarea name="observaciones" rows="3" class="mt-1 w-full rounded-xl border-surface-200">{{ old('observaciones') }}</textarea>
            </div>
        </div>

        <div class="card space-y-4">
            <div class="flex gap-2">
                <input type="text" x-model="query" placeholder="Buscar por serie, bien patrimonial o modelo" class="w-full rounded-xl border-surface-200">
                <button type="button" @click="buscarEquipos" class="rounded-xl border border-surface-200 px-4">Buscar</button>
            </div>

            <div x-show="results.length" class="rounded-xl border border-surface-200">
                <template x-for="item in results" :key="item.id">
                    <div class="flex items-center justify-between border-b border-surface-100 px-4 py-2 text-sm last:border-b-0">
                        <span x-text="item.label"></span>
                        <button type="button" class="text-primary-600" @click="agregar(item)">Agregar</button>
                    </div>
                </template>
            </div>

            <div>
                <h4 class="font-medium">Equipos seleccionados</h4>
                <template x-if="!selected.length">
                    <p class="mt-2 text-sm text-surface-500">No hay equipos agregados.</p>
                </template>
                <div class="mt-2 space-y-3" x-show="selected.length">
                    <template x-for="(item, index) in selected" :key="item.id">
                        <div class="rounded-xl border border-surface-200 p-3">
                            <div class="flex justify-between text-sm">
                                <span x-text="item.label"></span>
                                <button type="button" class="text-red-600" @click="remove(index)">Quitar</button>
                            </div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs text-surface-600">Cantidad</label>
                                    <input type="number" min="1" x-model="item.cantidad" class="mt-1 w-full rounded-xl border-surface-200">
                                </div>
                                <div>
                                    <label class="block text-xs text-surface-600">Accesorios</label>
                                    <input type="text" x-model="item.accesorios" class="mt-1 w-full rounded-xl border-surface-200">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <template x-for="(item, index) in selected" :key="`hidden-${item.id}`">
                <div>
                    <input type="hidden" :name="`equipos[${index}][equipo_id]`" :value="item.id">
                    <input type="hidden" :name="`equipos[${index}][cantidad]`" :value="item.cantidad || 1">
                    <input type="hidden" :name="`equipos[${index}][accesorios]`" :value="item.accesorios || ''">
                </div>
            </template>

            @error('equipos') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-primary-600 px-5 py-2 font-medium text-white">Generar acta y PDF</button>
        </div>
    </form>

    <script>
        function actaForm() {
            return {
                query: '',
                results: [],
                selected: [],
                institutionId: '{{ old('institution_id', auth()->user()->institution_id) }}',
                async buscarEquipos() {
                    if (this.query.length < 2) return;
                    const params = new URLSearchParams({ q: this.query });
                    if (this.institutionId) params.append('institution_id', this.institutionId);
                    const response = await fetch(`/api/search/equipos?${params.toString()}`);
                    this.results = await response.json();
                },
                agregar(item) {
                    if (this.selected.find((selectedItem) => selectedItem.id === item.id)) return;
                    this.selected.push({ ...item, cantidad: 1, accesorios: '' });
                },
                remove(index) {
                    this.selected.splice(index, 1);
                },
            };
        }
    </script>
@endsection
