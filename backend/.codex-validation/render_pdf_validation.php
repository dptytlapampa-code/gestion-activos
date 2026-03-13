<?php

declare(strict_types=1);

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$acta = new Acta([
    'tipo' => Acta::TIPO_PRESTAMO,
    'fecha' => now()->toDateString(),
    'receptor_nombre' => 'Ing. Carolina Veronica Fernandez Altamirano',
    'receptor_dni' => '27.456.789',
    'receptor_cargo' => 'Coordinadora Operativa Externa de Dispositivos Criticos',
    'receptor_dependencia' => 'Programa Regional de Soporte y Contingencias Hospitalarias',
    'observaciones' => 'Validacion visual de PDF con campos largos para Serie y Patrimonial.',
    'status' => Acta::STATUS_ACTIVA,
    'evento_payload' => [
        'origen_multiple' => false,
        'origenes_por_equipo' => [
            '1001' => [
                'institucion_nombre' => 'Hospital Gobernador Centeno',
                'servicio_nombre' => 'Unidad de Terapia Intensiva Adultos',
                'oficina_nombre' => 'Puesto de Monitoreo Clinico 01',
            ],
            '1002' => [
                'institucion_nombre' => 'Hospital Lucio Molas',
                'servicio_nombre' => 'Area de Informatica Biomedica y Soporte Tecnico',
                'oficina_nombre' => 'Deposito de Equipamiento de Resguardo',
            ],
        ],
    ],
]);
$acta->id = 999;
$acta->setRelation('institution', new Institution(['nombre' => 'Hospital Dr. Lucio Molas']));

$equipos = new Collection([
    tap(new Equipo([
        'tipo' => 'Notebook',
        'marca' => 'Dell',
        'modelo' => 'Latitude 7450 Rugged Clinical Edition',
        'numero_serie' => 'SN-2026-ULTRA-LARGA-ALFA-BRAVO-CHARLIE-DELTA-0000000001',
        'bien_patrimonial' => 'PAT-2026-000000000000000000000000000000000001-A',
        'uuid' => '11111111-1111-1111-1111-111111111111',
    ]), function (Equipo $equipo): void {
        $equipo->id = 1001;
        $equipo->setRelation('pivot', (object) [
            'cantidad' => 1,
            'institucion_origen_nombre' => 'Hospital Gobernador Centeno',
            'servicio_origen_nombre' => 'Unidad de Terapia Intensiva Adultos',
            'oficina_origen_nombre' => 'Puesto de Monitoreo Clinico 01',
        ]);
    }),
    tap(new Equipo([
        'tipo' => 'Monitor multiparametrico',
        'marca' => 'Philips',
        'modelo' => 'IntelliVue MX550 con modulo de expansion neonatal',
        'numero_serie' => 'SN-MX550-2026-SECUNDARIA-LARGA-XYZ-9876543210-EXTRA',
        'bien_patrimonial' => 'BP-HOSP-NEO-2026-000000000000000000000009876543210',
        'uuid' => '22222222-2222-2222-2222-222222222222',
    ]), function (Equipo $equipo): void {
        $equipo->id = 1002;
        $equipo->setRelation('pivot', (object) [
            'cantidad' => 1,
            'institucion_origen_nombre' => 'Hospital Lucio Molas',
            'servicio_origen_nombre' => 'Area de Informatica Biomedica y Soporte Tecnico',
            'oficina_origen_nombre' => 'Deposito de Equipamiento de Resguardo',
        ]);
    }),
]);

$acta->setRelation('equipos', $equipos);

$pdfData = [
    'acta' => $acta,
    'pdfInstitutionName' => 'Hospital Dr. Lucio Molas',
    'pdfHeaderLogoPath' => null,
    'pdfDocumentTitle' => 'ACTA DE PRESTAMO DE EQUIPAMIENTO INFORMATICO',
    'equipoPublicUrl' => null,
    'equipoQrSvg' => null,
    'pdfDestinoInstitucional' => [
        'institucion' => null,
        'servicio' => null,
        'oficina' => null,
        'texto' => '-',
        'has_data' => false,
    ],
    'pdfPrestamoDestinatario' => [
        'is_prestamo' => true,
        'nombre' => 'Ing. Carolina Veronica Fernandez Altamirano',
        'dni' => '27.456.789',
        'cargo' => 'Coordinadora Operativa Externa de Dispositivos Criticos',
        'dependencia' => 'Programa Regional de Soporte y Contingencias Hospitalarias',
        'has_data' => true,
        'summary' => 'Ing. Carolina Veronica Fernandez Altamirano | DNI 27.456.789 | Coordinadora Operativa Externa de Dispositivos Criticos | Programa Regional de Soporte y Contingencias Hospitalarias',
    ],
];

$outputDir = __DIR__.'/storage/app/pdf-validation';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$html = view('actas.pdf.prestamo', $pdfData)->render();
file_put_contents($outputDir.'/acta-prestamo-validacion.html', $html);

$pdfBinary = Pdf::loadView('actas.pdf.prestamo', $pdfData)
    ->setPaper('a4')
    ->output();

$pdfPath = $outputDir.'/acta-prestamo-validacion.pdf';
file_put_contents($pdfPath, $pdfBinary);

echo $pdfPath.PHP_EOL;
