@if (session('status'))
    <div class="app-alert app-alert-success" role="status" aria-live="polite">
        <span class="app-alert-icon" aria-hidden="true">OK</span>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('warning'))
    <div class="app-alert app-alert-warning" role="alert" aria-live="assertive">
        <span class="app-alert-icon" aria-hidden="true">!</span>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="app-alert app-alert-error" role="alert" aria-live="assertive">
        <span class="app-alert-icon" aria-hidden="true">!</span>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any() && ! session('error'))
    <div class="app-alert app-alert-error" role="alert" aria-live="assertive">
        <span class="app-alert-icon" aria-hidden="true">!</span>
        <div>
            <p class="font-semibold">Revise los campos marcados para continuar.</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach (array_slice($errors->all(), 0, 3) as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
