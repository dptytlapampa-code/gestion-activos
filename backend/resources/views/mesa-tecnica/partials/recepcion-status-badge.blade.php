@props([
    'status' => '',
    'label' => null,
])

@php
    $status = (string) $status;
    [$classes, $icon] = match ($status) {
        \App\Models\RecepcionTecnica::ESTADO_RECIBIDO => ['border border-slate-200 bg-slate-50 text-slate-700', 'file-text'],
        \App\Models\RecepcionTecnica::ESTADO_EN_DIAGNOSTICO => ['border border-sky-200 bg-sky-50/70 text-sky-700', 'search'],
        \App\Models\RecepcionTecnica::ESTADO_EN_REPARACION => ['border border-indigo-200 bg-indigo-50/70 text-indigo-700', 'wrench'],
        \App\Models\RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO => ['border border-amber-200 bg-amber-50/75 text-amber-700', 'boxes'],
        \App\Models\RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR => ['border border-emerald-200 bg-emerald-50/70 text-emerald-800', 'check-circle-2'],
        \App\Models\RecepcionTecnica::ESTADO_ENTREGADO => ['border border-slate-300 bg-slate-100 text-slate-700', 'door-closed'],
        \App\Models\RecepcionTecnica::ESTADO_NO_REPARABLE => ['border border-rose-200 bg-rose-50/75 text-rose-700', 'alert-circle'],
        \App\Models\RecepcionTecnica::ESTADO_CANCELADO => ['border border-red-200 bg-red-50/75 text-red-700', 'x'],
        default => ['border border-slate-200 bg-slate-100 text-slate-700', 'info'],
    };
@endphp

<span {{ $attributes->class(['app-badge inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] uppercase tracking-[0.14em]', $classes]) }}>
    <x-icon :name="$icon" class="h-3.5 w-3.5" />
    <span>{{ $label ?? (\App\Models\RecepcionTecnica::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status))) }}</span>
</span>
