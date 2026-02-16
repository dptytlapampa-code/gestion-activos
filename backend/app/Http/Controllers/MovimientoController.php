<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovimientoController extends Controller
{
    private const TIPOS_MANUALES = [
        'mantenimiento',
        'préstamo',
        'baja',
    ];

    public function create(Equipo $equipo): View
    {
        $this->authorize('update', $equipo);

        return view('movimientos.create', [
            'equipo' => $equipo->load('oficina.service.institution'),
            'tipos_movimiento' => self::TIPOS_MANUALES,
        ]);
    }

    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $validated = $request->validate([
            'tipo_movimiento' => ['required', 'string', 'in:'.implode(',', self::TIPOS_MANUALES)],
            'observacion' => ['nullable', 'string'],
        ]);

        $ubicacionActual = $this->mapOfficeLocation($equipo->oficina()->with('service.institution')->first());

        Movimiento::query()->create([
            'equipo_id' => $equipo->id,
            'usuario_id' => $request->user()?->id,
            'tipo_movimiento' => $validated['tipo_movimiento'],
            'fecha' => now(),
            'institucion_origen_id' => $ubicacionActual['institucion_id'],
            'servicio_origen_id' => $ubicacionActual['servicio_id'],
            'oficina_origen_id' => $ubicacionActual['oficina_id'],
            'institucion_destino_id' => $ubicacionActual['institucion_id'],
            'servicio_destino_id' => $ubicacionActual['servicio_id'],
            'oficina_destino_id' => $ubicacionActual['oficina_id'],
            'observacion' => $validated['observacion'] ?? null,
        ]);

        return redirect()->route('equipos.show', $equipo)->with('status', 'Movimiento registrado correctamente.');
    }

    /**
     * @return array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}
     */
    private function mapOfficeLocation(?Office $office): array
    {
        return [
            'institucion_id' => $office?->service?->institution?->id,
            'servicio_id' => $office?->service?->id,
            'oficina_id' => $office?->id,
        ];
    }
}
