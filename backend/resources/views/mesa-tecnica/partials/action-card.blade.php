@php
    $title = $title ?? '';
    $description = $description ?? '';
    $meta = $meta ?? null;
    $icon = $icon ?? 'plus';
    $tone = $tone ?? 'slate';
    $click = $click ?? null;

    $tones = [
        'emerald' => [
            'card' => 'border-emerald-200 bg-emerald-50/90 text-emerald-950 hover:border-emerald-300 hover:bg-emerald-100/80',
            'icon' => 'bg-emerald-600 text-white shadow-emerald-200/70',
            'meta' => 'text-emerald-700',
            'eyebrow' => 'bg-white/90 text-emerald-700',
        ],
        'blue' => [
            'card' => 'border-blue-200 bg-blue-50/90 text-blue-950 hover:border-blue-300 hover:bg-blue-100/80',
            'icon' => 'bg-blue-600 text-white shadow-blue-200/70',
            'meta' => 'text-blue-700',
            'eyebrow' => 'bg-white/90 text-blue-700',
        ],
        'slate' => [
            'card' => 'border-slate-200 bg-white/95 text-slate-950 hover:border-slate-300 hover:bg-slate-50',
            'icon' => 'bg-slate-900 text-white shadow-slate-200/70',
            'meta' => 'text-slate-600',
            'eyebrow' => 'bg-slate-100 text-slate-700',
        ],
        'amber' => [
            'card' => 'border-amber-200 bg-amber-50/95 text-amber-950 hover:border-amber-300 hover:bg-amber-100/85',
            'icon' => 'bg-amber-500 text-amber-950 shadow-amber-200/80',
            'meta' => 'text-amber-700',
            'eyebrow' => 'bg-white/90 text-amber-700',
        ],
        'indigo' => [
            'card' => 'border-indigo-200 bg-indigo-50/95 text-indigo-950 hover:border-indigo-300 hover:bg-indigo-100/85',
            'icon' => 'bg-indigo-600 text-white shadow-indigo-200/70',
            'meta' => 'text-indigo-700',
            'eyebrow' => 'bg-white/90 text-indigo-700',
        ],
    ];

    $toneData = $tones[$tone] ?? $tones['slate'];
@endphp

<button
    type="button"
    @if ($click)
        x-on:click="{{ $click }}"
    @endif
    class="group relative flex min-h-[9.25rem] w-full flex-col items-start justify-between overflow-hidden rounded-[1.75rem] border px-4 py-4 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 {{ $toneData['card'] }}"
>
    <div class="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-white/45 to-transparent"></div>

    <div class="relative flex w-full items-start justify-between gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl shadow-lg {{ $toneData['icon'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>

        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $toneData['eyebrow'] }}">
            Abrir
        </span>
    </div>

    <div class="relative space-y-1.5">
        <h3 class="text-base font-semibold tracking-tight">{{ $title }}</h3>

        @if ($description !== '')
            <p class="text-sm leading-5 text-slate-600">{{ $description }}</p>
        @endif

        @if ($meta)
            <p class="text-xs font-semibold uppercase tracking-[0.16em] {{ $toneData['meta'] }}">{{ $meta }}</p>
        @endif
    </div>
</button>
