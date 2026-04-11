@php
    $title = $title ?? '';
    $description = $description ?? '';
    $meta = $meta ?? null;
    $icon = $icon ?? 'plus';
    $tone = $tone ?? 'slate';
    $click = $click ?? null;
    $href = $href ?? null;

    $tones = [
        'emerald' => [
            'card' => 'border-emerald-200 bg-emerald-50/80 text-emerald-950 hover:border-emerald-300 hover:bg-emerald-50',
            'icon' => 'bg-emerald-600 text-white',
            'meta' => 'text-emerald-700',
            'arrow' => 'text-emerald-700',
        ],
        'slate' => [
            'card' => 'border-slate-200 bg-white text-slate-950 hover:border-slate-300 hover:bg-slate-50',
            'icon' => 'bg-slate-900 text-white',
            'meta' => 'text-slate-600',
            'arrow' => 'text-slate-500',
        ],
        'amber' => [
            'card' => 'border-amber-200 bg-amber-50/70 text-amber-950 hover:border-amber-300 hover:bg-amber-50',
            'icon' => 'bg-amber-500 text-amber-950',
            'meta' => 'text-amber-700',
            'arrow' => 'text-amber-700',
        ],
        'indigo' => [
            'card' => 'border-indigo-200 bg-indigo-50/80 text-indigo-950 hover:border-indigo-300 hover:bg-indigo-50',
            'icon' => 'bg-indigo-600 text-white',
            'meta' => 'text-indigo-700',
            'arrow' => 'text-indigo-700',
        ],
    ];

    $toneData = $tones[$tone] ?? $tones['slate'];
@endphp

@if ($href)
    <a href="{{ $href }}" class="mt-action-shortcut {{ $toneData['card'] }}">
        <span class="mt-action-shortcut-icon {{ $toneData['icon'] }}">
            <x-icon :name="$icon" class="h-4 w-4" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold tracking-tight">{{ $title }}</span>
            @if ($description !== '')
                <span class="mt-1 block text-sm text-slate-600">{{ $description }}</span>
            @endif
            @if ($meta)
                <span class="mt-2 block text-[11px] font-semibold uppercase tracking-[0.16em] {{ $toneData['meta'] }}">{{ $meta }}</span>
            @endif
        </span>

        <span class="mt-action-shortcut-arrow {{ $toneData['arrow'] }}">
            <x-icon name="chevron-down" class="h-4 w-4 -rotate-90" />
        </span>
    </a>
@else
    <button
        type="button"
        @if ($click)
            x-on:click="{{ $click }}"
        @endif
        class="mt-action-shortcut {{ $toneData['card'] }}"
    >
        <span class="mt-action-shortcut-icon {{ $toneData['icon'] }}">
            <x-icon :name="$icon" class="h-4 w-4" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold tracking-tight">{{ $title }}</span>
            @if ($description !== '')
                <span class="mt-1 block text-sm text-slate-600">{{ $description }}</span>
            @endif
            @if ($meta)
                <span class="mt-2 block text-[11px] font-semibold uppercase tracking-[0.16em] {{ $toneData['meta'] }}">{{ $meta }}</span>
            @endif
        </span>

        <span class="mt-action-shortcut-arrow {{ $toneData['arrow'] }}">
            <x-icon name="chevron-down" class="h-4 w-4 -rotate-90" />
        </span>
    </button>
@endif
