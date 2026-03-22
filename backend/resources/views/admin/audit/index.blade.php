@extends('layouts.app')

@section('title', 'Consulta avanzada de auditoria')
@section('header', 'Consulta avanzada de auditoria')

@section('content')
    <div class="space-y-5">
        @include('admin.audit.partials.navigation')

        <section class="app-panel p-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Busqueda profunda de eventos</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Filtre por periodo, usuario, institucion, modulo, accion, entidad y criterios de criticidad.
                </p>
            </div>

            <form method="GET" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="date_from" class="mb-1 block text-sm font-medium text-slate-700">Desde</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="app-input" />
                </div>

                <div>
                    <label for="date_to" class="mb-1 block text-sm font-medium text-slate-700">Hasta</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="app-input" />
                </div>

                <div>
                    <label for="user_id" class="mb-1 block text-sm font-medium text-slate-700">Usuario</label>
                    <select id="user_id" name="user_id" class="app-input">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="institution_id" class="mb-1 block text-sm font-medium text-slate-700">Institucion</label>
                    <select id="institution_id" name="institution_id" class="app-input">
                        <option value="">Todas</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) ($filters['institution_id'] ?? '') === (string) $institution->id)>{{ $institution->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="module" class="mb-1 block text-sm font-medium text-slate-700">Modulo</label>
                    <select id="module" name="module" class="app-input">
                        <option value="">Todos</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}" @selected((string) ($filters['module'] ?? '') === (string) $module)>{{ \Illuminate\Support\Str::headline((string) $module) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="action" class="mb-1 block text-sm font-medium text-slate-700">Accion</label>
                    <select id="action" name="action" class="app-input">
                        <option value="">Todas</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected((string) ($filters['action'] ?? '') === (string) $action)>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $action)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="entity_type" class="mb-1 block text-sm font-medium text-slate-700">Tipo de entidad</label>
                    <select id="entity_type" name="entity_type" class="app-input">
                        <option value="">Todos</option>
                        @foreach ($entityTypes as $entityType)
                            <option value="{{ $entityType }}" @selected((string) ($filters['entity_type'] ?? '') === (string) $entityType)>{{ \Illuminate\Support\Str::headline(str_replace(['_', '.'], ' ', (string) $entityType)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="entity_id" class="mb-1 block text-sm font-medium text-slate-700">ID de entidad</label>
                    <input id="entity_id" name="entity_id" type="number" min="1" value="{{ $filters['entity_id'] ?? '' }}" class="app-input" placeholder="Ej. 125" />
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <label for="text" class="mb-1 block text-sm font-medium text-slate-700">Texto libre</label>
                    <input
                        id="text"
                        name="text"
                        type="text"
                        value="{{ $filters['text'] ?? '' }}"
                        class="app-input"
                        placeholder="Buscar por resumen, usuario, modulo, accion o correlation_id"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-4 pt-7">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="only_accesses" value="1" class="rounded border-slate-300" @checked((bool) ($filters['only_accesses'] ?? false))>
                        <span>Solo accesos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="only_critical" value="1" class="rounded border-slate-300" @checked((bool) ($filters['only_critical'] ?? false))>
                        <span>Solo cambios criticos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="only_errors" value="1" class="rounded border-slate-300" @checked((bool) ($filters['only_errors'] ?? false))>
                        <span>Solo errores o eventos relevantes</span>
                    </label>
                </div>

                <div class="md:col-span-2 xl:col-span-4 flex flex-wrap justify-end gap-2">
                    <a href="{{ route('admin.audit.index') }}" class="btn btn-neutral !px-3 !py-2">Limpiar</a>
                    <button class="btn btn-primary !px-4 !py-2">Filtrar</button>
                </div>
            </form>
        </section>

        @include('admin.audit.partials.table', ['logs' => $logs])

        <div>
            {{ $logs->links() }}
        </div>
    </div>
@endsection
