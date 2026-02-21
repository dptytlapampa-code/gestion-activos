<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_puede_ver_y_crear_usuarios(): void
    {
        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $institution = Institution::create(['nombre' => 'Hospital Uno']);

        $this->actingAs($superadmin)->get(route('admin.users.index'))->assertOk();

        $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    }

    public function test_no_superadmin_recibe_403_en_admin_users_y_auditoria(): void
    {
        $admin = $this->crearUsuario(User::ROLE_ADMIN);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_auditoria_se_registra_al_crear_equipo_y_movimiento(): void
    {
        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $institution = Institution::create(['nombre' => 'Hospital']);
        $service = Service::create(['nombre' => 'Clínica', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Monitor']);

        $this->actingAs($superadmin)->post(route('equipos.store'), [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Samsung',
            'modelo' => 'M1',
            'numero_serie' => 'SER-1',
            'bien_patrimonial' => 'BP-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $equipo = Equipo::firstOrFail();

        $this->actingAs($superadmin)->post(route('equipos.movimientos.store', $equipo), [
            'tipo_movimiento' => 'mantenimiento',
            'observacion' => 'Revisión',
        ]);

        $this->assertDatabaseHas('audit_logs', ['auditable_type' => Equipo::class, 'action' => 'create']);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => Movimiento::class, 'action' => 'create']);
    }

    private function crearUsuario(string $role): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => uniqid().$role.'@test.com',
            'password' => '123456',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
