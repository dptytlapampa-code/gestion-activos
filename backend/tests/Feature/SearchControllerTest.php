<?php

namespace Tests\Feature;

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
