@extends('layouts.app')
@section('title', 'Crear usuario')
@section('header', 'Crear usuario')
@section('content')
<form method="POST" action="{{ route('admin.users.store') }}" class="rounded-xl border bg-white p-6">@include('admin.users.form')</form>
@endsection
