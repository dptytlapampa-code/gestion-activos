@extends('layouts.app')

@section('title', 'Equipos')
@section('header', 'Equipos')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-xl font-semibold text-slate-900">Listado de equipos</h2>
        @can('create', \App\Models\Equipo::class)
            <a href="{{ route('equipos.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Nuevo equipo</a>
        @endcan
    </div>

    <form method="GET" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-5">
        <input name="tipo" value="{{ request('tipo') }}" placeholder="Tipo" class="rounded-lg border-slate-300 text-sm" />
        <input name="marca" value="{{ request('marca') }}" placeholder="Marca" class="rounded-lg border-slate-300 text-sm" />
        <input name="modelo" value="{{ request('modelo') }}" placeholder="Modelo" class="rounded-lg border-slate-300 text-sm" />
        <select name="estado" class="rounded-lg border-slate-300 text-sm">
            <option value="">Todos los estados</option>
            @foreach($estados as $estado)
                <option value="{{ $estado }}" @selected(request('estado')===$estado)>{{ ucfirst($estado) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Buscar</button>
            <a href="{{ route('equipos.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Limpiar</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Marca</th><th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Estado</th><th class="px-4 py-3">N° serie</th><th class="px-4 py-3">Oficina</th><th class="px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($equipos as $equipo)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3">{{ $equipo->tipo }}</td>
                    <td class="px-4 py-3">{{ $equipo->marca }}</td>
                    <td class="px-4 py-3">{{ $equipo->modelo }}</td>
                    <td class="px-4 py-3">{{ ucfirst($equipo->estado) }}</td>
                    <td class="px-4 py-3">{{ $equipo->nro_serie }}</td>
                    <td class="px-4 py-3">{{ $equipo->oficina?->nombre }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-3">
                            @can('view', $equipo)<a class="text-indigo-600" href="{{ route('equipos.show',$equipo) }}">Ver</a>@endcan
                            @can('update', $equipo)<a class="text-amber-600" href="{{ route('equipos.edit',$equipo) }}">Editar</a>@endcan
                            @can('delete', $equipo)
                                <form method="POST" action="{{ route('equipos.destroy',$equipo) }}" onsubmit="return confirm('¿Eliminar equipo?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600">Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Sin resultados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $equipos->links() }}
</div>
@endsection
