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

    public function test_admin_puede_crear_acta_entrega_y_actualiza_equipo_historial_y_pdf(): void
    {
        Storage::fake();

        [$admin, $instA, , $officeA] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $response = $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $instB->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'receptor_nombre' => 'Ana Perez',
            'receptor_dni' => '12345678',
            'receptor_cargo' => 'Enfermeria',
            'observaciones' => 'Entrega operativa',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1, 'accesorios' => 'Fuente'],
            ],
        ]);

        $response->assertRedirect();

        $acta = Acta::query()->firstOrFail();
        $equipo->refresh();

        $this->assertSame($officeB->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);

        $this->assertDatabaseHas('equipo_historial', [
            'equipo_id' => $equipo->id,
            'acta_id' => $acta->id,
            'tipo_evento' => Acta::TIPO_ENTREGA,
            'estado_nuevo' => Equipo::ESTADO_OPERATIVO,
            'institucion_anterior' => $instA->id,
            'institucion_nueva' => $instB->id,
        ]);

        $document = Document::query()->where('documentable_type', Acta::class)->first();
        $this->assertNotNull($document);
        Storage::assertExists($document->file_path);
    }

    public function test_acta_prestamo_actualiza_estado_a_prestado_sin_mover_ubicacion(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Carlos Gomez',
            'receptor_dni' => '22333444',
            'receptor_cargo' => 'Chofer',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_PRESTADO, $equipo->estado);
        $this->assertDatabaseHas('equipo_historial', [
            'equipo_id' => $equipo->id,
            'tipo_evento' => Acta::TIPO_PRESTAMO,
            'estado_nuevo' => Equipo::ESTADO_PRESTADO,
            'oficina_anterior' => $officeA->id,
            'oficina_nueva' => $officeA->id,
        ]);
    }

    public function test_acta_traslado_actualiza_ubicacion_dentro_de_la_misma_institucion(): void
    {
        Storage::fake();

        [$admin, $inst, , $officeA, $serviceB, $officeB] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_TRASLADO,
            'fecha' => now()->toDateString(),
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'observaciones' => 'Traslado interno',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame($officeB->id, $equipo->oficina_id);
        $this->assertDatabaseHas('equipo_historial', [
            'equipo_id' => $equipo->id,
            'tipo_evento' => Acta::TIPO_TRASLADO,
            'institucion_anterior' => $inst->id,
            'institucion_nueva' => $inst->id,
            'oficina_anterior' => $officeA->id,
            'oficina_nueva' => $officeB->id,
        ]);
    }

    public function test_traslado_no_permite_enviar_institucion_destino(): void
    {
        Storage::fake();

        [$admin, $instA, , $officeA, $serviceB, $officeB] = $this->crearEscenarioBase('A');
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_TRASLADO,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $instA->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('institution_destino_id');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_traslado_entre_instituciones_genera_error(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase('A');
        [, , , , $serviceB, $officeB] = $this->crearEscenarioBase('B');

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_TRASLADO,
            'fecha' => now()->toDateString(),
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('service_destino_id');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_acta_baja_actualiza_estado(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_BAJA,
            'fecha' => now()->toDateString(),
            'motivo_baja' => 'Obsolescencia',
            'observaciones' => 'Sin reparacion viable',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame(Equipo::ESTADO_BAJA, $equipo->estado);
    }

    public function test_acta_mantenimiento_actualiza_estado_sin_mover_ubicacion(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_MANTENIMIENTO,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Ingreso al taller',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_EN_MANTENIMIENTO, $equipo->estado);
    }

    public function test_acta_devolucion_vuelve_estado_operativo(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_PRESTADO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_DEVOLUCION,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retorna a inventario',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
        $this->assertSame($officeA->id, $equipo->oficina_id);
    }

    public function test_admin_no_puede_generar_acta_con_equipo_de_otra_institucion(): void
    {
        Storage::fake();

        [$adminA, , , $officeA] = $this->crearEscenarioBase('A');
        [, , , $officeB] = $this->crearEscenarioBase('B');

        $equipoA = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);
        $equipoB = $this->crearEquipo($officeB, Equipo::ESTADO_OPERATIVO, 'B');

        $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $officeA->service->institution_id,
            'service_destino_id' => $officeA->service_id,
            'office_destino_id' => $officeA->id,
            'receptor_nombre' => 'Prueba',
            'receptor_dni' => '99888777',
            'equipos' => [
                ['equipo_id' => $equipoA->id, 'cantidad' => 1],
                ['equipo_id' => $equipoB->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('equipos');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_viewer_no_puede_crear_actas(): void
    {
        Storage::fake();

        [, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $viewer = User::create([
            'name' => 'Viewer',
            'email' => uniqid('viewer_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $officeA->service->institution_id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $officeA->service->institution_id,
            'service_destino_id' => $officeA->service_id,
            'office_destino_id' => $officeA->id,
            'receptor_nombre' => 'No permitido',
            'receptor_dni' => '1111',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertForbidden();
    }

    private function crearEscenarioBase(string $suffix = 'A'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $serviceA = Service::create(['nombre' => 'Servicio '.$suffix.'-1', 'institution_id' => $institution->id]);
        $officeA = Office::create(['nombre' => 'Oficina '.$suffix.'-1', 'service_id' => $serviceA->id]);
        $serviceB = Service::create(['nombre' => 'Servicio '.$suffix.'-2', 'institution_id' => $institution->id]);
        $officeB = Office::create(['nombre' => 'Oficina '.$suffix.'-2', 'service_id' => $serviceB->id]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('admin_').$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $serviceA, $officeA, $serviceB, $officeB];
    }

    private function crearEquipo(Office $office, string $estado, string $suffix = 'A'): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => uniqid('ser-'.$suffix.'-'),
            'bien_patrimonial' => uniqid('bp-'.$suffix.'-'),
            'estado' => $estado,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
