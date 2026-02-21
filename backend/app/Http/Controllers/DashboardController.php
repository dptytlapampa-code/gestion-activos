<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Equipo;
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

        return view('dashboard', compact('totalEquipos', 'equiposPorTipo', 'movimientos', 'actas'));
    }
}
