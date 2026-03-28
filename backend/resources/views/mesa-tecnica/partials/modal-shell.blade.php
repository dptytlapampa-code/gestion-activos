@php
    $name = $name ?? '';
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $maxWidth = $maxWidth ?? 'max-w-6xl';
@endphp

<div
    x-cloak
    x-show="activeModal === '{{ $name }}'"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5"
    aria-modal="true"
    role="dialog"
>
    <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="closeModal()"></div>

    <div
        x-transition.scale.origin.center.duration.150ms
        class="relative max-h-[calc(100vh-1.5rem)] w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl {{ $maxWidth }}"
    >
        <div class="border-b border-slate-200 bg-slate-50/90 px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Mesa Tecnica</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
                    @endif
                </div>

                <button
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="closeModal()"
                    aria-label="Cerrar ventana"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div class="max-h-[calc(100vh-8rem)] overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
            {{ $slot }}
        </div>
    </div>
</div>
