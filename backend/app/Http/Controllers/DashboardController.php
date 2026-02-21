<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Mantenimiento;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $equipoScope = Equipo::query()->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id');

        if (! $user->hasRole(User::ROLE_SUPERADMIN)) {
            $equipoScope->where('services.institution_id', $user->institution_id);
        }

        $totalEquipos = (clone $equipoScope)->count('equipos.id');

        $equiposPorTipo = (clone $equipoScope)
            ->selectRaw('equipos.tipo, count(*) as total')
            ->groupBy('equipos.tipo')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $estadoCodes = [
            EquipoStatus::CODE_OPERATIVA,
            EquipoStatus::CODE_EN_SERVICIO_TECNICO,
            EquipoStatus::CODE_BAJA,
        ];

        $equiposPorEstado = Equipo::query()
            ->join('equipo_statuses', 'equipo_statuses.id', '=', 'equipos.equipo_status_id')
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->whereIn('equipo_statuses.code', $estadoCodes)
            ->when(! $user->hasRole(User::ROLE_SUPERADMIN), fn (Builder $query) => $query->where('services.institution_id', $user->institution_id))
            ->selectRaw('equipo_statuses.code, equipo_statuses.name, count(*) as total')
            ->groupBy('equipo_statuses.code', 'equipo_statuses.name')
            ->pluck('total', 'code');

        $ultimosServicioTecnico = Mantenimiento::query()
            ->with(['equipo:id,tipo,numero_serie'])
            ->where('tipo', Mantenimiento::TIPO_EXTERNO)
            ->whereNull('fecha_egreso_st')
            ->when(! $user->hasRole(User::ROLE_SUPERADMIN), fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->latest('fecha')
            ->limit(10)
            ->get();

        $movimientos = Movimiento::query()
            ->with(['equipo:id,tipo,numero_serie'])
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->whereHas('equipo.oficina.service', fn (Builder $q) => $q->where('institution_id', $user->institution_id))
            )
            ->latest('fecha')
            ->limit(10)
            ->get();

        $actas = Acta::query()
            ->withCount('equipos')
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->where('institution_id', $user->institution_id)
            )
            ->latest('fecha')
            ->limit(10)
            ->get();

        return view('dashboard', compact('totalEquipos', 'equiposPorTipo', 'equiposPorEstado', 'ultimosServicioTecnico', 'movimientos', 'actas'));
    }
}
