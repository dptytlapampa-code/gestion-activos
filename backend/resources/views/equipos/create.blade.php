@extends('layouts.app')

@section('title', 'Crear equipo')
@section('header', 'Crear equipo')

@section('content')
@include('equipos.partials.form', [
    'action' => route('equipos.store'),
    'method' => 'POST',
    'equipo' => null,
])
@endsection
