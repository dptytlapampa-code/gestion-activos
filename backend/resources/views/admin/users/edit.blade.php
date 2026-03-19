@extends('layouts.app')
@section('title', 'Editar usuario')
@section('header', 'Editar usuario')
@section('content')
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="card">@method('PUT') @include('admin.users.form')</form>
@endsection
