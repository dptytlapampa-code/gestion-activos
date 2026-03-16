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
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const RECENT_EQUIPOS_LIMIT = 5;

    private const RECENT_ACTAS_LIMIT = 5;

    public function __invoke(Request $request): View
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
            EquipoStatus::CODE_PRESTADO,
            EquipoStatus::CODE_PRESTADA,
            EquipoStatus::CODE_EN_SERVICIO_TECNICO,
            EquipoStatus::CODE_FUERA_DE_SERVICIO,
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

        $instituciones = \App\Models\Institution::query()
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->whereKey($user->institution_id)
            )
            ->count();

        $oficinas = \App\Models\Office::query()
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->where('services.institution_id', $user->institution_id)
            )
            ->count('offices.id');

        $equiposRecientes = Equipo::query()
            ->with(['oficina:id,nombre', 'equipoStatus:id,code,name', 'tipoEquipo:id,nombre,image_path'])
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->where('services.institution_id', $user->institution_id)
            )
            ->select('equipos.*')
            ->latest('equipos.created_at')
            ->limit(self::RECENT_EQUIPOS_LIMIT)
            ->get();

        $actas = Acta::query()
            ->withCount('equipos')
            ->with(['creator:id,name'])
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->where('institution_id', $user->institution_id)
            )
            ->latest('fecha')
            ->limit(self::RECENT_ACTAS_LIMIT)
            ->get();

        return view('dashboard', compact(
            'actas',
            'equiposPorEstado',
            'equiposPorTipo',
            'equiposRecientes',
            'instituciones',
            'movimientos',
            'oficinas',
            'totalEquipos',
            'ultimosServicioTecnico',
        ));
    }
}
