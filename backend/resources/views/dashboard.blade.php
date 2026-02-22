@extends('layouts.app')

@section('title', 'Panel')
@section('header', 'Panel general')

@section('content')
    <section class="card max-w-3xl">
        <h3 class="text-lg font-semibold text-surface-800">Infraestructura base lista</h3>
        <p class="mt-3 text-sm text-surface-600">
            La instancia Laravel 11.x sobre PHP 8.3 quedó operativa con autenticación básica.
        </p>
        <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-surface-600">
            <li>Stack: Laravel + Blade + Tailwind + Alpine.js.</li>
            <li>Base de datos: PostgreSQL con conexión por variables de entorno.</li>
            <li>Entorno de ejecución: Docker Compose para app, web y base de datos.</li>
        </ul>
    </section>
@endsection
