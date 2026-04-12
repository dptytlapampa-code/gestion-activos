<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

    public function test_superadmin_puede_subir_logos_institucional_y_pdf(): void
    {
        Storage::fake('public');

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $logoInstitucional = UploadedFile::fake()->image('institucional.png', 240, 240);
        $logoPdf = UploadedFile::fake()->image('pdf.png', 480, 160);

        $this->actingAs($superadmin)
            ->put(route('admin.configuracion.general.update'), [
                'site_name' => 'Hospital Regional Norte',
                'primary_color' => '#112233',
                'sidebar_color' => '#334455',
                'logo_institucional' => $logoInstitucional,
                'logo_pdf' => $logoPdf,
            ])
            ->assertRedirect(route('admin.configuracion.general.edit'));

        $setting = SystemSetting::query()->firstOrFail();

        $this->assertSame('logos/institucional.png', $setting->logo_institucional);
        $this->assertSame('logos/institucional.png', $setting->logo_path);
        $this->assertSame('logos/pdf.png', $setting->logo_pdf);

        Storage::disk('public')->assertExists('logos/institucional.png');
        Storage::disk('public')->assertExists('logos/pdf.png');
    }

    public function test_system_config_normaliza_rutas_legacy_y_expone_urls_relativas_con_cache_busting(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('logos/institucional.png', 'institucional');
        Storage::disk('public')->put('logos/pdf.png', 'pdf');

        SystemSetting::query()->create([
            'site_name' => 'Hospital Regional Norte',
            'primary_color' => '#112233',
            'sidebar_color' => '#334455',
            'logo_path' => storage_path('app/public/logos/institucional.png'),
            'logo_institucional' => 'http://localhost:8080/storage/logos/institucional.png',
            'logo_pdf' => '/storage/logos/pdf.png',
        ]);

        Cache::forget(system_config_cache_key());

        $settings = system_config();

        $this->assertSame('logos/institucional.png', $settings->logo_path);
        $this->assertSame('logos/institucional.png', $settings->logo_institucional);
        $this->assertSame('logos/pdf.png', $settings->logo_pdf);
        $this->assertStringStartsWith('/storage/logos/institucional.png?v=', $settings->logo_institucional_url);
        $this->assertStringStartsWith('/storage/logos/pdf.png?v=', $settings->logo_pdf_url);
        $this->assertSame(
            Storage::disk('public')->path('logos/institucional.png'),
            $settings->logo_institucional_file_path
        );
        $this->assertSame(
            Storage::disk('public')->path('logos/pdf.png'),
            $settings->logo_pdf_file_path
        );
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
