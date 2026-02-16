@php
    $institutionSelected = old('institution_id', $equipo?->oficina?->service?->institution_id);
    $serviceSelected = old('service_id', $equipo?->oficina?->service_id);
    $officeSelected = old('oficina_id', $equipo?->oficina_id);
@endphp

<div class="mx-auto max-w-4xl rounded-xl border border-slate-200 bg-white p-6" x-data="{
    instituciones: @js($instituciones),
    servicios: @js($servicios),
    oficinas: @js($oficinas),
    institution_id: '{{ $institutionSelected }}',
    service_id: '{{ $serviceSelected }}',
    oficina_id: '{{ $officeSelected }}',
    get filteredServicios(){ return this.servicios.filter(s => String(s.institution_id) === String(this.institution_id)); },
    get filteredOficinas(){ return this.oficinas.filter(o => String(o.service_id) === String(this.service_id)); },
    onInstitutionChange(){ this.service_id=''; this.oficina_id=''; },
    onServiceChange(){ this.oficina_id=''; }
}">
    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="text-sm font-medium">Institución</label>
                <select name="institution_id" x-model="institution_id" @change="onInstitutionChange" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="">Seleccione</option>
                    <template x-for="item in instituciones" :key="item.id"><option :value="item.id" x-text="item.nombre"></option></template>
                </select></div>
            <div><label class="text-sm font-medium">Servicio</label>
                <select name="service_id" x-model="service_id" @change="onServiceChange" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="">Seleccione</option>
                    <template x-for="item in filteredServicios" :key="item.id"><option :value="item.id" x-text="item.nombre"></option></template>
                </select></div>
            <div><label class="text-sm font-medium">Oficina</label>
                <select name="oficina_id" x-model="oficina_id" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="">Seleccione</option>
                    <template x-for="item in filteredOficinas" :key="item.id"><option :value="item.id" x-text="item.nombre"></option></template>
                </select></div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <input name="tipo" value="{{ old('tipo', $equipo?->tipo) }}" placeholder="Tipo" class="rounded-lg border-slate-300" />
            <input name="marca" value="{{ old('marca', $equipo?->marca) }}" placeholder="Marca" class="rounded-lg border-slate-300" />
            <input name="modelo" value="{{ old('modelo', $equipo?->modelo) }}" placeholder="Modelo" class="rounded-lg border-slate-300" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <input name="nro_serie" value="{{ old('nro_serie', $equipo?->nro_serie) }}" placeholder="N° de serie" class="rounded-lg border-slate-300" />
            <input name="bien_patrimonial" value="{{ old('bien_patrimonial', $equipo?->bien_patrimonial) }}" placeholder="Bien patrimonial" class="rounded-lg border-slate-300" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <select name="estado" class="rounded-lg border-slate-300">
                @foreach($estados as $estado)
                    <option value="{{ $estado }}" @selected(old('estado', $equipo?->estado) === $estado)>{{ ucfirst($estado) }}</option>
                @endforeach
            </select>
            <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', $equipo?->fecha_ingreso?->format('Y-m-d')) }}" class="rounded-lg border-slate-300" />
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="flex gap-2">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
            <a href="{{ route('equipos.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancelar</a>
        </div>
    </form>
</div>
