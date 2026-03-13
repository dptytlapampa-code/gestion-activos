@extends('layouts.app')

@section('title', 'Mantenimientos')
@section('header', 'Mantenimientos')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-4">
    <table class="min-w-full text-sm">
        <thead><tr><th>Fecha</th><th>Equipo</th><th>Tipo</th><th>Título</th></tr></thead>
        <tbody>
        @foreach($mantenimientos as $mantenimiento)
            <tr class="border-t"><td>{{ $mantenimiento->fecha?->format('d/m/Y') }}</td><td>{{ $mantenimiento->equipo?->tipo }} ({{ $mantenimiento->equipo?->numero_serie }})</td><td>{{ ucfirst($mantenimiento->tipo) }}</td><td>{{ $mantenimiento->titulo }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="mt-4">{{ $mantenimientos->links() }}</div>
</div>
@endsection
