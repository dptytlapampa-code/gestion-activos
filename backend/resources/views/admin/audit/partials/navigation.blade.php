<div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
    <div class="min-w-0 max-w-3xl">
        <p class="text-sm leading-6 text-slate-500">Auditoria institucional con eventos visibles y consulta profunda bajo demanda.</p>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
        <a
            href="{{ route('admin.audit.live') }}"
            class="btn w-full sm:w-auto {{ request()->routeIs('admin.audit.live') ? 'btn-primary' : 'btn-neutral' }} !px-3 !py-2"
        >
            Actividad en vivo
        </a>
        <a
            href="{{ route('admin.audit.index') }}"
            class="btn w-full sm:w-auto {{ request()->routeIs('admin.audit.index') ? 'btn-primary' : 'btn-neutral' }} !px-3 !py-2"
        >
            Consulta avanzada
        </a>
    </div>
</div>
