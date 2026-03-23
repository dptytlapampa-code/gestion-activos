@php
    use Illuminate\Support\Str;
@endphp

<div class="app-table-panel">
    <div class="space-y-3 p-3 sm:p-4 md:hidden">
        @forelse ($logs as $log)
            @php
                $levelClasses = match ($log->level) {
                    \App\Models\AuditLog::LEVEL_CRITICAL => 'bg-red-100 text-red-700',
                    \App\Models\AuditLog::LEVEL_ERROR => 'bg-rose-100 text-rose-700',
                    \App\Models\AuditLog::LEVEL_WARNING => 'bg-amber-100 text-amber-700',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp

            <article class="app-subcard p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 app-cell-wrap">{{ $log->summary }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }} @if ($log->created_at) | {{ $log->created_at?->diffForHumans() ?? '' }} @endif</p>
                    </div>

                    <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-neutral !px-3 !py-2">Ver detalle</a>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="app-badge bg-slate-100 text-slate-600">
                        {{ Str::headline((string) $log->module) }}
                    </span>
                    <span class="app-badge {{ $levelClasses }}">
                        {{ Str::headline(str_replace('_', ' ', (string) $log->action)) }}
                    </span>
                    @if ($log->is_critical)
                        <span class="app-badge bg-red-50 text-red-700">Critico</span>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usuario</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $log->user?->name ?? 'Sistema' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entidad</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $log->display_entity_type }}</p>
                        <p class="mt-1 text-xs text-slate-500">ID {{ $log->resolved_entity_id ?? '-' }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Institucion</p>
                        <p class="mt-1 text-sm font-medium text-slate-900 app-cell-wrap">{{ $log->institution?->nombre ?? 'No aplica' }}</p>
                    </div>
                </div>

                @if ($log->correlation_id)
                    <div class="mt-4 rounded-xl bg-slate-50 px-3 py-3 text-xs text-slate-500">
                        <p class="font-semibold text-slate-600">Correlation ID</p>
                        <div class="mt-1 overflow-x-auto">
                            <span class="inline-block whitespace-nowrap font-mono text-slate-700">{{ $log->correlation_id }}</span>
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="px-4 py-8 text-center text-sm text-slate-500">
                No hay eventos para mostrar con los criterios actuales.
            </div>
        @endforelse
    </div>

    <div class="hidden overflow-x-auto md:block">
        <table class="app-table min-w-[76rem] text-sm">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Modulo</th>
                    <th>Accion</th>
                    <th>Entidad</th>
                    <th>Resumen</th>
                    <th>Institucion</th>
                    <th class="text-right">Detalle</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $levelClasses = match ($log->level) {
                            \App\Models\AuditLog::LEVEL_CRITICAL => 'bg-red-100 text-red-700',
                            \App\Models\AuditLog::LEVEL_ERROR => 'bg-rose-100 text-rose-700',
                            \App\Models\AuditLog::LEVEL_WARNING => 'bg-amber-100 text-amber-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <tr>
                        <td class="app-cell-nowrap">
                            <p class="font-medium text-slate-800">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->created_at?->diffForHumans() ?? '' }}</p>
                        </td>
                        <td class="app-cell-nowrap">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td class="app-cell-nowrap">
                            <span class="app-badge bg-slate-100 text-slate-600">
                                {{ Str::headline((string) $log->module) }}
                            </span>
                        </td>
                        <td class="app-cell-nowrap">
                            <span class="app-badge {{ $levelClasses }}">
                                {{ Str::headline(str_replace('_', ' ', (string) $log->action)) }}
                            </span>
                        </td>
                        <td class="app-cell-nowrap">
                            <p class="font-medium text-slate-800">{{ $log->display_entity_type }}</p>
                            <p class="mt-1 text-xs text-slate-500">ID {{ $log->resolved_entity_id ?? '-' }}</p>
                        </td>
                        <td class="min-w-[22rem]">
                            <p class="font-medium text-slate-800 app-cell-wrap">{{ $log->summary }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($log->is_critical)
                                    <span class="app-badge bg-red-50 text-red-700">Critico</span>
                                @endif
                            </div>
                            @if ($log->correlation_id)
                                <div class="mt-2 overflow-x-auto text-xs text-slate-500">
                                    <span class="font-semibold text-slate-600">Correlation ID:</span>
                                    <span class="inline-block whitespace-nowrap font-mono">{{ $log->correlation_id }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="min-w-[14rem] app-cell-wrap">{{ $log->institution?->nombre ?? 'No aplica' }}</td>
                        <td class="app-cell-nowrap text-right">
                            <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-neutral !px-3 !py-2">
                                Ver detalle
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-sm text-slate-500">
                            No hay eventos para mostrar con los criterios actuales.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
