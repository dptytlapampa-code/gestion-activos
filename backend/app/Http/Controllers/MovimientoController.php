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

        $equipo->refresh()->load('oficina.service.institution');

        $activeInstitutionId = $this->activeInstitutionId($request->user());
        $equipoInstitutionId = (int) ($equipo->oficina?->service?->institution_id ?? 0);

        if (
            ! $this->operatesWithGlobalScope($request->user())
            && $activeInstitutionId !== null
            && $equipoInstitutionId > 0
            && $equipoInstitutionId !== $activeInstitutionId
        ) {
            $institutionName = $equipo->oficina?->service?->institution?->nombre ?? 'la nueva institucion';

            return redirect()
                ->route('equipos.index')
                ->with(
                    'status',
                    sprintf(
                        'Movimiento registrado correctamente. El equipo ahora opera en %s. Cambie la institucion activa para volver a su ficha.',
                        $institutionName
                    )
                );
        }

        return redirect()->route('equipos.show', $equipo)->with('status', 'Movimiento registrado correctamente.');
    }

    private function scopedInstituciones(?User $user)
    {
        return $this->applyGlobalAdministrationScope(
            Institution::query(),
            'id',
            $user
        )
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    private function scopedServicios(?User $user)
    {
        return $this->applyGlobalAdministrationScope(
            Service::query(),
            'institution_id',
            $user
        )
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institution_id']);
    }

    private function scopedOficinas(?User $user)
    {
        return Office::query()
            ->when(
                ($scopeIds = $this->globalAdministrationScopeIds($user)) !== null,
                function ($query) use ($scopeIds): void {
                    $query->whereHas('service', function ($serviceQuery) use ($scopeIds): void {
                        if ($scopeIds === []) {
                            $serviceQuery->whereRaw('1 = 0');

                            return;
                        }

                        $serviceQuery->whereIn('institution_id', $scopeIds);
                    });
                }
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'service_id']);
    }
}
