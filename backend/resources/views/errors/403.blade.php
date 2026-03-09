@extends('errors.layout')

@section('title', 'Acceso denegado')

@section('content')
    @include('errors.partials.card', [
        'status' => 403,
        'title' => $error['title'] ?? 'No tiene permisos para esta accion',
        'message' => $error['message'] ?? 'No se pudo completar la operacion porque su usuario no tiene permisos.',
        'reason' => $error['reason'] ?? 'Su rol actual no tiene acceso a este recurso o a esta operacion.',
        'nextSteps' => $error['next_steps'] ?? 'Regrese al panel o consulte con un administrador para validar permisos.',
    ])
@endsection
