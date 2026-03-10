<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\View\View;

class EquipoPublicController extends Controller
{
    public function show(string $uuid): View
    {
        $equipo = Equipo::query()
            ->where('uuid', $uuid)
            ->with([
                'tipoEquipo:id,nombre',
                'oficina:id,nombre,service_id',
                'oficina.service:id,nombre,institution_id',
                'oficina.service.institution:id,nombre',
                'equipoStatus:id,name',
            ])
            ->firstOrFail();

        $ultimaAccion = $equipo->historial()
            ->with('acta:id,tipo,fecha')
            ->latest('fecha')
            ->first();

        $actas = $equipo->actas()
            ->select('actas.id', 'actas.tipo', 'actas.fecha', 'actas.status')
            ->orderByDesc('actas.fecha')
            ->orderByDesc('actas.id')
            ->get();

        return view('equipos.public.show', [
            'equipo' => $equipo,
            'ultimaAccion' => $ultimaAccion,
            'actas' => $actas,
        ]);
    }
}
