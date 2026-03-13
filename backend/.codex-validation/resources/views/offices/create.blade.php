@extends('layouts.app')

@section('title', 'Nueva oficina')
@section('header', 'Nueva oficina')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-surface-900">Crear oficina</h3>
            <p class="text-sm text-surface-500">Registre una oficina con un flujo claro de institucion y servicio.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Se encontraron errores en el formulario:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('offices.partials.form', [
            'action' => route('offices.store'),
            'method' => 'POST',
            'submitLabel' => 'Guardar oficina',
            'office' => null,
            'institutions' => $institutions,
            'services' => $services,
        ])
    </div>
@endsection
