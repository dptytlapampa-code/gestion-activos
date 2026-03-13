@extends('errors.layout')

@section('title', 'Servicio no disponible')

@section('content')
    @include('errors.partials.card', [
        'status' => 503,
        'title' => $error['title'] ?? 'Servicio temporalmente no disponible',
        'message' => $error['message'] ?? 'El sistema no pudo completar la operacion debido a un problema tecnico temporal.',
        'reason' => $error['reason'] ?? 'Puede haber mantenimiento o una dependencia externa no disponible.',
        'nextSteps' => $error['next_steps'] ?? 'Intente nuevamente en unos minutos.',
    ])
@endsection
