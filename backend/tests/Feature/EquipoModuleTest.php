<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipoModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceso_segun_rol(): void
    {
        $hospital = Institution::create(['nombre' => 'Hospital Norte']);
        $service = Service::create(['nombre' => 'Clínica', 'institution_id' => $hospital->id]);
        $office = Office::create(['nombre' => 'Oficina 1', 'service_id' => $service->id]);
        $equipo = $this->crearEquipo($office);

        $viewer = $this->crearUsuario(User::ROLE_VIEWER);
        $viewer->institution_id = $hospital->id;
        $viewer->save();

        $this->actingAs($viewer)->get(route('equipos.index'))->assertOk();
        $this->actingAs($viewer)->get(route('equipos.show', $equipo))->assertOk();
        $this->actingAs($viewer)->get(route('equipos.create'))->assertForbidden();

        $tecnico = $this->crearUsuario(User::ROLE_TECNICO);
        $this->actingAs($tecnico)->get(route('equipos.create'))->assertOk();
        $this->actingAs($tecnico)->delete(route('equipos.destroy', $equipo))->assertForbidden();
    }

    public function test_crud_completo_para_superadmin(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Central']);
        $service = Service::create(['nombre' => 'Laboratorio', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Lab', 'service_id' => $service->id]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $this->actingAs($superadmin);

        $payload = [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo' => 'Monitor',
            'marca' => 'Samsung',
            'modelo' => 'M1',
            'numero_serie' => 'SER-001',
            'bien_patrimonial' => 'BP-001',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => '2025-01-20',
        ];

        $this->post(route('equipos.store'), $payload)->assertRedirect(route('equipos.index'));
        $equipo = Equipo::firstOrFail();

        $this->put(route('equipos.update', $equipo), array_merge($payload, ['modelo' => 'M2', 'numero_serie' => 'SER-002', 'bien_patrimonial' => 'BP-002']))
            ->assertRedirect(route('equipos.index'));

        $this->get(route('equipos.show', $equipo))->assertOk()->assertSee('M2');

        $this->delete(route('equipos.destroy', $equipo))->assertRedirect(route('equipos.index'));
        $this->assertDatabaseMissing('equipos', ['id' => $equipo->id]);
    }

    public function test_paginacion_y_buscador(): void
    {
        $inst = Institution::create(['nombre' => 'Hospital Sur']);
        $service = Service::create(['nombre' => 'Imágenes', 'institution_id' => $inst->id]);
        $office = Office::create(['nombre' => 'Oficina RX', 'service_id' => $service->id]);

        for ($i = 1; $i <= 20; $i++) {
            Equipo::create([
                'tipo' => $i % 2 === 0 ? 'Laptop' : 'Impresora',
                'marca' => $i % 2 === 0 ? 'Dell' : 'HP',
                'modelo' => 'M-'.$i,
                'numero_serie' => 'NS-'.$i,
                'bien_patrimonial' => 'BP-'.$i,
                'estado' => $i % 2 === 0 ? Equipo::ESTADO_OPERATIVO : Equipo::ESTADO_BAJA,
                'fecha_ingreso' => now()->toDateString(),
                'oficina_id' => $office->id,
            ]);
        }

        $user = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($user)->get(route('equipos.index'))->assertOk()->assertSee('NS-1')->assertSee('NS-15')->assertDontSee('NS-16');
        $this->actingAs($user)->get(route('equipos.index', ['tipo' => 'Laptop', 'marca' => 'Dell', 'estado' => Equipo::ESTADO_OPERATIVO]))
            ->assertOk()->assertSee('Laptop')->assertDontSee('Impresora');
    }

    public function test_permisos_por_hospital_para_admin_hospital(): void
    {
        $a = Institution::create(['nombre' => 'Hospital A']);
        $b = Institution::create(['nombre' => 'Hospital B']);

        $serviceA = Service::create(['nombre' => 'Cardio', 'institution_id' => $a->id]);
        $serviceB = Service::create(['nombre' => 'Trauma', 'institution_id' => $b->id]);

        $officeA = Office::create(['nombre' => 'Oficina A', 'service_id' => $serviceA->id]);
        $officeB = Office::create(['nombre' => 'Oficina B', 'service_id' => $serviceB->id]);

        $equipoA = $this->crearEquipo($officeA);
        $equipoB = $this->crearEquipo($officeB, 'SER-B', 'BP-B');

        $admin = $this->crearUsuario(User::ROLE_ADMIN);
        $admin->institution_id = $a->id;
        $admin->save();

        $this->actingAs($admin)->get(route('equipos.show', $equipoA))->assertOk();
        $this->actingAs($admin)->get(route('equipos.show', $equipoB))->assertForbidden();
        $this->actingAs($admin)->delete(route('equipos.destroy', $equipoA))->assertRedirect();
        $this->actingAs($admin)->delete(route('equipos.destroy', $equipoB))->assertForbidden();
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

    private function crearEquipo(Office $office, string $serie = 'SER-01', string $bien = 'BP-01'): Equipo
    {
        return Equipo::create([
            'tipo' => 'Laptop',
            'marca' => 'Dell',
            'modelo' => 'XPS',
            'numero_serie' => $serie,
            'bien_patrimonial' => $bien,
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
