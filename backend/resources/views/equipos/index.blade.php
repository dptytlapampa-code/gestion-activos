@extends('layouts.app')

@section('title', 'Equipos')
@section('header', 'Equipos')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Listado de equipos</h2>
            <p class="text-sm text-slate-500">Gestion visual de activos biomedicos y tecnologicos.</p>
        </div>
        @can('create', \App\Models\Equipo::class)
            <a href="{{ route('equipos.create') }}" class="btn btn-primary gap-2">
                <x-icon name="plus" class="h-4 w-4" />
                Crear equipo
            </a>
        @endcan
    </div>

    <form method="GET" class="card grid gap-4 md:grid-cols-5">
        <input name="tipo" value="{{ request('tipo') }}" placeholder="Tipo" class="app-input" />
        <input name="marca" value="{{ request('marca') }}" placeholder="Marca" class="app-input" />
        <input name="modelo" value="{{ request('modelo') }}" placeholder="Modelo" class="app-input" />
        <select name="estado" class="app-input">
            <option value="">Todos los estados</option>
            @foreach($estados as $estado)
                <option value="{{ $estado }}" @selected(request('estado')===$estado)>{{ strtoupper(str_replace('_', ' ', $estado)) }}</option>
            @endforeach
        </select>
        <div class="flex items-center gap-2">
            <button class="btn btn-primary gap-2">
                <x-icon name="search" class="h-4 w-4" />
                Buscar
            </button>
            <a href="{{ route('equipos.index') }}" class="btn btn-neutral gap-2">
                <x-icon name="x" class="h-4 w-4" />
                Limpiar
            </a>
        </div>
    </form>

    <div class="app-table-panel overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Estado</th>
                    <th>N serie</th>
                    <th>Ubicacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($equipos as $equipo)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-tipo-equipo-image :tipo-equipo="$equipo->tipoEquipo" size="xs" class="rounded-lg" />
                            <span>{{ $equipo->tipo }}</span>
                        </div>
                    </td>
                    <td>{{ $equipo->marca }}</td>
                    <td>{{ $equipo->modelo }}</td>
                    <td>
                        @php($estadoClase = match($equipo->estado) {
                            'operativo' => 'status-operativo',
                            'prestado' => 'status-prestado',
                            'mantenimiento' => 'status-mantenimiento',
                            'fuera_de_servicio' => 'status-fuera-de-servicio',
                            'baja' => 'status-baja',
                            default => 'bg-slate-100 text-slate-700'
                        })
                        <span class="status-badge {{ $estadoClase }}">{{ strtoupper(str_replace('_', ' ', $equipo->estado)) }}</span>
                    </td>
                    <td>{{ $equipo->numero_serie }}</td>
                    <td>
                        <div class="space-y-1">
                            <div class="font-semibold text-slate-900">{{ $equipo->oficina?->service?->institution?->nombre }}</div>
                            <div class="text-sm text-slate-600">{{ $equipo->oficina?->service?->nombre }}</div>
                            <div class="text-xs text-slate-400">{{ $equipo->oficina?->nombre }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-2">
                            @can('view', $equipo)
                                <a class="btn btn-neutral !px-3 !py-1.5 gap-1.5" href="{{ route('equipos.show',$equipo) }}">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Ver
                                </a>
                            @endcan
                            @can('update', $equipo)
                                <a class="btn btn-info !px-3 !py-1.5 gap-1.5" href="{{ route('equipos.edit',$equipo) }}">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                    Editar
                                </a>
                            @endcan
                            @can('delete', $equipo)
                                <form method="POST" action="{{ route('equipos.destroy',$equipo) }}" onsubmit="return confirm('Eliminar equipo?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger !px-3 !py-1.5 gap-1.5">
                                        <x-icon name="trash-2" class="h-4 w-4" />
                                        Eliminar
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-slate-500">Sin resultados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-2">
        {{ $equipos->links() }}
    </div>
</div>
@endsection

