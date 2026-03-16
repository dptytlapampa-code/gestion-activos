@props(['title' => ''])

<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-surface-900/40 backdrop-blur" @click="open = false"></div>
    <div class="app-panel relative w-full max-w-2xl p-6 dark:bg-surface-800">
        <div class="flex items-center justify-between border-b border-surface-200 pb-4 dark:border-surface-700">
            <h3 class="text-lg font-semibold text-surface-800 dark:text-surface-100">{{ $title }}</h3>
            <button type="button" class="text-surface-400 hover:text-surface-600 dark:hover:text-white" @click="open = false">✕</button>
        </div>
        <div class="pt-4">
            {{ $slot }}
        </div>
    </div>
</div>
