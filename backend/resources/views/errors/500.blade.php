@extends('errors.layout')

@section('title', 'Error del sistema')

@section('content')
    @include('errors.partials.card', [
        'status' => 500,
        'title' => $error['title'] ?? 'Algo salio mal',
        'message' => $error['message'] ?? 'El sistema no pudo completar la operacion.',
        'reason' => $error['reason'] ?? 'Puede deberse a un problema temporal o a una condicion no esperada.',
        'nextSteps' => $error['next_steps'] ?? 'Intente nuevamente. Si el problema persiste, contacte al administrador.',
    ])
@endsection
