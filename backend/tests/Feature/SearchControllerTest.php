<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function createUser(string $role): User
    {
        return User::create([
            'name' => 'Search User',
            'email' => strtolower($role).'-'.uniqid().'@test.com',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
