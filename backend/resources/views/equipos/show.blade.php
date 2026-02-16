@extends('layouts.app')

@section('title', 'Detalle equipo')
@section('header', 'Detalle equipo')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-6">
    <dl class="grid gap-4 md:grid-cols-2">
        <div><dt class="text-xs uppercase text-slate-500">Tipo</dt><dd class="font-medium">{{ $equipo->tipo }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">Marca</dt><dd class="font-medium">{{ $equipo->marca }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">Modelo</dt><dd class="font-medium">{{ $equipo->modelo }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">N° Serie</dt><dd class="font-medium">{{ $equipo->nro_serie }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">Bien patrimonial</dt><dd class="font-medium">{{ $equipo->bien_patrimonial }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">Estado</dt><dd class="font-medium">{{ ucfirst($equipo->estado) }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">Fecha ingreso</dt><dd class="font-medium">{{ $equipo->fecha_ingreso?->format('d/m/Y') }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500">Ubicación</dt><dd class="font-medium">{{ $equipo->oficina?->service?->institution?->nombre }} / {{ $equipo->oficina?->service?->nombre }} / {{ $equipo->oficina?->nombre }}</dd></div>
    </dl>
</div>
@endsection
