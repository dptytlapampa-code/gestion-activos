<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creacion_valida_que_servicio_pertenezca_a_institucion(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital A']);
        $institucionB = Institution::create(['nombre' => 'Hospital B']);

        $servicioA = Service::create(['nombre' => 'Guardia', 'institution_id' => $institucionA->id]);
        $servicioB = Service::create(['nombre' => 'Laboratorio', 'institution_id' => $institucionB->id]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionB->id])
            ->post(route('offices.store'), [
                'institution_id' => $institucionA->id,
                'service_id' => $servicioB->id,
                'nombre' => 'Oficina Invalida',
                'descripcion' => 'No debe guardarse',
            ])
            ->assertSessionHasErrors('service_id');

        $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionB->id])
            ->post(route('offices.store'), [
                'institution_id' => $institucionA->id,
                'service_id' => $servicioA->id,
                'nombre' => 'Oficina Valida',
                'descripcion' => 'Alta correcta',
            ])
            ->assertRedirect(route('offices.index'));

        $this->assertDatabaseHas('offices', [
            'service_id' => $servicioA->id,
            'nombre' => 'Oficina Valida',
        ]);
    }

    public function test_actualizacion_valida_que_servicio_pertenezca_a_institucion(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital Norte']);
        $institucionB = Institution::create(['nombre' => 'Hospital Sur']);

        $servicioA = Service::create(['nombre' => 'Rayos', 'institution_id' => $institucionA->id]);
        $servicioB = Service::create(['nombre' => 'UTI', 'institution_id' => $institucionB->id]);

        $office = Office::create([
            'service_id' => $servicioA->id,
            'nombre' => 'Oficina RX',
        ]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionB->id])
            ->put(route('offices.update', $office), [
                'institution_id' => $institucionA->id,
                'service_id' => $servicioB->id,
                'nombre' => 'Cambio invalido',
                'descripcion' => 'No deberia pasar',
            ])
            ->assertSessionHasErrors('service_id');

        $office->refresh();

        $this->assertSame('Oficina RX', $office->nombre);
        $this->assertSame($servicioA->id, $office->service_id);
    }

    public function test_superadmin_ve_todas_las_oficinas_aunque_tenga_institucion_activa(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital Este']);
        $institucionB = Institution::create(['nombre' => 'Hospital Oeste']);

        $servicioA = Service::create(['nombre' => 'Guardia', 'institution_id' => $institucionA->id]);
        $servicioB = Service::create(['nombre' => 'Terapia', 'institution_id' => $institucionB->id]);

        $oficinaA = Office::create(['nombre' => 'Oficina Este', 'service_id' => $servicioA->id]);
        $oficinaB = Office::create(['nombre' => 'Oficina Oeste', 'service_id' => $servicioB->id]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionA->id])
            ->get(route('offices.index'));

        $response->assertOk()
            ->assertSee($oficinaA->nombre)
            ->assertSee($oficinaB->nombre);

        $this->assertSame(2, $response->viewData('offices')->total());
    }

    public function test_admin_en_nivel_central_ve_todas_las_oficinas_desde_el_contexto_global(): void
    {
        $nivelCentral = Institution::query()
            ->where('scope_type', Institution::SCOPE_GLOBAL)
            ->firstOrFail();
        $institucionA = Institution::create(['nombre' => 'Hospital Este']);
        $institucionB = Institution::create(['nombre' => 'Hospital Oeste']);

        $servicioA = Service::create(['nombre' => 'Guardia', 'institution_id' => $institucionA->id]);
        $servicioB = Service::create(['nombre' => 'Terapia', 'institution_id' => $institucionB->id]);

        $oficinaA = Office::create(['nombre' => 'Oficina Este', 'service_id' => $servicioA->id]);
        $oficinaB = Office::create(['nombre' => 'Oficina Oeste', 'service_id' => $servicioB->id]);

        $adminCentral = $this->crearUsuario(User::ROLE_ADMIN);
        $adminCentral->institution_id = $nivelCentral->id;
        $adminCentral->save();

        $response = $this->actingAs($adminCentral)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $nivelCentral->id])
            ->get(route('offices.index'));

        $response->assertOk()
            ->assertSee($oficinaA->nombre)
            ->assertSee($oficinaB->nombre);

        $this->assertSame(2, $response->viewData('offices')->total());
    }

    public function test_admin_hospital_no_puede_crear_oficina_en_otra_institucion(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital Local']);
        $institucionB = Institution::create(['nombre' => 'Hospital Externo']);

        $servicioB = Service::create(['nombre' => 'Neonatologia', 'institution_id' => $institucionB->id]);

        $admin = $this->crearUsuario(User::ROLE_ADMIN);
        $admin->institution_id = $institucionA->id;
        $admin->save();

        $this->actingAs($admin)
            ->post(route('offices.store'), [
                'institution_id' => $institucionB->id,
                'service_id' => $servicioB->id,
                'nombre' => 'Oficina externa',
            ])
            ->assertSessionHasErrors(['institution_id', 'service_id']);

        $this->assertDatabaseMissing('offices', [
            'nombre' => 'Oficina externa',
        ]);
    }

    private function crearUsuario(string $role): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => $role.'-'.uniqid().'@test.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
