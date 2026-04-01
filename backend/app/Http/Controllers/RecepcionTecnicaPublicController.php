<?php

namespace App\Http\Controllers;

use App\Models\RecepcionTecnica;
use App\Services\RecepcionTecnicaService;
use Illuminate\View\View;

class RecepcionTecnicaPublicController extends Controller
{
    public function __construct(private readonly RecepcionTecnicaService $recepcionTecnicaService) {}

    public function show(string $uuid): View
    {
        $recepcionTecnica = RecepcionTecnica::query()
            ->where('uuid', $uuid)
            ->with([
                'institution:id,nombre',
                'equipo:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
                'equipoCreado:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
            ])
            ->firstOrFail();

        return view(
            'mesa-tecnica.recepciones-tecnicas.public.show',
            $this->recepcionTecnicaService->publicData($recepcionTecnica)
        );
    }
}
