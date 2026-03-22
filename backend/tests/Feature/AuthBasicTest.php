<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available_for_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Acceso al sistema');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'tecnico@local.test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login',
            'entity_type' => 'usuario',
            'entity_id' => $user->id,
        ]);
    }

    public function test_user_cannot_login_when_inactive(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@local.test',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login_failed',
            'entity_type' => 'usuario',
            'entity_id' => $user->id,
        ]);
    }

    public function test_logout_registra_auditoria(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@local.test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'logout',
            'entity_type' => 'usuario',
            'entity_id' => $user->id,
        ]);
    }
}
