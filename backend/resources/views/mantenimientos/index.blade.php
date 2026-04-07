@extends('layouts.app')

@section('title', 'Mantenimientos')
@section('header', 'Mantenimientos')

@section('content')
<div class="app-table-panel">
    <table class="app-table text-sm">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Equipo</th>
                <th>Tipo</th>
                <th>Titulo</th>
                <th>Referencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mantenimientos as $mantenimiento)
                <tr>
                    <td>{{ $mantenimiento->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $mantenimiento->equipo?->tipo }} ({{ $mantenimiento->equipo?->numero_serie }})</td>
                    <td>{{ match ($mantenimiento->tipo) {
                        \App\Models\Mantenimiento::TIPO_MESA_TECNICA => 'Mesa tecnica',
                        default => ucfirst($mantenimiento->tipo),
                    } }}</td>
                    <td>{{ $mantenimiento->titulo }}</td>
                    <td>{{ $mantenimiento->recepcionTecnica?->codigo ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="border-t border-slate-200 px-5 py-4">
        {{ $mantenimientos->links() }}
    </div>
</div>
@endsection
