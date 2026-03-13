@extends('errors.layout')

@section('title', 'Sesion vencida')

@section('content')
    @include('errors.partials.card', [
        'status' => 419,
        'title' => $error['title'] ?? 'La sesion vencio',
        'message' => $error['message'] ?? 'La operacion no pudo completarse porque su sesion ya no es valida.',
        'reason' => $error['reason'] ?? 'Esto suele pasar por inactividad prolongada o por seguridad del navegador.',
        'nextSteps' => $error['next_steps'] ?? 'Ingrese nuevamente al sistema y repita la accion.',
    ])
@endsection
