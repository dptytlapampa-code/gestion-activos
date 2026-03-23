@php
    use Illuminate\Support\Str;
@endphp

<div class="app-table-panel">
    <div class="xl:hidden">
        @forelse ($logs as $log)
            @php
                $levelClasses = match ($log->level) {
                    \App\Models\AuditLog::LEVEL_CRITICAL => 'bg-red-100 text-red-700',
                    \App\Models\AuditLog::LEVEL_ERROR => 'bg-rose-100 text-rose-700',
                    \App\Models\AuditLog::LEVEL_WARNING => 'bg-amber-100 text-amber-700',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp

            <article class="border-b border-slate-200/80 p-4 last:border-b-0 sm:p-5">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap gap-2">
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

                            <p class="mt-3 break-words text-sm font-semibold text-slate-900">{{ $log->summary }}</p>
                        </div>

                        <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-2">
                            Ver detalle
                        </a>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha y hora</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->created_at?->diffForHumans() ?? '' }}</p>
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usuario</p>
                            <p class="mt-1 break-words text-sm font-medium text-slate-900">{{ $log->user?->name ?? 'Sistema' }}</p>
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entidad</p>
                            <p class="mt-1 break-words text-sm font-medium text-slate-900">{{ $log->display_entity_type }}</p>
                            <p class="mt-1 text-xs text-slate-500">ID {{ $log->resolved_entity_id ?? '-' }}</p>
                        </div>

                        <div class="min-w-0 sm:col-span-2 lg:col-span-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Institucion</p>
                            <p class="mt-1 break-words text-sm font-medium text-slate-900">{{ $log->institution?->nombre ?? 'No aplica' }}</p>
                        </div>
                    </div>

                    @if ($log->correlation_id)
                        <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-3 text-xs text-slate-500">
                            <span class="font-semibold text-slate-600">Correlation ID:</span>
                            <span class="break-all">{{ $log->correlation_id }}</span>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="px-4 py-8 text-center text-sm text-slate-500 sm:px-5">
                No hay eventos para mostrar con los criterios actuales.
            </div>
        @endforelse
    </div>

    <div class="hidden xl:block">
        <table class="app-table text-sm">
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
                        <td>
                            <p class="font-medium text-slate-800">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->created_at?->diffForHumans() ?? '' }}</p>
                        </td>
                        <td class="break-words">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td>
                            <span class="app-badge bg-slate-100 text-slate-600">
                                {{ Str::headline((string) $log->module) }}
                            </span>
                        </td>
                        <td>
                            <span class="app-badge {{ $levelClasses }}">
                                {{ Str::headline(str_replace('_', ' ', (string) $log->action)) }}
                            </span>
                        </td>
                        <td>
                            <p class="break-words font-medium text-slate-800">{{ $log->display_entity_type }}</p>
                            <p class="mt-1 text-xs text-slate-500">ID {{ $log->resolved_entity_id ?? '-' }}</p>
                        </td>
                        <td class="min-w-0">
                            <p class="break-words font-medium text-slate-800">{{ $log->summary }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($log->is_critical)
                                    <span class="app-badge bg-red-50 text-red-700">Critico</span>
                                @endif
                            </div>
                            @if ($log->correlation_id)
                                <p class="mt-2 break-all text-xs text-slate-500">
                                    <span class="font-semibold text-slate-600">Correlation ID:</span>
                                    {{ $log->correlation_id }}
                                </p>
                            @endif
                        </td>
                        <td class="break-words">{{ $log->institution?->nombre ?? 'No aplica' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-neutral w-full 2xl:w-auto !px-3 !py-2">
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
