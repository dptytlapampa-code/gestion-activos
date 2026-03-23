@extends('layouts.app')

@section('title', 'Detalle del evento')
@section('header', 'Detalle del evento')

@section('content')
    @php
        $changes = $auditLog->detailChanges();
        $context = $auditLog->detailContext();
        $formatValue = function (mixed $value): string {
            if ($value === null || $value === '') {
                return 'Sin dato';
            }

            if (is_bool($value)) {
                return $value ? 'Si' : 'No';
            }

            if (is_array($value)) {
                return collect($value)
                    ->map(fn (mixed $item): string => is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) ?: '' : (string) $item)
                    ->filter()
                    ->implode(', ');
            }

            return (string) $value;
        };

        $levelClasses = match ($auditLog->level) {
            \App\Models\AuditLog::LEVEL_CRITICAL => 'bg-red-100 text-red-700',
            \App\Models\AuditLog::LEVEL_ERROR => 'bg-rose-100 text-rose-700',
            \App\Models\AuditLog::LEVEL_WARNING => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="space-y-4 sm:space-y-5">
        @include('admin.audit.partials.navigation')

        <section class="app-panel p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 max-w-4xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Evento auditado</p>
                    <h2 class="mt-2 break-words text-2xl font-semibold text-slate-900">{{ $auditLog->summary }}</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $auditLog->created_at?->format('d/m/Y H:i:s') ?? '-' }}
                        @if ($auditLog->created_at)
                            | {{ $auditLog->created_at->diffForHumans() }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('admin.audit.live') }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-2">Volver a actividad</a>
                    <a href="{{ route('admin.audit.index') }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-2">Ir a consulta</a>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quien</p>
                    <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ $auditLog->user?->name ?? 'Sistema' }}</p>
                    <p class="mt-1 break-words text-xs text-slate-500">{{ $auditLog->user?->email ?? 'Sin correo asociado' }}</p>
                </div>

                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modulo y accion</p>
                    <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline((string) $auditLog->module) }}</p>
                    <p class="mt-1 break-words text-xs text-slate-500">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $auditLog->action)) }}</p>
                </div>

                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entidad</p>
                    <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ $auditLog->display_entity_type }}</p>
                    <p class="mt-1 text-xs text-slate-500">ID {{ $auditLog->resolved_entity_id ?? '-' }}</p>
                </div>

                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Criticidad</p>
                    <p class="mt-2">
                        <span class="app-badge {{ $levelClasses }}">
                            {{ \Illuminate\Support\Str::headline((string) $auditLog->level) }}
                        </span>
                    </p>
                    <p class="mt-2 text-xs text-slate-500">{{ $auditLog->is_critical ? 'Marcado como critico' : 'Evento informativo o de advertencia' }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Institucion</p>
                    <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ $auditLog->institution?->nombre ?? 'No aplica' }}</p>
                </div>

                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correlation ID</p>
                    <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ $auditLog->correlation_id ?? 'Sin correlacion' }}</p>
                </div>

                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">IP</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $auditLog->ip ?? 'No disponible' }}</p>
                </div>

                <div class="app-subcard p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agente de usuario</p>
                    <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ $auditLog->user_agent ?? 'No disponible' }}</p>
                </div>
            </div>
        </section>

        <section class="app-panel p-4 sm:p-5 lg:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Contexto entendible</h3>
            <p class="mt-1 text-sm text-slate-500">Resumen puntual de lo que ocurrio y sobre que elementos impacta.</p>

            @if ($context !== [])
                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($context as $item)
                        <div class="app-subcard p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 break-words text-sm font-medium text-slate-900">{{ $formatValue($item['value']) }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    El evento no incluye contexto adicional mas alla del resumen principal.
                </div>
            @endif
        </section>

        <section class="app-panel p-4 sm:p-5 lg:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Cambios detectados</h3>
            <p class="mt-1 text-sm text-slate-500">Comparacion campo por campo cuando el evento informo diferencias relevantes.</p>

            @if ($changes !== [])
                <div class="mt-5 space-y-3 md:hidden">
                    @foreach ($changes as $change)
                        <article class="app-subcard p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $change['label'] }}</p>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Antes</p>
                                    <p class="mt-1 break-words text-sm text-slate-900">{{ $formatValue($change['before']) }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Despues</p>
                                    <p class="mt-1 break-words text-sm text-slate-900">{{ $formatValue($change['after']) }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5 hidden overflow-x-auto md:block">
                    <table class="app-table text-sm">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Antes</th>
                                <th>Despues</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($changes as $change)
                                <tr>
                                    <td class="font-medium text-slate-800">{{ $change['label'] }}</td>
                                    <td class="break-words">{{ $formatValue($change['before']) }}</td>
                                    <td class="break-words">{{ $formatValue($change['after']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    Este evento no requiere comparacion detallada de campos.
                </div>
            @endif
        </section>

        <section class="app-panel p-4 sm:p-5 lg:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Eventos relacionados</h3>
            <p class="mt-1 text-sm text-slate-500">Otros eventos registrados dentro de la misma operacion cuando comparten correlation ID.</p>

            @if ($relatedEvents->isNotEmpty())
                <div class="mt-5 space-y-3">
                    @foreach ($relatedEvents as $related)
                        <div class="app-subcard p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-semibold text-slate-900">{{ $related->summary }}</p>
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                        <span>{{ $related->created_at?->format('d/m/Y H:i:s') ?? '-' }}</span>
                                        <span>{{ $related->user?->name ?? 'Sistema' }}</span>
                                        <span>{{ \Illuminate\Support\Str::headline((string) $related->module) }}</span>
                                    </div>
                                </div>

                                <a href="{{ route('admin.audit.show', $related) }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-2">Abrir</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    No hay otros eventos asociados a esta misma operacion.
                </div>
            @endif
        </section>
    </div>
@endsection
