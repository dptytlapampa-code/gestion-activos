@extends('layouts.app')
@section('title', 'Editar usuario')
@section('header', 'Editar usuario')
@section('content')
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="rounded-xl border bg-white p-6">@method('PUT') @include('admin.users.form')</form>
@endsection
