@props([
    'recepcion',
    'returnTo' => null,
    'priority' => 'recent',
    'priorityLabel' => 'Reciente',
    'priorityHint' => null,
    'ageLabel' => null,
    'ageTone' => 'neutral',
    'showDetailAction' => true,
])

@php
    /** @var \App\Models\RecepcionTecnica $recepcion */
    $returnUrl = $returnTo ?: request()->fullUrl();

    $priorityClasses = match ($priority) {
        'critical' => [
            'card' => 'mt-queue-card-priority-critical',
            'badge' => 'border-red-200 bg-red-50 text-red-700',
        ],
        'delayed' => [
            'card' => 'mt-queue-card-priority-delayed',
            'badge' => 'border-amber-200 bg-amber-50 text-amber-800',
        ],
        'ready' => [
            'card' => 'mt-queue-card-priority-ready',
            'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        ],
        default => [
            'card' => 'mt-queue-card-priority-recent',
            'badge' => 'border-slate-200 bg-slate-100 text-slate-700',
        ],
    };

    $ageClasses = match ($ageTone) {
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };

    $primaryUrl = $recepcion->canBeIncorporated()
        ? route('mesa-tecnica.recepciones-tecnicas.incorporate.create', ['recepcionTecnica' => $recepcion, 'return_to' => $returnUrl])
        : route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $recepcion, 'return_to' => $returnUrl]);

    $primaryLabel = match (true) {
        $recepcion->isReadyForDelivery() => 'Entregar',
        $recepcion->isClosed(), $recepcion->isCancelled() => 'Ver detalle',
        default => 'Continuar',
    };

    $primaryButtonClass = match (true) {
        $recepcion->isReadyForDelivery() => 'btn btn-emerald',
        default => 'btn btn-indigo',
    };

    $primaryIcon = $recepcion->isReadyForDelivery() ? 'check-circle-2' : 'eye';
    $currentLocation = collect([
        $recepcion->institution?->nombre,
        $recepcion->sector_receptor,
    ])->filter()->implode(' / ');
@endphp

<article @class(['mt-queue-card', $priorityClasses['card']])>
    <div class="mt-queue-card-main">
        <div class="mt-queue-card-head">
            <span class="app-badge bg-slate-900 px-3 text-white">{{ $recepcion->codigo }}</span>
            @include('mesa-tecnica.partials.recepcion-status-badge', ['status' => $recepcion->estado, 'label' => $recepcion->statusLabel()])
            <span class="app-badge border px-3 py-1.5 text-[11px] uppercase tracking-[0.16em] {{ $priorityClasses['badge'] }}">{{ $priorityLabel }}</span>
            @if ($ageLabel)
                <span class="app-badge border px-3 py-1.5 text-[11px] uppercase tracking-[0.16em] {{ $ageClasses }}">{{ $ageLabel }}</span>
            @endif
        </div>

        <div class="space-y-2">
            <h3 class="text-base font-semibold tracking-tight text-slate-950">{{ $recepcion->equipmentReference() }}</h3>
            <p class="text-sm text-slate-600">{{ $priorityHint ?: $recepcion->nextActionDescription() }}</p>
        </div>

        <div class="mt-queue-card-facts">
            <div class="mt-queue-fact">
                <x-icon name="building-2" class="h-4 w-4" />
                <span>
                    <span class="mt-queue-fact-label">Procedencia</span>
                    <span class="mt-queue-fact-value">{{ $recepcion->procedenciaResumen() }}</span>
                </span>
            </div>

            <div class="mt-queue-fact">
                <x-icon name="map-pin" class="h-4 w-4" />
                <span>
                    <span class="mt-queue-fact-label">Ubicacion actual</span>
                    <span class="mt-queue-fact-value">{{ $currentLocation !== '' ? $currentLocation : 'Mesa Tecnica' }}</span>
                </span>
            </div>

            <div class="mt-queue-fact">
                <x-icon name="users" class="h-4 w-4" />
                <span>
                    <span class="mt-queue-fact-label">Persona asociada</span>
                    <span class="mt-queue-fact-value">{{ $recepcion->receptorResumen() }}</span>
                </span>
            </div>

            <div class="mt-queue-fact">
                <x-icon name="file-text" class="h-4 w-4" />
                <span>
                    <span class="mt-queue-fact-label">Ingreso</span>
                    <span class="mt-queue-fact-value">{{ $recepcion->ingresado_at?->format('d/m/Y H:i') ?: '-' }}</span>
                </span>
            </div>
        </div>
    </div>

    <div class="mt-queue-card-actions">
        <div class="mt-queue-card-summary">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Accion principal</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $recepcion->nextActionLabel() }}</p>
        </div>

        <a href="{{ $primaryUrl }}" class="{{ $primaryButtonClass }}">
            <x-icon name="{{ $primaryIcon }}" class="h-4 w-4" />
            {{ $primaryLabel }}
        </a>

        @if ($showDetailAction)
            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', ['recepcionTecnica' => $recepcion, 'return_to' => $returnUrl]) }}" class="btn btn-slate">
                <x-icon name="eye" class="h-4 w-4" />
                Ver detalle
            </a>
        @endif

        <a href="{{ route('mesa-tecnica.recepciones-tecnicas.print', $recepcion) }}" target="_blank" rel="noopener noreferrer" class="mt-queue-card-link">
            <x-icon name="printer" class="h-4 w-4" />
            Imprimir
        </a>
    </div>
</article>
