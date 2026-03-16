@extends('layouts.app')
@section('title', 'Crear usuario')
@section('header', 'Crear usuario')
@section('content')
<form method="POST" action="{{ route('admin.users.store') }}" class="card">@include('admin.users.form')</form>
@endsection
