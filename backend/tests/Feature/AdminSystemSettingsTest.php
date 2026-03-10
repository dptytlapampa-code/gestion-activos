<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_puede_ver_modulo_configuracion_general(): void
    {
        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)
            ->get(route('admin.configuracion.general.edit'))
            ->assertOk()
            ->assertSee('Configuracion general');
    }

    public function test_usuario_no_superadmin_recibe_403_en_modulo_configuracion_general(): void
    {
        $admin = $this->crearUsuario(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.configuracion.general.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.configuracion.general.update'), [
                'site_name' => 'Hospital Regional',
                'primary_color' => '#1F2937',
                'sidebar_color' => '#111827',
            ])
            ->assertForbidden();
    }

    public function test_superadmin_puede_actualizar_nombre_y_colores(): void
    {
        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)
            ->put(route('admin.configuracion.general.update'), [
                'site_name' => 'Hospital Regional Norte',
                'primary_color' => '#112233',
                'sidebar_color' => '#334455',
            ])
            ->assertRedirect(route('admin.configuracion.general.edit'))
            ->assertSessionHas('status', 'La configuracion general se actualizo correctamente.');

        $this->assertDatabaseHas('system_settings', [
            'site_name' => 'Hospital Regional Norte',
            'primary_color' => '#112233',
            'sidebar_color' => '#334455',
        ]);

        $this->assertSame(1, SystemSetting::query()->count());
    }

    public function test_valida_colores_invalidos(): void
    {
        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)
            ->from(route('admin.configuracion.general.edit'))
            ->put(route('admin.configuracion.general.update'), [
                'site_name' => 'Hospital Regional Norte',
                'primary_color' => 'azul',
                'sidebar_color' => '#12GG11',
            ])
            ->assertRedirect(route('admin.configuracion.general.edit'))
            ->assertSessionHasErrors(['primary_color', 'sidebar_color']);
    }

    public function test_superadmin_puede_subir_logo(): void
    {
        Storage::fake('public');

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $logo = UploadedFile::fake()->image('logo.png', 240, 240);

        $this->actingAs($superadmin)
            ->put(route('admin.configuracion.general.update'), [
                'site_name' => 'Hospital Regional Norte',
                'primary_color' => '#112233',
                'sidebar_color' => '#334455',
                'logo' => $logo,
            ])
            ->assertRedirect(route('admin.configuracion.general.edit'));

        $setting = SystemSetting::query()->firstOrFail();

        $this->assertNotNull($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);
    }

    private function crearUsuario(string $role): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => uniqid('', true).$role.'@test.com',
            'password' => '123456',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
