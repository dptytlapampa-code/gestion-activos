<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Services\ActaPdfDataService;
use Illuminate\View\View;

class ActaPublicController extends Controller
{
    public function __construct(
        private readonly ActaPdfDataService $actaPdfDataService,
    ) {}

    public function show(string $uuid): View
    {
        $acta = Acta::query()
            ->where('uuid', $uuid)
            ->with([
                'institution:id,nombre',
                'institucionDestino:id,nombre',
                'servicioDestino:id,nombre',
                'oficinaDestino:id,nombre',
                'equipos.tipoEquipo:id,nombre',
                'equipos.oficina.service.institution',
            ])
            ->firstOrFail();

        $pdfData = $this->actaPdfDataService->build($acta);

        return view('actas.public.show', [
            'acta' => $acta,
            'tipoLabel' => Acta::LABELS[$acta->tipo] ?? strtoupper((string) $acta->tipo),
            'originSummary' => $pdfData['pdfOriginSummary'],
            'destinoInstitucional' => $pdfData['pdfDestinoInstitucional'],
            'equipmentTable' => $pdfData['pdfEquipmentTable'],
            'equipoPublicUrl' => $pdfData['equipoPublicUrl'],
        ]);
    }
}
