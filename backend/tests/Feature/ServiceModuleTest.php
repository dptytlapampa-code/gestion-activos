<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Service;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_ve_todos_los_servicios_aunque_tenga_institucion_activa(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital Alpha']);
        $institucionB = Institution::create(['nombre' => 'Hospital Beta']);

        $servicioA = Service::create(['nombre' => 'Guardia Alpha', 'institution_id' => $institucionA->id]);
        $servicioB = Service::create(['nombre' => 'Guardia Beta', 'institution_id' => $institucionB->id]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionA->id])
            ->get(route('services.index'));

        $response->assertOk()
            ->assertSee($servicioA->nombre)
            ->assertSee($servicioB->nombre);

        $this->assertSame(2, $response->viewData('services')->total());
    }

    public function test_superadmin_puede_gestionar_servicios_fuera_de_su_contexto_activo(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital Norte']);
        $institucionB = Institution::create(['nombre' => 'Hospital Sur']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionA->id])
            ->post(route('services.store'), [
                'institution_id' => $institucionB->id,
                'nombre' => 'Servicio Global',
                'descripcion' => 'Alta fuera del contexto activo',
            ])
            ->assertRedirect(route('services.index'));

        $service = Service::query()->where('nombre', 'Servicio Global')->firstOrFail();

        $this->assertSame($institucionB->id, $service->institution_id);

        $this->actingAs($superadmin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionA->id])
            ->put(route('services.update', $service), [
                'institution_id' => $institucionB->id,
                'nombre' => 'Servicio Global Actualizado',
                'descripcion' => 'Edicion global',
            ])
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'institution_id' => $institucionB->id,
            'nombre' => 'Servicio Global Actualizado',
        ]);
    }

    public function test_admin_hospital_sigue_restringido_a_la_institucion_activa_en_servicios(): void
    {
        $institucionA = Institution::create(['nombre' => 'Hospital Uno']);
        $institucionB = Institution::create(['nombre' => 'Hospital Dos']);

        $servicioA = Service::create(['nombre' => 'Servicio Uno', 'institution_id' => $institucionA->id]);
        $servicioB = Service::create(['nombre' => 'Servicio Dos', 'institution_id' => $institucionB->id]);

        $admin = $this->crearUsuario(User::ROLE_ADMIN, $institucionA->id);
        $admin->permittedInstitutions()->sync([$institucionB->id]);

        $responseInstitucionA = $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionA->id])
            ->get(route('services.index'));

        $responseInstitucionA->assertOk()
            ->assertSee($servicioA->nombre)
            ->assertDontSee($servicioB->nombre);

        $this->assertSame(1, $responseInstitucionA->viewData('services')->total());

        $responseInstitucionB = $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institucionB->id])
            ->get(route('services.index'));

        $responseInstitucionB->assertOk()
            ->assertSee($servicioB->nombre)
            ->assertDontSee($servicioA->nombre);

        $this->assertSame(1, $responseInstitucionB->viewData('services')->total());
    }

    private function crearUsuario(string $role, ?int $institutionId = null): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => strtolower($role).'-'.uniqid('', true).'@test.com',
            'password' => 'password',
            'role' => $role,
            'institution_id' => $institutionId,
            'is_active' => true,
        ]);
    }
}
