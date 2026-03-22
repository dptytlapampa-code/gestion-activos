@php
    use Illuminate\Support\Str;
@endphp

<div class="app-table-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="app-table text-sm">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Modulo</th>
                    <th>Accion</th>
                    <th>Entidad</th>
                    <th>ID</th>
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
                            <p class="text-xs text-slate-500">{{ $log->created_at?->diffForHumans() ?? '' }}</p>
                        </td>
                        <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                {{ Str::headline((string) $log->module) }}
                            </span>
                        </td>
                        <td>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $levelClasses }}">
                                {{ Str::headline(str_replace('_', ' ', (string) $log->action)) }}
                            </span>
                        </td>
                        <td>{{ $log->display_entity_type }}</td>
                        <td>{{ $log->resolved_entity_id ?? '-' }}</td>
                        <td class="min-w-[24rem]">
                            <p class="font-medium text-slate-800">{{ $log->summary }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($log->is_critical)
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Critico</span>
                                @endif
                                @if ($log->correlation_id)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ Str::limit($log->correlation_id, 18, '...') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $log->institution?->nombre ?? 'No aplica' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-neutral !px-3 !py-2">
                                Ver detalle
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-sm text-slate-500">
                            No hay eventos para mostrar con los criterios actuales.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
