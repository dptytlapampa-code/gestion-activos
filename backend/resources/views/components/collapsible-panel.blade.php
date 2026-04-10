@props([
    'title',
    'eyebrow' => null,
    'icon' => null,
    'description' => null,
    'summary' => null,
    'statusLabel' => null,
    'statusHint' => null,
    'statusClass' => 'border-slate-200 bg-slate-100 text-slate-700',
    'defaultOpen' => false,
    'forceOpen' => false,
    'persistKey' => null,
    'contentClass' => '',
    'iconClass' => 'text-slate-700',
    'eyebrowClass' => 'text-slate-500',
    'titleClass' => 'text-slate-950',
    'descriptionClass' => 'text-slate-600',
    'summaryClass' => 'text-slate-500',
    'toggleLabelOpen' => 'Ocultar',
    'toggleLabelClosed' => 'Ver',
])

<section
    {{ $attributes->class(['app-panel app-collapsible']) }}
    x-data="collapsiblePanel({
        defaultOpen: @js((bool) $defaultOpen),
        forceOpen: @js((bool) $forceOpen),
        persistKey: @js($persistKey),
    })"
    x-id="['collapsible-panel-content']"
    @app:collapsible-open.window="if ($event.detail && $event.detail.persistKey === persistKey) { open = true }"
>
    <div class="app-collapsible-header" :class="{ 'app-collapsible-header-open': open }">
        <button
            type="button"
            class="app-collapsible-trigger"
            @click="toggle()"
            :aria-expanded="open.toString()"
            :aria-controls="$id('collapsible-panel-content')"
        >
            <span class="app-collapsible-heading">
                @if ($icon)
                    <span class="app-collapsible-icon {{ $iconClass }}">
                        <x-icon :name="$icon" class="h-5 w-5" />
                    </span>
                @endif

                <span class="min-w-0">
                    @if ($eyebrow)
                        <span class="app-collapsible-eyebrow {{ $eyebrowClass }}">{{ $eyebrow }}</span>
                    @endif

                    <span class="app-collapsible-title {{ $titleClass }}">{{ $title }}</span>

                    @if ($description)
                        <span class="app-collapsible-description {{ $descriptionClass }}">{{ $description }}</span>
                    @endif

                    @if ($summary)
                        <span x-show="!open" x-cloak class="app-collapsible-summary {{ $summaryClass }}">{{ $summary }}</span>
                    @endif

                    @if ($statusLabel || $statusHint)
                        <span class="mt-2 flex flex-wrap items-center gap-2">
                            @if ($statusLabel)
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            @endif

                            @if ($statusHint)
                                <span class="text-xs font-medium text-slate-500">{{ $statusHint }}</span>
                            @endif
                        </span>
                    @endif
                </span>
            </span>

            <span class="app-collapsible-state">
                <span class="app-collapsible-state-label" x-text="open ? @js($toggleLabelOpen) : @js($toggleLabelClosed)"></span>
                <span class="app-collapsible-chevron" :class="{ 'rotate-180': open }">
                    <x-icon name="chevron-down" class="h-4 w-4" />
                </span>
            </span>
        </button>
    </div>

    <div
        :id="$id('collapsible-panel-content')"
        x-cloak
        x-show="open"
        class="app-collapsible-content"
        :aria-hidden="(! open).toString()"
    >
        <div @class(['app-collapsible-content-inner', $contentClass])>
            {{ $slot }}
        </div>
    </div>
</section>
