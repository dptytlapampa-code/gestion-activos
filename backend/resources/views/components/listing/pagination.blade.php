@props(['paginator'])

@if ($paginator->total() > 0)
    <div class="flex flex-col gap-3 pt-4 lg:flex-row lg:items-center lg:justify-between">
        <p class="text-sm text-slate-500">
            Mostrando
            <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
            a
            <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            de
            <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
            registros.
        </p>

        @if ($paginator->hasPages())
            <div>
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endif
