@extends('layouts.app')

@section('title', 'Detalle de acta')
@section('header', 'Detalle de acta '.$acta->codigo)

@section('content')
    <div class="space-y-6">
        <div class="card grid gap-4 md:grid-cols-2">
            <div><span class="text-xs text-surface-500">Código</span><p class="font-medium">{{ $acta->codigo }}</p></div>
            <div><span class="text-xs text-surface-500">Tipo</span><p class="font-medium">{{ ucfirst($acta->tipo) }}</p></div>
            <div><span class="text-xs text-surface-500">Fecha</span><p class="font-medium">{{ $acta->fecha?->format('d/m/Y') }}</p></div>
            <div><span class="text-xs text-surface-500">Receptor</span><p class="font-medium">{{ $acta->receptor_nombre }}</p></div>
            <div><span class="text-xs text-surface-500">DNI</span><p class="font-medium">{{ $acta->receptor_dni ?: '-' }}</p></div>
            <div><span class="text-xs text-surface-500">Cargo</span><p class="font-medium">{{ $acta->receptor_cargo ?: '-' }}</p></div>
            <div><span class="text-xs text-surface-500">Dependencia</span><p class="font-medium">{{ $acta->receptor_dependencia ?: '-' }}</p></div>
            <div><span class="text-xs text-surface-500">Generado por</span><p class="font-medium">{{ $acta->creator?->name }}</p></div>
            <div class="md:col-span-2"><span class="text-xs text-surface-500">Observaciones</span><p class="font-medium">{{ $acta->observaciones ?: '-' }}</p></div>
        </div>

        <div class="card overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-200 text-sm">
                <thead>
                <tr class="text-left text-surface-600">
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Marca</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Serie</th>
                    <th class="px-4 py-3">Patrimonial</th>
                    <th class="px-4 py-3">Cantidad</th>
                    <th class="px-4 py-3">Accesorios</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                @foreach ($acta->equipos as $equipo)
                    <tr>
                        <td class="px-4 py-3">{{ $equipo->tipo }}</td>
                        <td class="px-4 py-3">{{ $equipo->marca }}</td>
                        <td class="px-4 py-3">{{ $equipo->modelo }}</td>
                        <td class="px-4 py-3">{{ $equipo->numero_serie }}</td>
                        <td class="px-4 py-3">{{ $equipo->bien_patrimonial }}</td>
                        <td class="px-4 py-3">{{ $equipo->pivot->cantidad }}</td>
                        <td class="px-4 py-3">{{ $equipo->pivot->accesorios ?: '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('actas.download', $acta) }}" class="rounded-xl bg-primary-600 px-5 py-2 font-medium text-white">Descargar PDF</a>
        </div>
    </div>
@endsection
