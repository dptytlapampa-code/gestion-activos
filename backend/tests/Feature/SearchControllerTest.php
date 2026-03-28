<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActaEquipoSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutions_returns_all_when_query_is_three_dots(): void
    {
        Institution::create(['nombre' => 'Hospital Central']);
        Institution::create(['nombre' => 'Clinica Norte']);
        Institution::create(['nombre' => 'Sanatorio Sur']);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/institutions?q=...');

        $response->assertOk();

        $labels = collect($response->json())->pluck('label')->all();

        $this->assertSame(['Clinica Norte', 'Hospital Central', 'Sanatorio Sur'], $labels);
    }

    public function test_services_returns_all_for_selected_institution_when_query_is_three_dots(): void
    {
        $institutionA = Institution::create(['nombre' => 'Hospital A']);
        $institutionB = Institution::create(['nombre' => 'Hospital B']);

        Service::create(['nombre' => 'Administracion', 'institution_id' => $institutionA->id]);
        Service::create(['nombre' => 'Enfermeria', 'institution_id' => $institutionA->id]);
        Service::create(['nombre' => 'Computos', 'institution_id' => $institutionB->id]);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/services?q=...&institution_id='.$institutionA->id);

        $response->assertOk();

        $labels = collect($response->json())->pluck('label')->all();

        $this->assertSame(['Administracion', 'Enfermeria'], $labels);
    }

    public function test_services_returns_empty_when_institution_is_missing(): void
    {
        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/services?q=...');

        $response->assertOk()->assertExactJson([]);
    }

    public function test_offices_returns_all_for_selected_service_and_institution_when_query_is_three_dots(): void
    {
        $institutionA = Institution::create(['nombre' => 'Hospital A']);
        $institutionB = Institution::create(['nombre' => 'Hospital B']);

        $serviceA = Service::create(['nombre' => 'Enfermeria', 'institution_id' => $institutionA->id]);
        $serviceB = Service::create(['nombre' => 'Enfermeria', 'institution_id' => $institutionB->id]);

        Office::create(['nombre' => 'Box 1', 'service_id' => $serviceA->id]);
        Office::create(['nombre' => 'Box 2', 'service_id' => $serviceA->id]);
        Office::create(['nombre' => 'Box Externo', 'service_id' => $serviceB->id]);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/offices?q=...&institution_id='.$institutionA->id.'&service_id='.$serviceA->id);

        $response->assertOk();

        $labels = collect($response->json())->pluck('label')->all();

        $this->assertSame(['Box 1', 'Box 2'], $labels);
    }

    public function test_offices_returns_empty_without_required_context_or_when_context_mismatches(): void
    {
        $institutionA = Institution::create(['nombre' => 'Hospital A']);
        $institutionB = Institution::create(['nombre' => 'Hospital B']);
        $serviceA = Service::create(['nombre' => 'Laboratorio', 'institution_id' => $institutionA->id]);

        $user = $this->createUser(User::ROLE_SUPERADMIN);

        $this->actingAs($user)
            ->get('/api/search/offices?q=...&institution_id='.$institutionA->id)
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($user)
            ->get('/api/search/offices?q=...&service_id='.$serviceA->id)
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($user)
            ->get('/api/search/offices?q=...&institution_id='.$institutionB->id.'&service_id='.$serviceA->id)
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_text_search_keeps_working_for_services(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital A']);
        Service::create(['nombre' => 'Cardiologia', 'institution_id' => $institution->id]);
        Service::create(['nombre' => 'Traumatologia', 'institution_id' => $institution->id]);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/services?q=card&institution_id='.$institution->id);

        $response->assertOk();

        $labels = collect($response->json())->pluck('label')->all();

        $this->assertSame(['Cardiologia'], $labels);
    }

    public function test_tipos_equipo_returns_full_list_when_query_is_three_dots_with_limit_fifty(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            TipoEquipo::create([
                'nombre' => 'Tipo '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/tipos-equipos?q=...');

        $response->assertOk();

        $payload = collect($response->json());

        $this->assertCount(50, $payload);
        $this->assertSame('Tipo 01', $payload->first()['label']);
    }

    public function test_tipos_equipo_text_search_keeps_working(): void
    {
        TipoEquipo::create(['nombre' => 'Monitor']);
        TipoEquipo::create(['nombre' => 'Notebook']);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/tipos-equipos?q=mon');

        $response->assertOk();

        $labels = collect($response->json())->pluck('label')->all();

        $this->assertSame(['Monitor'], $labels);
    }

    public function test_search_equipos_permite_listar_todos_con_tres_puntos(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Equipos']);
        $service = Service::create(['nombre' => 'Clinica', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina 1', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'CPU']);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Optiplex',
            'numero_serie' => 'SER-ALL-1',
            'bien_patrimonial' => 'BP-ALL-1',
            'mac_address' => 'AA:BB:CC:DD:EE:11',
            'codigo_interno' => 'CI-ALL-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/equipos?q=...&institution_id='.$institution->id);

        $response->assertOk();
        $payload = collect($response->json());

        $this->assertCount(1, $payload);
        $this->assertSame('SER-ALL-1', $payload->first()['numero_serie']);
    }

    public function test_search_equipos_filtra_por_mac_y_codigo_interno(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Busqueda']);
        $service = Service::create(['nombre' => 'Laboratorio', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Deposito', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Notebook']);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'HP',
            'modelo' => 'Elitebook',
            'numero_serie' => 'SER-BUS-1',
            'bien_patrimonial' => 'BP-BUS-1',
            'mac_address' => 'AA:AA:AA:AA:AA:01',
            'codigo_interno' => 'COD-INT-001',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $macResponse = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/equipos?q=AA:AA:AA:AA:AA:01&institution_id='.$institution->id);

        $macResponse->assertOk();
        $this->assertCount(1, $macResponse->json());

        $codigoResponse = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/equipos?q=COD-INT-001&institution_id='.$institution->id);

        $codigoResponse->assertOk();
        $this->assertCount(1, $codigoResponse->json());
    }


    public function test_search_equipos_excluye_baja_por_defecto_y_permite_incluirla(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Estado']);
        $service = Service::create(['nombre' => 'Soporte', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Estado', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'PC']);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Lenovo',
            'modelo' => 'M70',
            'numero_serie' => 'SER-EST-01',
            'bien_patrimonial' => 'BP-EST-01',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Lenovo',
            'modelo' => 'M80',
            'numero_serie' => 'SER-EST-02',
            'bien_patrimonial' => 'BP-EST-02',
            'estado' => Equipo::ESTADO_BAJA,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $user = $this->createUser(User::ROLE_SUPERADMIN);

        $defaultResponse = $this->actingAs($user)
            ->get('/api/search/equipos?q=...&institution_id='.$institution->id);

        $defaultResponse->assertOk();
        $this->assertCount(1, $defaultResponse->json());
        $this->assertSame('SER-EST-01', $defaultResponse->json()[0]['numero_serie']);

        $includeBajaResponse = $this->actingAs($user)
            ->get('/api/search/equipos?q=...&institution_id='.$institution->id.'&include_baja=1');

        $includeBajaResponse->assertOk();
        $this->assertCount(2, $includeBajaResponse->json());
    }

    public function test_admin_actas_context_puede_buscar_servicios_de_otra_institucion_para_entrega(): void
    {
        $institutionA = Institution::create(['nombre' => 'Hospital Origen']);
        $institutionB = Institution::create(['nombre' => 'Hospital Destino']);

        Service::create(['nombre' => 'Servicio A', 'institution_id' => $institutionA->id]);
        Service::create(['nombre' => 'Servicio B', 'institution_id' => $institutionB->id]);

        $admin = $this->createUser(User::ROLE_ADMIN, $institutionA->id);

        $this->actingAs($admin)
            ->get('/api/search/services?q=...&institution_id='.$institutionB->id)
            ->assertOk()
            ->assertExactJson([]);

        $response = $this->actingAs($admin)
            ->get('/api/search/services?q=...&institution_id='.$institutionB->id.'&acta_context=1');

        $response->assertOk();
        $labels = collect($response->json())->pluck('label')->all();
        $this->assertSame(['Servicio B'], $labels);
    }

    public function test_admin_actas_context_puede_buscar_oficinas_de_otra_institucion_para_entrega(): void
    {
        $institutionA = Institution::create(['nombre' => 'Hospital Origen']);
        $institutionB = Institution::create(['nombre' => 'Hospital Destino']);

        $serviceA = Service::create(['nombre' => 'Servicio A', 'institution_id' => $institutionA->id]);
        $serviceB = Service::create(['nombre' => 'Servicio B', 'institution_id' => $institutionB->id]);

        Office::create(['nombre' => 'Oficina A1', 'service_id' => $serviceA->id]);
        Office::create(['nombre' => 'Oficina B1', 'service_id' => $serviceB->id]);

        $admin = $this->createUser(User::ROLE_ADMIN, $institutionA->id);

        $this->actingAs($admin)
            ->get('/api/search/offices?q=...&institution_id='.$institutionB->id.'&service_id='.$serviceB->id)
            ->assertOk()
            ->assertExactJson([]);

        $response = $this->actingAs($admin)
            ->get('/api/search/offices?q=...&institution_id='.$institutionB->id.'&service_id='.$serviceB->id.'&acta_context=1');

        $response->assertOk();
        $labels = collect($response->json())->pluck('label')->all();
        $this->assertSame(['Oficina B1'], $labels);
    }

    public function test_acta_search_no_carga_resultados_sin_criterios(): void
    {
        $response = $this->actingAs($this->createUser(User::ROLE_ADMIN))
            ->get('/api/search/acta-equipos');

        $response->assertOk()
            ->assertJsonCount(0, 'items')
            ->assertJsonPath('meta.searched', false);
    }

    public function test_acta_search_permite_buscar_por_filtros_sin_texto_y_por_uuid(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Actas']);
        $service = Service::create(['nombre' => 'Laboratorio', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Sala 1', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Monitor']);

        $equipo = Equipo::create([
            'uuid' => '32dcff4a-9954-4dd1-9a9b-11e8f78ee001',
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Philips',
            'modelo' => 'MX450',
            'numero_serie' => 'SER-ACTA-01',
            'bien_patrimonial' => 'BP-ACTA-01',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $user = $this->createUser(User::ROLE_SUPERADMIN);

        $filterResponse = $this->actingAs($user)
            ->get('/api/search/acta-equipos?institution_id='.$institution->id.'&service_id='.$service->id.'&office_id='.$office->id.'&tipo_equipo_id='.$tipo->id.'&estado='.Equipo::ESTADO_OPERATIVO);

        $filterResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $equipo->id)
            ->assertJsonPath('meta.searched', true);

        $uuidResponse = $this->actingAs($user)
            ->get('/api/search/acta-equipos?q=32dcff4a-9954-4dd1-9a9b-11e8f78ee001');

        $uuidResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.uuid', '32dcff4a-9954-4dd1-9a9b-11e8f78ee001');
    }

    public function test_acta_search_permite_buscar_por_nombre_de_tipo_equipo(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Tipo']);
        $service = Service::create(['nombre' => 'Imagenes', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Sala 2', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Monitor Multiparametrico']);

        $equipo = Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Mindray',
            'modelo' => 'iMEC',
            'numero_serie' => 'SER-TIPO-01',
            'bien_patrimonial' => 'BP-TIPO-01',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $response = $this->actingAs($this->createUser(User::ROLE_SUPERADMIN))
            ->get('/api/search/acta-equipos?q=Multiparametrico');

        $response->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $equipo->id)
            ->assertJsonPath('items.0.tipo_equipo_id', $tipo->id);
    }

    public function test_acta_search_respeta_permisos_de_instituciones_accesibles(): void
    {
        $institutionA = Institution::create(['nombre' => 'Hospital A']);
        $institutionB = Institution::create(['nombre' => 'Hospital B']);
        $institutionC = Institution::create(['nombre' => 'Hospital C']);

        $serviceB = Service::create(['nombre' => 'Diagnostico', 'institution_id' => $institutionB->id]);
        $serviceC = Service::create(['nombre' => 'Diagnostico', 'institution_id' => $institutionC->id]);
        $officeB = Office::create(['nombre' => 'Sala B', 'service_id' => $serviceB->id]);
        $officeC = Office::create(['nombre' => 'Sala C', 'service_id' => $serviceC->id]);
        $tipo = TipoEquipo::create(['nombre' => 'ECG']);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'GE',
            'modelo' => 'B-1',
            'numero_serie' => 'PERM-B-01',
            'bien_patrimonial' => 'BP-PERM-B',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $officeB->id,
        ]);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'GE',
            'modelo' => 'C-1',
            'numero_serie' => 'PERM-C-01',
            'bien_patrimonial' => 'BP-PERM-C',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $officeC->id,
        ]);

        $admin = $this->createUser(User::ROLE_ADMIN, $institutionA->id);
        $admin->permittedInstitutions()->attach($institutionB->id);

        $allowedResponse = $this->actingAs($admin)
            ->get('/api/search/acta-equipos?institution_id='.$institutionB->id.'&q=PERM');

        $allowedResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.institucion', 'Hospital B');

        $blockedResponse = $this->actingAs($admin)
            ->get('/api/search/acta-equipos?institution_id='.$institutionC->id.'&q=PERM');

        $blockedResponse->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_acta_search_devuelve_error_controlado_si_falla_el_servicio(): void
    {
        $service = Mockery::mock(ActaEquipoSearchService::class);
        $service->shouldReceive('search')
            ->once()
            ->andThrow(new \RuntimeException('search failed'));

        $this->app->instance(ActaEquipoSearchService::class, $service);

        $response = $this->actingAs($this->createUser(User::ROLE_ADMIN))
            ->get('/api/search/acta-equipos?q=SER-123');

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Ocurrio un error al buscar equipos. Intente nuevamente en unos segundos.',
            ]);
    }

    public function test_acta_context_en_search_equipos_devuelve_formato_paginado_compatible(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Compat']);
        $service = Service::create(['nombre' => 'Bioingenieria', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Taller', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Bomba de infusion']);

        $equipo = Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Baxter',
            'modelo' => 'X1',
            'numero_serie' => 'SER-COMP-01',
            'bien_patrimonial' => 'BP-COMP-01',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $response = $this->actingAs($this->createUser(User::ROLE_ADMIN, $institution->id))
            ->get('/api/search/equipos?q=SER-COMP-01&acta_context=1');

        $response->assertOk()
            ->assertJsonPath('items.0.id', $equipo->id)
            ->assertJsonPath('meta.searched', true)
            ->assertJsonPath('meta.has_more', false);
    }

    public function test_acta_search_rechaza_usuarios_sin_permiso_para_crear_actas(): void
    {
        $response = $this->actingAs($this->createUser(User::ROLE_VIEWER))
            ->get('/api/search/acta-equipos?q=SER-123');

        $response->assertForbidden();
    }

    public function test_tecnico_puede_buscar_equipos_en_contexto_de_actas(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Tecnico']);
        $service = Service::create(['nombre' => 'Bioingenieria', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Taller', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Monitor']);

        $equipo = Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Philips',
            'modelo' => 'IntelliVue',
            'numero_serie' => 'TEC-ACTA-01',
            'bien_patrimonial' => 'BP-TEC-ACTA-01',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $response = $this->actingAs($this->createUser(User::ROLE_TECNICO, $institution->id))
            ->get('/api/search/acta-equipos?institution_id='.$institution->id.'&q=TEC-ACTA');

        $response->assertOk()
            ->assertJsonPath('items.0.id', $equipo->id)
            ->assertJsonPath('meta.searched', true);
    }


    private function createUser(string $role, ?int $institutionId = null): User
    {
        return User::create([
            'name' => 'Search User',
            'email' => strtolower($role).'-'.uniqid().'@test.com',
            'password' => 'password',
            'role' => $role,
            'institution_id' => $institutionId,
            'is_active' => true,
        ]);
    }
}

