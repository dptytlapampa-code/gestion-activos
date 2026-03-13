@extends('layouts.app')
@section('title','Auditoría')
@section('header','Auditoría')
@section('content')
<form class="mb-4 grid gap-3 md:grid-cols-5">
    <input name="date_from" type="date" value="{{ request('date_from') }}" class="rounded border px-3 py-2">
    <input name="date_to" type="date" value="{{ request('date_to') }}" class="rounded border px-3 py-2">
    <select name="user_id" class="rounded border px-3 py-2"><option value="">Usuario</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) request('user_id')===(string)$user->id)>{{ $user->name }}</option>@endforeach</select>
    <input name="action" value="{{ request('action') }}" placeholder="Acción" class="rounded border px-3 py-2">
    <button class="rounded bg-indigo-600 text-white">Filtrar</button>
</form>
<div class="overflow-hidden rounded-xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-3 py-2">Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>ID</th></tr></thead><tbody>
@foreach($logs as $log)<tr class="border-t"><td class="px-3 py-2">{{ $log->created_at }}</td><td>{{ $log->user?->name ?? 'sistema' }}</td><td>{{ $log->action }}</td><td>{{ class_basename($log->auditable_type) }}</td><td>{{ $log->auditable_id }}</td></tr>@endforeach
</tbody></table></div>
{{ $logs->links() }}
@endsection
