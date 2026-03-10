@extends('layouts.app')

@section('title', 'Panel')
@section('header', 'Panel general')

@section('content')
    @php
        $user = auth()->user();

        $instituciones = \App\Models\Institution::query()
            ->when(
                ! $user->hasRole(\App\Models\User::ROLE_SUPERADMIN),
                fn ($query) => $query->whereKey($user->institution_id)
            )
            ->count();

        $oficinas = \App\Models\Office::query()
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when(
                ! $user->hasRole(\App\Models\User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('services.institution_id', $user->institution_id)
            )
            ->count('offices.id');

        $equiposEnMantenimiento = $equiposPorEstado[\App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO] ?? 0;

        $equiposRecientes = \App\Models\Equipo::query()
            ->with(['oficina:id,nombre', 'equipoStatus:id,code,name'])
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when(
                ! $user->hasRole(\App\Models\User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('services.institution_id', $user->institution_id)
            )
            ->select('equipos.*')
            ->latest('equipos.created_at')
            ->limit(10)
            ->get();
    @endphp

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <svg class="h-8 w-8 text-primary-theme" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 21V6.75A2.25 2.25 0 0 1 7.5 4.5h9a2.25 2.25 0 0 1 2.25 2.25V21M9 9h.008v.008H9V9Zm0 3h.008v.008H9V12Zm0 3h.008v.008H9V15Zm3-6h.008v.008H12V9Zm0 3h.008v.008H12V12Zm0 3h.008v.008H12V15Zm3-6h.008v.008H15V9Zm0 3h.008v.008H15V12Zm0 3h.008v.008H15V15Z" />
            </svg>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $instituciones }}</p>
                <p class="text-sm text-slate-500">Instituciones</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <svg class="h-8 w-8 text-primary-theme" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21V6.75a2.25 2.25 0 0 1 2.25-2.25h3a2.25 2.25 0 0 1 2.25 2.25V21m-7.5 0h7.5m-7.5 0H4.875A1.125 1.125 0 0 1 3.75 19.875V11.25c0-.621.504-1.125 1.125-1.125h2.25m8.25 10.875h3.75a1.125 1.125 0 0 0 1.125-1.125V11.25a1.125 1.125 0 0 0-1.125-1.125h-2.25M9 9.75h.008v.008H9V9.75Zm0 3h.008v.008H9v-.008Zm0 3h.008v.008H9v-.008Zm3-6h.008v.008H12V9.75Zm0 3h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm3-6h.008v.008H15V9.75Zm0 3h.008v.008H15v-.008Zm0 3h.008v.008H15v-.008Z" />
            </svg>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $oficinas }}</p>
                <p class="text-sm text-slate-500">Oficinas</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <svg class="h-8 w-8 text-primary-theme" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5v-9a1.5 1.5 0 0 1 1.5-1.5ZM9.75 20.25h4.5M10.5 17.25l-.75 3m4.5-3 .75 3" />
            </svg>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $totalEquipos }}</p>
                <p class="text-sm text-slate-500">Equipos</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">
            <svg class="h-8 w-8 text-primary-theme" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 15.17 6.375 6.375a1.875 1.875 0 0 0 2.652-2.652l-6.375-6.375m-6.75 6.75 6.375-6.375m0 0L8.625 3.67a1.875 1.875 0 0 0-2.652 2.652l6.375 6.375m0 0 3.182-3.182m-6.364 6.364 3.182-3.182" />
            </svg>
            <div>
                <p class="text-3xl font-semibold text-slate-800">{{ $equiposEnMantenimiento }}</p>
                <p class="text-sm text-slate-500">Equipos en mantenimiento</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="mb-4 text-lg font-semibold">Equipos recientes</h3>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-2 py-3 font-medium">Tipo</th>
                            <th class="px-2 py-3 font-medium">Serial</th>
                            <th class="px-2 py-3 font-medium">Oficina</th>
                            <th class="px-2 py-3 font-medium">Fecha</th>
                            <th class="px-2 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($equiposRecientes as $equipo)
                            @php
                                $statusCode = $equipo->equipoStatus?->code;
                                $statusLabel = match ($statusCode) {
                                    \App\Models\EquipoStatus::CODE_OPERATIVA => 'Operativo',
                                    \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'Fuera de servicio',
                                    \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO => 'Mantenimiento',
                                    \App\Models\EquipoStatus::CODE_BAJA => 'Baja',
                                    default => $equipo->equipoStatus?->name ?? 'Sin estado',
                                };
                                $statusClasses = match ($statusCode) {
                                    \App\Models\EquipoStatus::CODE_OPERATIVA => 'bg-green-100 text-green-700',
                                    \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO, \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'bg-yellow-100 text-yellow-700',
                                    \App\Models\EquipoStatus::CODE_BAJA => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr class="border-b border-slate-100 transition hover:bg-slate-50">
                                <td class="px-2 py-3 text-slate-700">{{ $equipo->tipo }}</td>
                                <td class="px-2 py-3 text-slate-700">{{ $equipo->numero_serie ?: '-' }}</td>
                                <td class="px-2 py-3 text-slate-700">{{ $equipo->oficina?->nombre ?: '-' }}</td>
                                <td class="px-2 py-3 text-slate-700">{{ optional($equipo->created_at)->format('d/m/Y') }}</td>
                                <td class="px-2 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-2 py-6 text-center text-sm text-slate-500">No hay equipos recientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="rounded-xl bg-white p-6 shadow-sm lg:col-span-1">
            <h3 class="mb-4 text-lg font-semibold">Actas recientes</h3>

            <div class="space-y-3">
                @forelse ($actas as $acta)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 p-4">
                        <div class="min-w-0 text-sm text-slate-700">
                            <p class="truncate font-medium text-slate-800">{{ $acta->codigo }}</p>
                            <p>{{ $acta->created_at?->diffForHumans() }}</p>
                            <p>{{ $acta->creator?->name ?? 'Usuario' }}</p>
                        </div>
                        <a href="{{ route('actas.show', $acta) }}" class="rounded-md bg-primary-soft-theme px-3 py-1 text-sm text-primary-theme transition hover-bg-primary-soft-theme">Ver</a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay actas recientes.</p>
                @endforelse
            </div>
        </aside>
    </div>
@endsection



