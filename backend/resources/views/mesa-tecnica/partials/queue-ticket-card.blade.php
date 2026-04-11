@props([
    'recepcion',
    'returnTo' => null,
    'showDetailAction' => true,
])

@php
    /** @var \App\Models\RecepcionTecnica $recepcion */
    $returnUrl = $returnTo ?: request()->fullUrl();

    $cardClass = match (true) {
        $recepcion->isReadyForDelivery() => 'mt-queue-card-state-ready',
        $recepcion->estado === \App\Models\RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO => 'mt-queue-card-state-pending',
        $recepcion->isClosed(), $recepcion->isCancelled() => 'mt-queue-card-state-final',
        default => 'mt-queue-card-state-active',
    };

    $primaryUrl = $recepcion->canBeIncorporated()
        ? route('mesa-tecnica.recepciones-tecnicas.incorporate.create', ['recepcionTecnica' => $recepcion, 'return_to' => $returnUrl])
        : route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $recepcion, 'return_to' => $returnUrl]);

    $primaryLabel = match (true) {
        $recepcion->isReadyForDelivery() => 'Entregar',
        $recepcion->isClosed(), $recepcion->isCancelled() => 'Ver historial',
        default => 'Continuar',
    };

    $primaryButtonClass = match (true) {
        $recepcion->isReadyForDelivery() => 'btn btn-emerald',
        $recepcion->isClosed(), $recepcion->isCancelled() => 'btn btn-slate',
        default => 'btn btn-indigo',
    };

    $actionSummary = $recepcion->canBeIncorporated()
        ? 'Falta vincular el equipo al ticket.'
        : $recepcion->nextActionLabel();
@endphp

<article @class(['mt-queue-card', $cardClass])>
    <div class="mt-queue-card-main">
        <div class="mt-queue-card-head">
            <span class="app-badge bg-slate-900 px-3 text-white">{{ $recepcion->codigo }}</span>
            @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcion->estado, 'label' => $recepcion->statusLabel()])
        </div>

        <div class="space-y-2">
            <h3 class="text-base font-semibold tracking-tight text-slate-950">{{ $recepcion->equipmentReference() }}</h3>

            <div class="mt-queue-card-facts">
                <p class="mt-queue-fact">
                    <x-icon name="building-2" class="h-4 w-4" />
                    <span>{{ $recepcion->procedenciaResumen() }}</span>
                </p>

                <p class="mt-queue-fact">
                    <x-icon name="users" class="h-4 w-4" />
                    <span>{{ $recepcion->receptorResumen() }}</span>
                </p>

                <p class="mt-queue-fact">
                    <x-icon name="file-text" class="h-4 w-4" />
                    <span>{{ $recepcion->ingresado_at?->format('d/m/Y H:i') ?: '-' }}</span>
                </p>
            </div>
        </div>
    </div>

    <div class="mt-queue-card-actions">
        <div class="mt-queue-card-summary">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Accion sugerida</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $actionSummary }}</p>
        </div>

        <a href="{{ $primaryUrl }}" class="{{ $primaryButtonClass }}">
            <x-icon name="{{ $recepcion->isReadyForDelivery() ? 'check-circle-2' : 'eye' }}" class="h-4 w-4" />
            {{ $primaryLabel }}
        </a>

        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcion) }}" target="_blank" rel="noopener noreferrer" class="btn btn-slate">
            <x-icon name="printer" class="h-4 w-4" />
            Imprimir
        </a>

        @if ($showDetailAction)
            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $recepcion, 'return_to' => $returnUrl]) }}" class="mt-queue-card-link">
                Ver detalle
            </a>
        @endif
    </div>
</article>
