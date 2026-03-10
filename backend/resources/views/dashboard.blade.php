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

    <div class="space-y-6 rounded-2xl bg-slate-50 p-4 text-slate-800 sm:p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm">
                <div class="rounded-lg bg-indigo-50 p-3">
                    <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 21V6.75A2.25 2.25 0 0 1 7.5 4.5h9a2.25 2.25 0 0 1 2.25 2.25V21M9 9h.008v.008H9V9Zm0 3h.008v.008H9V12Zm0 3h.008v.008H9V15Zm3-6h.008v.008H12V9Zm0 3h.008v.008H12V12Zm0 3h.008v.008H12V15Zm3-6h.008v.008H15V9Zm0 3h.008v.008H15V12Zm0 3h.008v.008H15V15Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-semibold text-slate-800">{{ $instituciones }}</p>
                    <p class="text-sm text-slate-500">Instituciones</p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm">
                <div class="rounded-lg bg-indigo-50 p-3">
                    <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M4.5 3h15m-13.5 0v18m12-18v18M8.25 7.5h.008v.008H8.25V7.5Zm0 3h.008v.008H8.25V10.5Zm0 3h.008v.008H8.25V13.5Zm3-6h.008v.008h-.008V7.5Zm0 3h.008v.008h-.008V10.5Zm0 3h.008v.008h-.008V13.5Zm3-6h.008v.008h-.008V7.5Zm0 3h.008v.008h-.008V10.5Zm0 3h.008v.008h-.008V13.5Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-semibold text-slate-800">{{ $oficinas }}</p>
                    <p class="text-sm text-slate-500">Oficinas</p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm">
                <div class="rounded-lg bg-indigo-50 p-3">
                    <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5v-9a1.5 1.5 0 0 1 1.5-1.5ZM9.75 20.25h4.5M10.5 17.25l-.75 3m4.5-3 .75 3" />
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-semibold text-slate-800">{{ $totalEquipos }}</p>
                    <p class="text-sm text-slate-500">Equipos</p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm">
                <div class="rounded-lg bg-indigo-50 p-3">
                    <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.121 2.121 0 0 0 20.25 18l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.672 1.211-.829a4.5 4.5 0 0 0 5.168-5.168 1.5 1.5 0 0 0-1.316-1.316 4.5 4.5 0 0 0-5.168 5.168c-.157.471-.445.894-.83 1.21l-3.03 2.497m0 0L7.501 17.5 3 21m4.5-4.5-3-3" />
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-semibold text-slate-800">{{ $equiposEnMantenimiento }}</p>
                    <p class="text-sm text-slate-500">Equipos en mantenimiento</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
                <h3 class="text-lg font-semibold text-slate-800">Equipos recientes</h3>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-slate-500">
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
                                        \App\Models\EquipoStatus::CODE_BAJA => 'Baja',
                                        \App\Models\EquipoStatus::CODE_EN_SERVICIO_TECNICO => 'Mantenimiento',
                                        default => $equipo->equipoStatus?->name ?? 'Sin estado',
                                    };
                                    $statusClasses = match ($statusCode) {
                                        \App\Models\EquipoStatus::CODE_OPERATIVA => 'bg-emerald-100 text-emerald-700',
                                        \App\Models\EquipoStatus::CODE_FUERA_DE_SERVICIO => 'bg-amber-100 text-amber-700',
                                        \App\Models\EquipoStatus::CODE_BAJA => 'bg-rose-100 text-rose-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="border-b border-slate-100">
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
                <h3 class="text-lg font-semibold text-slate-800">Actas recientes</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($actas as $acta)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-slate-100 p-3">
                            <div class="min-w-0 text-sm text-slate-700">
                                <p class="truncate font-medium text-slate-800">{{ $acta->codigo }}</p>
                                <p class="text-slate-500">{{ $acta->created_at?->diffForHumans() }}</p>
                                <p class="text-slate-500">{{ $acta->creator?->name ?? 'Usuario' }}</p>
                            </div>
                            <a
                                href="{{ route('actas.show', $acta) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-600"
                            >
                                Ver
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay actas recientes.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
@endsection
