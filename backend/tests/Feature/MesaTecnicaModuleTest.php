<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MesaTecnicaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tecnico_puede_acceder_a_mesa_tecnica_y_viewer_no(): void
    {
        [, $institution] = $this->crearEscenarioBase();

        $tecnico = User::create([
            'name' => 'Tecnico Mesa',
            'email' => uniqid('mesa_tecnico_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_TECNICO,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $viewer = User::create([
            'name' => 'Viewer Mesa',
            'email' => uniqid('mesa_viewer_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $this->actingAs($tecnico)
            ->get(route('mesa-tecnica.index'))
            ->assertOk()
            ->assertSee('Mesa tecnica')
            ->assertSee('Recibir')
            ->assertSee('Ingreso tecnico');

        $this->actingAs($tecnico)
            ->get(route('mesa-tecnica.index'))
            ->assertSee('data-desktop-sidebar-lock="collapsed"', false);

        $this->actingAs($tecnico)
            ->get(route('dashboard'))
            ->assertDontSee('data-desktop-sidebar-lock="collapsed"', false);

        $this->actingAs($viewer)
            ->get(route('mesa-tecnica.index'))
            ->assertForbidden();
    }

    public function test_mesa_tecnica_recepciona_equipo_prestado_generando_acta_de_devolucion(): void
    {
        Storage::fake();

        [$admin, , , $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office, Equipo::ESTADO_PRESTADO, 'REC');

        $this->actingAs($admin)
            ->post(route('mesa-tecnica.recepciones.store'), [
                'mesa_modal' => 'recepcion',
                'fecha' => now()->toDateString(),
                'equipo_id' => $equipo->id,
                'motivo' => 'Devolucion operativa',
                'observaciones' => 'Regresa a inventario tecnico',
            ])
            ->assertRedirect(route('mesa-tecnica.index'))
            ->assertSessionHas('status');

        $equipo->refresh();
        $acta = Acta::query()->firstOrFail();

        $this->assertSame(Acta::TIPO_DEVOLUCION, $acta->tipo);
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
        $this->assertStringContainsString('Motivo de recepcion: Devolucion operativa', (string) $acta->observaciones);

        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'acta_id' => $acta->id,
            'tipo_movimiento' => Movimiento::TIPO_DEVOLUCION,
        ]);
    }

    public function test_mesa_tecnica_rechaza_recepcion_de_equipo_no_prestado(): void
    {
        Storage::fake();

        [$admin, , , $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office, Equipo::ESTADO_OPERATIVO, 'RECERR');

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.index'))
            ->post(route('mesa-tecnica.recepciones.store'), [
                'mesa_modal' => 'recepcion',
                'fecha' => now()->toDateString(),
                'equipo_id' => $equipo->id,
            ])
            ->assertRedirect(route('mesa-tecnica.index'))
            ->assertSessionHasErrors('equipo_id');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_mesa_tecnica_entrega_equipo_reutilizando_acta_y_movimiento_existentes(): void
    {
        Storage::fake();

        [$admin, $instA, , $officeA] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');

        $admin->permittedInstitutions()->sync([$instB->id]);
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO, 'ENT');

        $this->actingAs($admin)
            ->post(route('mesa-tecnica.entregas.store'), [
                'mesa_modal' => 'entrega',
                'fecha' => now()->toDateString(),
                'equipo_id' => $equipo->id,
                'institution_destino_id' => $instB->id,
                'service_destino_id' => $serviceB->id,
                'office_destino_id' => $officeB->id,
                'receptor_nombre' => 'Mesa Tecnica Destino',
                'receptor_dni' => '12345678',
                'receptor_cargo' => 'Tecnica',
                'receptor_dependencia' => 'Ingenieria Clinica',
                'observaciones' => 'Entrega desde mesa tecnica',
            ])
            ->assertRedirect(route('mesa-tecnica.index'))
            ->assertSessionHas('status');

        $equipo->refresh();
        $acta = Acta::query()->firstOrFail();

        $this->assertSame($officeB->id, $equipo->oficina_id);
        $this->assertSame(Acta::TIPO_ENTREGA, $acta->tipo);

        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'acta_id' => $acta->id,
            'tipo_movimiento' => Movimiento::TIPO_TRASLADO,
            'institucion_origen_id' => $instA->id,
            'institucion_destino_id' => $instB->id,
            'oficina_destino_id' => $officeB->id,
        ]);
    }

    public function test_mesa_tecnica_muestra_etiqueta_imprimible(): void
    {
        [$admin, , , $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office, Equipo::ESTADO_OPERATIVO, 'LAB');

        $this->actingAs($admin)
            ->get(route('mesa-tecnica.label', $equipo))
            ->assertOk()
            ->assertSee($equipo->codigo_interno)
            ->assertSee('Uso institucional')
            ->assertSee('<svg', false);
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
            'email' => uniqid('mesa_admin_').$suffix.'@test.com',
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
