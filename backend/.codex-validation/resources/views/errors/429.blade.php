@extends('errors.layout')

@section('title', 'Demasiados intentos')

@section('content')
    @include('errors.partials.card', [
        'status' => 429,
        'title' => $error['title'] ?? 'Demasiados intentos',
        'message' => $error['message'] ?? 'Se detectaron demasiadas operaciones en poco tiempo.',
        'reason' => $error['reason'] ?? 'El sistema aplica limites temporales para proteger los servicios.',
        'nextSteps' => $error['next_steps'] ?? 'Espere unos segundos y vuelva a intentar.',
    ])
@endsection
