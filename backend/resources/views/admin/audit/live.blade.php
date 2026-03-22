@extends('layouts.app')

@section('title', 'Actividad en vivo')
@section('header', 'Actividad en vivo')

@section('content')
    <div class="space-y-5">
        @include('admin.audit.partials.navigation')

        <section class="app-panel p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Eventos recientes mas importantes</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Esta vista muestra solo los eventos que deben quedar visibles de inmediato sin cargar todo el historial.
                    </p>
                </div>

                <a href="{{ route('admin.audit.live') }}" class="btn btn-neutral !px-3 !py-2">Actualizar</a>
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
