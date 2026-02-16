<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MovimientoController extends Controller
{
    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $validated = $request->validate([
            'tipo_movimiento' => ['required', 'string', 'in:mantenimiento,prestamo,baja'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $oficinaActual = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);
        $ubicacionActual = $this->mapOfficeLocation($oficinaActual);

        Movimiento::query()->create([
            'equipo_id' => $equipo->id,
            'user_id' => auth()->id(),
            'tipo_movimiento' => $validated['tipo_movimiento'],
            'fecha' => now(),
            'institucion_origen_id' => $ubicacionActual['institucion_id'],
            'servicio_origen_id' => $ubicacionActual['servicio_id'],
            'oficina_origen_id' => $ubicacionActual['oficina_id'],
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
