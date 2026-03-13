<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimientoRequest;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use App\Services\MovimientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovimientoController extends Controller
{
    public function __construct(private readonly MovimientoService $movimientoService) {}

    public function create(Request $request): View
    {
        $equipoId = $request->integer('equipo_id');
        abort_if($equipoId <= 0, 404);

        $equipo = Equipo::query()->with('oficina.service.institution')->findOrFail($equipoId);
        $this->authorize('update', $equipo);

        $user = $request->user();
        $currentInstitutionId = (int) $equipo->oficina?->service?->institution_id;

        return view('movimientos.create', [
            'equipo' => $equipo,
            'tipos_movimiento' => Movimiento::TIPOS,
            'instituciones' => $this->scopedInstituciones($user),
            'servicios' => $this->scopedServicios($user),
            'oficinas' => $this->scopedOficinas($user),
            'current_institution_id' => $currentInstitutionId,
        ]);
    }

    public function store(StoreMovimientoRequest $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $this->movimientoService->registrar($equipo, $request->user(), $request->validated());

        return redirect()->route('equipos.show', $equipo)->with('status', 'Movimiento registrado correctamente.');
    }

    private function scopedInstituciones(?User $user)
    {
        return Institution::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), fn ($query) => $query->where('id', $user->institution_id))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    private function scopedServicios(?User $user)
    {
        return Service::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), fn ($query) => $query->where('institution_id', $user->institution_id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institution_id']);
    }

    private function scopedOficinas(?User $user)
    {
        return Office::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), fn ($query) => $query->whereHas('service', fn ($q) => $q->where('institution_id', $user->institution_id)))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'service_id']);
    }
}
