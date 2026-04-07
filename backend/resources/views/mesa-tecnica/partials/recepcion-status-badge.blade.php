@props([
    'status' => '',
    'label' => null,
])

@php
    $status = (string) $status;
    $classes = match ($status) {
        \App\Models\RecepcionTecnica::ESTADO_RECIBIDO => 'bg-slate-100 text-slate-700',
        \App\Models\RecepcionTecnica::ESTADO_EN_DIAGNOSTICO => 'bg-blue-100 text-blue-700',
        \App\Models\RecepcionTecnica::ESTADO_EN_REPARACION => 'bg-indigo-100 text-indigo-700',
        \App\Models\RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO => 'bg-amber-100 text-amber-700',
        \App\Models\RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR => 'bg-emerald-100 text-emerald-700',
        \App\Models\RecepcionTecnica::ESTADO_ENTREGADO => 'bg-emerald-100 text-emerald-800',
        \App\Models\RecepcionTecnica::ESTADO_NO_REPARABLE => 'bg-rose-100 text-rose-700',
        \App\Models\RecepcionTecnica::ESTADO_CANCELADO => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->class(['app-badge px-3', $classes]) }}>
    {{ $label ?? (\App\Models\RecepcionTecnica::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status))) }}
</span>
