<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="text-sm text-slate-500">Auditoria institucional con eventos visibles y consulta profunda bajo demanda.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a
            href="{{ route('admin.audit.live') }}"
            class="btn {{ request()->routeIs('admin.audit.live') ? 'btn-primary' : 'btn-neutral' }} !px-3 !py-2"
        >
            Actividad en vivo
        </a>
        <a
            href="{{ route('admin.audit.index') }}"
            class="btn {{ request()->routeIs('admin.audit.index') ? 'btn-primary' : 'btn-neutral' }} !px-3 !py-2"
        >
            Consulta avanzada
        </a>
    </div>
</div>
