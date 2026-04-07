<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\RecepcionTecnica;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Recibir para reparacion')
            ->assertSee('Ingreso tecnico temporal')
            ->assertSee('Actas y movimientos');

        $this->actingAs($viewer)
            ->get(route('mesa-tecnica.index'))
            ->assertForbidden();
    }

    public function test_dashboard_muestra_tickets_recientes_y_aclara_separacion_patrimonial(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office, 'DASH');

        RecepcionTecnica::query()->create([
            'institution_id' => $institution->id,
            'created_by' => $admin->id,
            'recibido_por' => $admin->id,
            'equipo_id' => $equipo->id,
            'fecha_recepcion' => now()->toDateString(),
            'ingresado_at' => now(),
            'estado' => RecepcionTecnica::ESTADO_EN_REPARACION,
            'status_changed_at' => now(),
            'sector_receptor' => 'Mesa Tecnica / Nivel Central',
            'referencia_equipo' => $equipo->reference(),
            'tipo_equipo_texto' => $equipo->tipo,
            'marca' => $equipo->marca,
            'modelo' => $equipo->modelo,
            'numero_serie' => $equipo->numero_serie,
            'bien_patrimonial' => $equipo->bien_patrimonial,
            'codigo_interno_equipo' => $equipo->codigo_interno,
            'procedencia_institution_id' => $institution->id,
            'procedencia_service_id' => $service->id,
            'procedencia_office_id' => $office->id,
            'persona_nombre' => 'Chofer Hospital',
            'falla_motivo' => 'No enciende',
        ]);

        $this->actingAs($admin)
            ->get(route('mesa-tecnica.index'))
            ->assertOk()
            ->assertSee('No altera patrimonio')
            ->assertSee('Chofer Hospital')
            ->assertSee('Actas y movimientos')
            ->assertSee('Ingreso tecnico')
            ->assertDontSee('Acta inmediata');
    }

    public function test_mesa_tecnica_muestra_etiqueta_imprimible(): void
    {
        [$admin, , , $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office, 'LAB');

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
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('mesa_admin_').$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $service, $office];
    }

    private function crearEquipo(Office $office, string $suffix = 'A'): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => uniqid('ser-'.$suffix.'-'),
            'bien_patrimonial' => uniqid('bp-'.$suffix.'-'),
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
