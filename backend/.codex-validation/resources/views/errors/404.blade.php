@extends('errors.layout')

@section('title', 'Pagina no encontrada')

@section('content')
    @include('errors.partials.card', [
        'status' => 404,
        'title' => $error['title'] ?? 'No encontramos lo que esta buscando',
        'message' => $error['message'] ?? 'El recurso solicitado no existe o ya no esta disponible.',
        'reason' => $error['reason'] ?? 'La direccion puede estar incompleta o el registro pudo haber sido eliminado.',
        'nextSteps' => $error['next_steps'] ?? 'Regrese al panel y vuelva a navegar desde el menu principal.',
    ])
@endsection
