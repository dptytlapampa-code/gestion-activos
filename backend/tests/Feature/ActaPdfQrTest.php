<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActaPdfDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActaPdfQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_incluye_qr_principal_del_acta_y_qr_del_equipo_si_hay_un_unico_equipo(): void
    {
        [$user, $institution, $service, $office] = $this->crearEscenarioBase();

        $acta = Acta::query()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'service_origen_id' => $service->id,
            'office_origen_id' => $office->id,
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Persona interna',
            'receptor_dni' => '30111222',
            'created_by' => $user->id,
            'status' => Acta::STATUS_ACTIVA,
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
            'institucion_origen_id' => $institution->id,
            'institucion_origen_nombre' => $institution->nombre,
            'servicio_origen_id' => $service->id,
            'servicio_origen_nombre' => $service->nombre,
            'oficina_origen_id' => $office->id,
            'oficina_origen_nombre' => $office->nombre,
        ]);

        $acta->load([
            'institution',
            'creator',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
        ]);

        $pdfData = app(ActaPdfDataService::class)->build($acta);
        $html = view('actas.pdf.prestamo', array_merge(['acta' => $acta], $pdfData))->render();

        $this->assertSame(route('actas.public.show', ['uuid' => $acta->uuid]), $pdfData['actaPublicUrl']);
        $this->assertNotNull($pdfData['actaQrImageDataUri']);
        $this->assertStringStartsWith('data:image/png;base64,', $pdfData['actaQrImageDataUri']);
        $this->assertSame(route('equipos.public.show', ['uuid' => $equipo->uuid]), $pdfData['equipoPublicUrl']);
        $this->assertNotNull($pdfData['equipoQrImageDataUri']);
        $this->assertStringStartsWith('data:image/png;base64,', $pdfData['equipoQrImageDataUri']);
        $this->assertCount(2, $pdfData['pdfQrCards']);
        $this->assertStringContainsString('Acta patrimonial', $html);
        $this->assertStringContainsString('Ficha publica del equipo', $html);
        $this->assertStringContainsString($pdfData['actaPublicUrl'], $html);
        $this->assertStringContainsString($pdfData['equipoPublicUrl'], $html);
        $this->assertStringContainsString('<img src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }

    public function test_pdf_con_varios_equipos_mantiene_solo_el_qr_principal_del_acta(): void
    {
        [$user, $institution, $service, $office] = $this->crearEscenarioBase();

        $acta = Acta::query()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'service_origen_id' => $service->id,
            'office_origen_id' => $office->id,
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Responsable interno',
            'created_by' => $user->id,
            'status' => Acta::STATUS_ACTIVA,
            'evento_payload' => [
                'institution_id' => $institution->id,
                'institution_name' => $institution->nombre,
                'origen_multiple' => false,
                'instituciones_origen_ids' => [$institution->id],
                'origenes_por_equipo' => [],
            ],
        ]);

        $equipoA = $this->crearEquipo($office, 'A');
        $equipoB = $this->crearEquipo($office, 'B');

        foreach ([$equipoA, $equipoB] as $equipo) {
            $acta->equipos()->attach($equipo->id, [
                'cantidad' => 1,
                'institucion_origen_id' => $institution->id,
                'institucion_origen_nombre' => $institution->nombre,
                'servicio_origen_id' => $service->id,
                'servicio_origen_nombre' => $service->nombre,
                'oficina_origen_id' => $office->id,
                'oficina_origen_nombre' => $office->nombre,
            ]);
        }

        $acta->load([
            'institution',
            'creator',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
        ]);

        $pdfData = app(ActaPdfDataService::class)->build($acta);
        $html = view('actas.pdf.entrega', array_merge(['acta' => $acta], $pdfData))->render();

        $this->assertSame(route('actas.public.show', ['uuid' => $acta->uuid]), $pdfData['actaPublicUrl']);
        $this->assertNotNull($pdfData['actaQrImageDataUri']);
        $this->assertStringStartsWith('data:image/png;base64,', $pdfData['actaQrImageDataUri']);
        $this->assertNull($pdfData['equipoPublicUrl']);
        $this->assertNull($pdfData['equipoQrImageDataUri']);
        $this->assertCount(1, $pdfData['pdfQrCards']);
        $this->assertStringContainsString($equipoA->codigo_interno, $html);
        $this->assertStringContainsString($equipoB->codigo_interno, $html);
        $this->assertStringNotContainsString('Ficha publica del equipo', $html);
        $this->assertStringContainsString('<img src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }

    private function crearEscenarioBase(): array
    {
        $institution = Institution::create(['nombre' => 'Hospital Central']);
        $service = Service::create(['nombre' => 'Servicio Central', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Central', 'service_id' => $service->id]);

        $user = User::create([
            'name' => 'Admin',
            'email' => uniqid('admin_', true).'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$user, $institution, $service, $office];
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
