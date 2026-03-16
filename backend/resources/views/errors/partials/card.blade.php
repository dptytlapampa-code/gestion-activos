@php
    $statusCode = $status ?? ($error['status'] ?? 500);
    $titleText = $title ?? ($error['title'] ?? 'Algo salio mal');
    $messageText = $message ?? ($error['message'] ?? 'El sistema no pudo completar la operacion.');
    $reasonText = $reason ?? ($error['reason'] ?? 'Puede deberse a un problema temporal o una condicion no esperada.');
    $nextStepsText = $nextSteps ?? ($error['next_steps'] ?? 'Intente nuevamente en unos minutos.');
@endphp

<div class="card w-full p-8">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Error {{ $statusCode }}</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $titleText }}</h1>
        </div>
        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold uppercase text-red-700">Atencion</span>
    </div>

    <div class="space-y-5 text-sm leading-6 text-slate-700">
        <section>
            <h2 class="text-base font-semibold text-slate-900">Que paso</h2>
            <p>{{ $messageText }}</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-slate-900">Por que pudo pasar</h2>
            <p>{{ $reasonText }}</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-slate-900">Que puede hacer ahora</h2>
            <p>{{ $nextStepsText }}</p>
        </section>
    </div>

    <div class="mt-8 flex flex-wrap items-center gap-3">
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Volver al panel</a>
        <a href="{{ url()->previous() }}" class="btn btn-neutral">Intentar de nuevo</a>
    </div>
</div>
