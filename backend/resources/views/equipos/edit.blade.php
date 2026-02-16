@extends('layouts.app')

@section('title', 'Editar equipo')
@section('header', 'Editar equipo')

@section('content')
    <div class="mx-auto w-full max-w-5xl">
        @include('equipos.partials.form', [
            'action' => route('equipos.update', $equipo),
            'method' => 'PUT',
            'equipo' => $equipo,
            'submit_label' => 'Actualizar equipo',
        ])
    </div>
@endsection
