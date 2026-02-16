@extends('layouts.app')

@section('title', 'Editar equipo')
@section('header', 'Editar equipo')

@section('content')
@include('equipos.partials.form', [
    'action' => route('equipos.update', $equipo),
    'method' => 'PUT',
    'equipo' => $equipo,
])
@endsection
