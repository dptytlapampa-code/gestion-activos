@props([
    'tipoEquipo' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'xs' => 'h-8 w-8',
        'sm' => 'h-10 w-10',
        'md' => 'h-14 w-14',
        'lg' => 'h-20 w-20',
        'xl' => 'h-28 w-28',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $imageUrl = $tipoEquipo?->image_url;
    $imageLabel = $tipoEquipo?->nombre ?? 'Tipo de equipo';
@endphp

<div {{ $attributes->class([$sizeClass, 'overflow-hidden rounded-xl border border-slate-200 bg-slate-50']) }}>
    @if ($imageUrl)
        <img
            src="{{ $imageUrl }}"
            alt="Imagen de {{ $imageLabel }}"
            class="h-full w-full bg-white object-contain p-1"
            loading="lazy"
        >
    @else
        <div class="flex h-full w-full items-center justify-center text-slate-400">
            <x-icon name="image" class="h-5 w-5" />
        </div>
    @endif
</div>