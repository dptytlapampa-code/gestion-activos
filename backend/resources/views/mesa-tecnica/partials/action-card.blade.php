@php
    $title = $title ?? '';
    $description = $description ?? '';
    $icon = $icon ?? 'plus';
    $tone = $tone ?? 'slate';
    $click = $click ?? null;

    $tones = [
        'emerald' => 'border-emerald-200 bg-emerald-50/90 text-emerald-950 hover:border-emerald-300 hover:bg-emerald-100/80',
        'blue' => 'border-blue-200 bg-blue-50/90 text-blue-950 hover:border-blue-300 hover:bg-blue-100/80',
        'slate' => 'border-slate-200 bg-white/95 text-slate-950 hover:border-slate-300 hover:bg-slate-50',
        'amber' => 'border-amber-200 bg-amber-50/95 text-amber-950 hover:border-amber-300 hover:bg-amber-100/85',
        'indigo' => 'border-indigo-200 bg-indigo-50/95 text-indigo-950 hover:border-indigo-300 hover:bg-indigo-100/85',
    ];

    $toneClasses = $tones[$tone] ?? $tones['slate'];
@endphp

<button
    type="button"
    @if ($click)
        x-on:click="{{ $click }}"
    @endif
    class="group relative flex min-h-[10.5rem] w-full flex-col items-start justify-between overflow-hidden rounded-[1.75rem] border p-5 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 {{ $toneClasses }}"
>
    <div class="absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-white/40 to-transparent"></div>

    <div class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-white/90 shadow-sm ring-1 ring-black/5">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>

    <div class="relative space-y-2">
        <h3 class="text-lg font-semibold tracking-tight">{{ $title }}</h3>
        <p class="text-sm leading-6 text-slate-600">{{ $description }}</p>
    </div>
</button>
