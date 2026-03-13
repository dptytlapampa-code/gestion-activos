@extends('layouts.app')

@section('title', 'Registrar equipo')
@section('header', 'Registrar equipo')

@section('content')
    <div class="mx-auto w-full max-w-5xl">
        @include('equipos.partials.form', [
            'action' => route('equipos.store'),
            'method' => 'POST',
            'equipo' => null,
            'submit_label' => 'Guardar equipo',
        ])
    </div>
@endsection
