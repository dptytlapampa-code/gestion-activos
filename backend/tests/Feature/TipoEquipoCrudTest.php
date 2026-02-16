<?php

namespace Tests\Feature;

use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoEquipoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_tipo_equipo(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($user)->post(route('tipos-equipos.store'), [
            'nombre' => 'Monitor Multiparámetro',
            'descripcion' => 'Equipo para monitoreo de signos vitales.',
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));
        $this->assertDatabaseHas('tipos_equipos', [
            'nombre' => 'Monitor Multiparámetro',
        ]);
    }

    public function test_user_can_update_tipo_equipo(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Bomba de Infusión',
            'descripcion' => 'Descripción inicial.',
        ]);

        $response = $this->actingAs($user)->put(route('tipos-equipos.update', $tipoEquipo), [
            'nombre' => 'Bomba de Infusión Volumétrica',
            'descripcion' => 'Descripción actualizada.',
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));
        $this->assertDatabaseHas('tipos_equipos', [
            'id' => $tipoEquipo->id,
            'nombre' => 'Bomba de Infusión Volumétrica',
        ]);
    }

    public function test_user_can_delete_tipo_equipo(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Electrocardiógrafo',
        ]);

        $response = $this->actingAs($user)->delete(route('tipos-equipos.destroy', $tipoEquipo));

        $response->assertRedirect(route('tipos-equipos.index'));
        $this->assertDatabaseMissing('tipos_equipos', [
            'id' => $tipoEquipo->id,
        ]);
    }

    public function test_validation_errors_appear(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        TipoEquipo::create([
            'nombre' => 'Monitor',
        ]);

        $response = $this->actingAs($user)->from(route('tipos-equipos.create'))->post(route('tipos-equipos.store'), [
            'nombre' => 'Monitor',
            'descripcion' => 'Duplicado',
        ]);

        $response->assertRedirect(route('tipos-equipos.create'));
        $response->assertSessionHasErrors(['nombre']);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => uniqid($role.'-', true).'@test.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
