<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_puede_buscar_y_paginar_instituciones_con_whitelist_de_per_page(): void
    {
        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        for ($i = 1; $i <= 21; $i++) {
            Institution::create([
                'codigo' => 'INST-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'nombre' => 'Hospital MAT-'.$i,
                'descripcion' => 'Institucion '.$i,
                'localidad' => 'Ciudad '.$i,
            ]);
        }

        $defaultResponse = $this->actingAs($superadmin)->get(route('institutions.index'));

        $defaultResponse->assertOk();
        $this->assertSame(20, $defaultResponse->viewData('institutions')->perPage());
        $this->assertCount(20, $defaultResponse->viewData('institutions')->items());

        $searchResponse = $this->actingAs($superadmin)->get(route('institutions.index', [
            'search' => 'MAT-21',
            'per_page' => 5,
        ]));

        $searchResponse->assertOk();

        $searchPaginator = $searchResponse->viewData('institutions');

        $this->assertSame(5, $searchPaginator->perPage());
        $this->assertSame(1, $searchPaginator->total());
        $this->assertSame('Hospital MAT-21', collect($searchPaginator->items())->first()?->nombre);

        $invalidResponse = $this->actingAs($superadmin)->get(route('institutions.index', ['per_page' => 999]));

        $invalidResponse->assertOk();
        $this->assertSame(20, $invalidResponse->viewData('institutions')->perPage());
    }

    public function test_admin_hospital_solo_visualiza_su_institucion_en_el_listado(): void
    {
        $hospitalPropio = Institution::create(['nombre' => 'Hospital Propio']);
        $hospitalAjeno = Institution::create(['nombre' => 'Hospital Ajeno']);
        $admin = $this->crearUsuario(User::ROLE_ADMIN, $hospitalPropio->id);

        $response = $this->actingAs($admin)->get(route('institutions.index', ['search' => 'Hospital']));

        $response->assertOk();

        $paginator = $response->viewData('institutions');

        $this->assertSame(1, $paginator->total());
        $this->assertSame($hospitalPropio->id, collect($paginator->items())->first()?->id);
        $this->assertNotSame($hospitalAjeno->id, collect($paginator->items())->first()?->id);
    }

    private function crearUsuario(string $role, ?int $institutionId = null): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => strtolower($role).'-'.uniqid().'@test.com',
            'password' => 'password',
            'role' => $role,
            'institution_id' => $institutionId,
            'is_active' => true,
        ]);
    }
}
