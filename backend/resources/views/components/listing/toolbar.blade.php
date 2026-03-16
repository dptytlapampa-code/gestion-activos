@props([
    'search' => '',
    'perPage' => \App\Support\Listings\ListingState::DEFAULT_PER_PAGE,
    'searchId' => 'listing-search',
    'perPageId' => 'listing-per-page',
    'searchLabel' => 'Busqueda rapida',
    'searchPlaceholder' => 'Buscar...',
    'clearUrl' => null,
])

@php
    $clearUrl ??= url()->current();
@endphp

<div
    x-data="{
        search: @js($search),
        submit() {
            const form = this.$root.closest('form');

            if (form) {
                form.requestSubmit();
            }
        },
        clearSearch() {
            this.search = '';
            this.$nextTick(() => this.submit());
        },
    }"
    class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 lg:grid-cols-[minmax(0,1fr)_180px_auto] lg:items-end"
>
    <div>
        <label for="{{ $searchId }}" class="mb-2 block text-sm font-medium text-slate-700">{{ $searchLabel }}</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input
                id="{{ $searchId }}"
                name="search"
                type="search"
                x-model="search"
                @input.debounce.400ms="submit()"
                placeholder="{{ $searchPlaceholder }}"
                class="app-input w-full pl-10 pr-10"
                autocomplete="off"
            />
            <button
                type="button"
                x-cloak
                x-show="search.length > 0"
                @click="clearSearch()"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600"
                aria-label="Limpiar busqueda"
            >
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
        <p class="mt-2 text-xs text-slate-500">Se actualiza automaticamente mientras escribe.</p>
    </div>

    <div>
        <label for="{{ $perPageId }}" class="mb-2 block text-sm font-medium text-slate-700">Registros por pagina</label>
        <select
            id="{{ $perPageId }}"
            name="per_page"
            @change="submit()"
            class="app-input w-full"
        >
            @foreach (\App\Support\Listings\ListingState::perPageOptions() as $option)
                <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-center justify-start lg:justify-end">
        <a href="{{ $clearUrl }}" class="btn btn-neutral gap-2">
            <x-icon name="x" class="h-4 w-4" />
            Restablecer
        </a>
    </div>
</div>
