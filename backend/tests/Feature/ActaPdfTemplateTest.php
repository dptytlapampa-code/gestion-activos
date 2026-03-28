<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActaPdfDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActaPdfTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_template_prioriza_logo_pdf_muestra_nombre_del_sistema_y_oculta_columna_cantidad(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('logos/institucional.png', 'institucional');
        Storage::disk('public')->put('logos/pdf.png', 'pdf');

        SystemSetting::query()->create([
            'site_name' => 'Sistema Provincial de Gestion de Activos y Trazabilidad',
            'primary_color' => '#1F2937',
            'sidebar_color' => '#1F2937',
            'logo_path' => 'logos/institucional.png',
            'logo_institucional' => 'logos/institucional.png',
            'logo_pdf' => 'logos/pdf.png',
        ]);

        Cache::forget(system_config_cache_key());

        [$user, $institution, $service, $office] = $this->crearEscenarioBase();
        [$destInstitution, $destService, $destOffice] = $this->crearDestino();

        $acta = Acta::query()->create([
            'institution_id' => $institution->id,
            'institution_destino_id' => $destInstitution->id,
            'service_origen_id' => $service->id,
            'office_origen_id' => $office->id,
            'service_destino_id' => $destService->id,
            'office_destino_id' => $destOffice->id,
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Ana Perez',
            'receptor_dni' => '12345678',
            'receptor_cargo' => 'Enfermeria',
            'receptor_dependencia' => 'Hospital Central',
            'observaciones' => 'Entrega operativa',
            'status' => Acta::STATUS_ACTIVA,
            'created_by' => $user->id,
            'evento_payload' => [
                'institution_id' => $institution->id,
                'institution_name' => $institution->nombre,
                'origen_multiple' => false,
                'instituciones_origen_ids' => [$institution->id],
                'origenes_por_equipo' => [],
            ],
        ]);

        $equipo = $this->crearEquipo($office, 'A');
        $acta->equipos()->attach($equipo->id, [
            'cantidad' => 1,
            'accesorios' => 'Fuente y bolso',
            'institucion_origen_id' => $institution->id,
            'institucion_origen_nombre' => $institution->nombre,
            'servicio_origen_id' => $service->id,
            'servicio_origen_nombre' => $service->nombre,
            'oficina_origen_id' => $office->id,
            'oficina_origen_nombre' => $office->nombre,
        ]);

        $acta->load([
            'institution',
            'institucionDestino',
            'servicioDestino',
            'oficinaDestino',
            'creator',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
        ]);

        $pdfData = app(ActaPdfDataService::class)->build($acta);

        $this->assertSame(
            Storage::disk('public')->path('logos/pdf.png'),
            $pdfData['pdfHeaderLogoPath']
        );
        $this->assertSame(
            Storage::disk('public')->path('logos/pdf.png'),
            $pdfData['pdfHeaderMastheadPath']
        );

        $html = view('actas.pdf.entrega', array_merge(['acta' => $acta], $pdfData))->render();

        $this->assertStringContainsString('Sistema Provincial de Gestion de Activos y Trazabilidad', $html);
        $this->assertStringContainsString('Hospital Origen', $html);
        $this->assertStringContainsString('Destino administrativo', $html);
        $this->assertStringContainsString('class="masthead-image"', $html);
        $this->assertStringContainsString('alt="Membrete institucional"', $html);
        $this->assertStringNotContainsString('header-logo-cell', $html);
        $this->assertStringNotContainsString('<div class="issuer-name">Hospital Origen</div>', $html);
        $this->assertStringContainsString(
            '<span class="footer-line">Documento generado por Sistema Provincial de Gestion de Activos y Trazabilidad</span>',
            $html
        );
        $this->assertStringNotContainsString('<span class="footer-line">Hospital Origen</span>', $html);
        $this->assertStringNotContainsString('Cant.', $html);
        $this->assertStringNotContainsString('Origen individual', $html);
    }

    public function test_pdf_template_muestra_origen_individual_solo_si_hay_multiples_ubicaciones(): void
    {
        Storage::fake('public');

        SystemSetting::query()->create([
            'site_name' => 'Sistema Provincial de Activos',
            'primary_color' => '#1F2937',
            'sidebar_color' => '#1F2937',
        ]);

        Cache::forget(system_config_cache_key());

        [$user, $institutionA, $serviceA, $officeA] = $this->crearEscenarioBase('A');
        [$institutionB, $serviceB, $officeB] = $this->crearDestino('B');

        $acta = Acta::query()->create([
            'institution_id' => $institutionA->id,
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Carlos Gomez',
            'receptor_dni' => '22333444',
            'receptor_cargo' => 'Chofer',
            'receptor_dependencia' => 'Logistica',
            'status' => Acta::STATUS_ACTIVA,
            'created_by' => $user->id,
            'evento_payload' => [
                'institution_id' => $institutionA->id,
                'institution_name' => $institutionA->nombre,
                'origen_multiple' => true,
                'instituciones_origen_ids' => [$institutionA->id, $institutionB->id],
                'origenes_por_equipo' => [],
            ],
        ]);

        $equipoA = $this->crearEquipo($officeA, 'A');
        $equipoB = $this->crearEquipo($officeB, 'B');

        $acta->equipos()->attach($equipoA->id, [
            'cantidad' => 1,
            'accesorios' => 'Maletin',
            'institucion_origen_id' => $institutionA->id,
            'institucion_origen_nombre' => $institutionA->nombre,
            'servicio_origen_id' => $serviceA->id,
            'servicio_origen_nombre' => $serviceA->nombre,
            'oficina_origen_id' => $officeA->id,
            'oficina_origen_nombre' => $officeA->nombre,
        ]);

        $acta->equipos()->attach($equipoB->id, [
            'cantidad' => 1,
            'accesorios' => 'Cargador',
            'institucion_origen_id' => $institutionB->id,
            'institucion_origen_nombre' => $institutionB->nombre,
            'servicio_origen_id' => $serviceB->id,
            'servicio_origen_nombre' => $serviceB->nombre,
            'oficina_origen_id' => $officeB->id,
            'oficina_origen_nombre' => $officeB->nombre,
        ]);

        $acta->load([
            'institution',
            'creator',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
        ]);

        $payload = $acta->evento_payload;
        $payload['origenes_por_equipo'] = [
            (string) $equipoA->id => [
                'institucion_nombre' => $institutionA->nombre,
                'servicio_nombre' => $serviceA->nombre,
                'oficina_nombre' => $officeA->nombre,
            ],
            (string) $equipoB->id => [
                'institucion_nombre' => $institutionB->nombre,
                'servicio_nombre' => $serviceB->nombre,
                'oficina_nombre' => $officeB->nombre,
            ],
        ];
        $acta->forceFill(['evento_payload' => $payload]);

        $html = view(
            'actas.pdf.prestamo',
            array_merge(['acta' => $acta], app(ActaPdfDataService::class)->build($acta))
        )->render();

        $this->assertStringContainsString('Origen individual', $html);
        $this->assertStringContainsString('Hospital A / Servicio A / Oficina A', $html);
        $this->assertStringContainsString('Hospital B / Servicio B / Oficina B', $html);
        $this->assertStringNotContainsString('<span class="footer-line">Hospital A</span>', $html);
        $this->assertStringNotContainsString('<span class="footer-line">Hospital B</span>', $html);
        $this->assertStringNotContainsString('Cant.', $html);
    }

    private function crearEscenarioBase(string $suffix = 'Origen'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);

        $user = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('admin_'.$suffix, true).'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$user, $institution, $service, $office];
    }

    private function crearDestino(string $suffix = 'Destino'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);

        return [$institution, $service, $office];
    }

    private function crearEquipo(Office $office, string $suffix): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude '.$suffix,
            'numero_serie' => 'SERIE-'.$suffix,
            'bien_patrimonial' => 'BP-'.$suffix,
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
