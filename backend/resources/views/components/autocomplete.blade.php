@props([
    'name',
    'endpoint',
    'placeholder' => 'Buscar...',
    'value' => null,
    'label' => null,
    'params' => [],
])

@php
    $current_value = old($name, $value);
    $current_label = old("{$name}_label", $label);
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        query: @js((string) ($current_label ?? '')),
        selected_id: @js($current_value !== null ? (string) $current_value : ''),
        selected_label: @js((string) ($current_label ?? '')),
        results: [],
        is_open: false,
        is_loading: false,
        debounce_timer: null,
        min_chars: 2,
        extra_params: @js($params),
        init() {
            window.addEventListener('autocomplete-set-params', (event) => {
                if (event.detail?.name !== @js($name)) {
                    return;
                }

                this.extra_params = event.detail?.params ?? {};
            });

            window.addEventListener('autocomplete-reset', (event) => {
                if (event.detail?.name !== @js($name)) {
                    return;
                }

                this.resetAutocomplete();
            });
        },
        sanitizedParams() {
            if (!this.extra_params || typeof this.extra_params !== 'object') {
                return {};
            }

            return Object.entries(this.extra_params).reduce((carry, [key, value]) => {
                if (value === null || value === undefined || value === '') {
                    return carry;
                }

                carry[key] = value;

                return carry;
            }, {});
        },
        async search() {
            if (this.debounce_timer) {
                clearTimeout(this.debounce_timer);
            }

            this.debounce_timer = setTimeout(async () => {
                if (this.query.trim().length < this.min_chars) {
                    this.results = [];
                    this.is_open = false;
                    return;
                }

                this.is_loading = true;

                try {
                    const searchParams = new URLSearchParams({
                        q: this.query.trim(),
                        ...this.sanitizedParams(),
                    });

                    const response = await fetch(`${@js($endpoint)}?${searchParams.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        this.results = [];
                        this.is_open = false;
                        return;
                    }

                    const payload = await response.json();
                    this.results = Array.isArray(payload) ? payload : [];
                    this.is_open = this.results.length > 0;
                } finally {
                    this.is_loading = false;
                }
            }, 300);
        },
        selectItem(item) {
            this.selected_id = String(item.id);
            this.selected_label = item.label;
            this.query = item.label;
            this.results = [];
            this.is_open = false;

            this.$refs.hidden_input.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.hidden_input.dispatchEvent(new Event('change', { bubbles: true }));

            window.dispatchEvent(new CustomEvent('autocomplete-selected', {
                detail: {
                    name: @js($name),
                    value: this.selected_id,
                    label: this.selected_label,
                },
            }));
        },
        clearSelection() {
            this.selected_id = '';
            this.selected_label = '';

            this.$refs.hidden_input.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.hidden_input.dispatchEvent(new Event('change', { bubbles: true }));

            window.dispatchEvent(new CustomEvent('autocomplete-cleared', {
                detail: {
                    name: @js($name),
                },
            }));
        },
        resetAutocomplete() {
            this.query = '';
            this.results = [];
            this.is_open = false;
            this.clearSelection();
        },
    }"
    @click.outside="is_open = false"
>
    <input
        type="text"
        id="{{ $name }}"
        x-model="query"
        @input="search(); if (query !== selected_label) { clearSelection(); }"
        @focus="if (results.length > 0) { is_open = true; }"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
    />

    <input type="hidden" name="{{ $name }}" x-ref="hidden_input" x-model="selected_id" />
    <input type="hidden" name="{{ $name }}_label" :value="selected_label" />

    <div x-show="is_loading" x-cloak class="pointer-events-none absolute right-3 top-3 text-xs text-slate-400">
        Buscando...
    </div>

    <div
        x-show="is_open"
        x-cloak
        class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        <ul class="py-1">
            <template x-for="item in results" :key="item.id">
                <li>
                    <button
                        type="button"
                        @click="selectItem(item)"
                        class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-100"
                        x-text="item.label"
                    ></button>
                </li>
            </template>
        </ul>
    </div>
</div>
