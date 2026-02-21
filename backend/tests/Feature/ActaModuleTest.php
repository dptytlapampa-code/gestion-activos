<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Document;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_acta_con_dos_equipos_y_genera_documento_pdf(): void
    {
        Storage::fake();
        [$admin, $inst, $equipos] = $this->crearEscenario();

        $response = $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Ana Perez',
            'receptor_dni' => '12345678',
            'receptor_cargo' => 'Enfermera',
            'receptor_dependencia' => 'Consultorio 1',
            'observaciones' => 'Sin novedades',
            'equipos' => [
                ['equipo_id' => $equipos[0]->id, 'cantidad' => 1, 'accesorios' => 'Cargador'],
                ['equipo_id' => $equipos[1]->id, 'cantidad' => 1, 'accesorios' => 'Cable HDMI'],
            ],
        ]);

        $response->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $this->assertDatabaseHas('actas', [
            'id' => $acta->id,
            'institution_id' => $inst->id,
            'tipo' => Acta::TIPO_ENTREGA,
        ]);

        $this->assertDatabaseCount('acta_equipo', 2);

        $document = Document::query()->where('documentable_type', Acta::class)->first();
        $this->assertNotNull($document);
        $this->assertSame('application/pdf', $document->mime);
        $this->assertStringEndsWith('.pdf', $document->file_path);
        Storage::assertExists($document->file_path);
    }

    public function test_viewer_no_puede_postear_y_admin_ajeno_no_ve_acta(): void
    {
        Storage::fake();
        [$adminA, $instA, $equiposA] = $this->crearEscenario();
        [$adminB] = $this->crearEscenario('B');

        $viewer = User::create([
            'name' => 'Viewer',
            'email' => uniqid().'viewer@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $instA->id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'No autorizado',
            'equipos' => [
                ['equipo_id' => $equiposA[0]->id, 'cantidad' => 1],
            ],
        ])->assertForbidden();

        $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Titular',
            'equipos' => [
                ['equipo_id' => $equiposA[0]->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $this->actingAs($adminB)->get(route('actas.show', $acta))->assertForbidden();
    }

    private function crearEscenario(string $suffix = 'A'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Notebook '.$suffix]);

        $equipos = [
            Equipo::create([
                'tipo' => 'Notebook',
                'tipo_equipo_id' => $tipo->id,
                'marca' => 'Dell',
                'modelo' => 'Latitude',
                'numero_serie' => uniqid('ser-'),
                'bien_patrimonial' => uniqid('bp-'),
                'estado' => Equipo::ESTADO_OPERATIVO,
                'fecha_ingreso' => now()->toDateString(),
                'oficina_id' => $office->id,
            ]),
            Equipo::create([
                'tipo' => 'Monitor',
                'tipo_equipo_id' => $tipo->id,
                'marca' => 'LG',
                'modelo' => '24MP',
                'numero_serie' => uniqid('ser-'),
                'bien_patrimonial' => uniqid('bp-'),
                'estado' => Equipo::ESTADO_OPERATIVO,
                'fecha_ingreso' => now()->toDateString(),
                'oficina_id' => $office->id,
            ]),
        ];

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid().$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $equipos];
    }
}
