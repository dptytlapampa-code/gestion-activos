@extends('layouts.app')

@section('title', 'Actividad en vivo')
@section('header', 'Actividad en vivo')

@section('content')
    <div class="space-y-4 sm:space-y-5">
        @include('admin.audit.partials.navigation')

        <section class="app-panel p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 max-w-3xl">
                    <h2 class="text-lg font-semibold text-slate-900">Eventos recientes mas importantes</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Esta vista muestra solo los eventos que deben quedar visibles de inmediato sin cargar todo el historial.
                    </p>
                </div>

                <a href="{{ route('admin.audit.live') }}" class="btn btn-neutral w-full sm:w-auto !px-3 !py-2">Actualizar</a>
            </div>

            <div class="mt-5">
                @include('admin.audit.partials.table', ['logs' => $logs])
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </section>
    </div>
@endsection
