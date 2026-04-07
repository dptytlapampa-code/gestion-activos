<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\RecepcionTecnica;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\EquipoListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipoIndexRecepcionTecnicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipos_index_responde_cuando_hay_recepcion_tecnica_abierta(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase('A');
        $equipo = $this->crearEquipo($office, 'A');
        $recepcion = $this->crearRecepcionTecnicaAbierta($admin, $equipo, $institution, $service, $office, 'A');

        $this->actingAs($admin)
            ->get(route('equipos.index'))
            ->assertOk()
            ->assertSee($equipo->numero_serie)
            ->assertSee('Ingreso tecnico '.$recepcion->codigo);
    }

    public function test_equipos_index_responde_cuando_no_hay_recepcion_tecnica(): void
    {
        [$admin, , , $office] = $this->crearEscenarioBase('B');
        $equipo = $this->crearEquipo($office, 'B');

        $this->actingAs($admin)
            ->get(route('equipos.index'))
            ->assertOk()
            ->assertSee($equipo->numero_serie)
            ->assertDontSee('Ingreso tecnico ');
    }

    public function test_equipo_listing_service_carga_recepcion_abierta_sin_ambiguedad_y_con_datos_necesarios(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase('C');
        $equipo = $this->crearEquipo($office, 'C');
        $recepcion = $this->crearRecepcionTecnicaAbierta($admin, $equipo, $institution, $service, $office, 'C');

        $equipos = app(EquipoListingService::class)
            ->buildIndexQuery($admin, '', app(EquipoListingService::class)->emptyFilters())
            ->get();

        $equipoListado = $equipos->firstWhere('id', $equipo->id);

        $this->assertNotNull($equipoListado);
        $this->assertTrue($equipoListado->relationLoaded('recepcionTecnicaAbierta'));
        $this->assertNotNull($equipoListado->recepcionTecnicaAbierta);
        $this->assertSame($recepcion->id, $equipoListado->recepcionTecnicaAbierta->id);
        $this->assertSame($equipo->id, $equipoListado->recepcionTecnicaAbierta->equipo_id);
        $this->assertSame($recepcion->codigo, $equipoListado->recepcionTecnicaAbierta->codigo);
        $this->assertSame($recepcion->estado, $equipoListado->recepcionTecnicaAbierta->estado);
    }

    public function test_equipos_index_mantiene_scope_por_institucion_con_recepcion_tecnica_abierta(): void
    {
        [$adminA, $institutionA, $serviceA, $officeA] = $this->crearEscenarioBase('AA');
        [$adminB, $institutionB, $serviceB, $officeB] = $this->crearEscenarioBase('BB');
        $equipoA = $this->crearEquipo($officeA, 'AA');
        $equipoB = $this->crearEquipo($officeB, 'BB');
        $recepcionA = $this->crearRecepcionTecnicaAbierta($adminA, $equipoA, $institutionA, $serviceA, $officeA, 'AA');
        $recepcionB = $this->crearRecepcionTecnicaAbierta($adminB, $equipoB, $institutionB, $serviceB, $officeB, 'BB');

        $this->actingAs($adminA)
            ->get(route('equipos.index'))
            ->assertOk()
            ->assertSee($equipoA->numero_serie)
            ->assertSee('Ingreso tecnico '.$recepcionA->codigo)
            ->assertDontSee($equipoB->numero_serie)
            ->assertDontSee($recepcionB->codigo);
    }

    /**
     * @return array{0:User,1:Institution,2:Service,3:Office}
     */
    private function crearEscenarioBase(string $suffix): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('equipos_index_', true).$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $service, $office];
    }

    private function crearEquipo(Office $office, string $suffix): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => 'SER-'.$suffix.'-'.uniqid(),
            'bien_patrimonial' => 'BP-'.$suffix.'-'.uniqid(),
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }

    private function crearRecepcionTecnicaAbierta(
        User $user,
        Equipo $equipo,
        Institution $institution,
        Service $service,
        Office $office,
        string $suffix
    ): RecepcionTecnica {
        return RecepcionTecnica::query()->create([
            'institution_id' => $institution->id,
            'created_by' => $user->id,
            'recibido_por' => $user->id,
            'equipo_id' => $equipo->id,
            'fecha_recepcion' => now()->toDateString(),
            'ingresado_at' => now()->subMinutes(5),
            'estado' => RecepcionTecnica::ESTADO_RECIBIDO,
            'status_changed_at' => now()->subMinutes(5),
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
            'persona_nombre' => 'Chofer '.$suffix,
            'persona_documento' => '20111222',
            'persona_area' => 'Logistica',
            'persona_institucion' => $institution->nombre,
            'persona_relacion_equipo' => 'Chofer',
            'falla_motivo' => 'No enciende',
        ]);
    }
}
