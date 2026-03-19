@props([
    'resultsUrl',
    'allUrl',
    'hasActiveFilters' => false,
])

<div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/80 p-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-slate-900">Exportacion CSV</p>
        <p class="text-xs text-slate-500">
            {{ $hasActiveFilters
                ? 'Exportar resultados respeta los filtros activos. Exportar todo ignora filtros y paginacion.'
                : 'Exportar resultados descarga el conjunto visible actual. Exportar todo genera el listado completo disponible.' }}
        </p>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <a href="{{ $resultsUrl }}" class="btn btn-neutral gap-2">
            <x-icon name="download" class="h-4 w-4" />
            Exportar resultados
        </a>
        <a href="{{ $allUrl }}" class="btn btn-primary gap-2">
            <x-icon name="download" class="h-4 w-4" />
            Exportar todo
        </a>
    </div>
</div>
