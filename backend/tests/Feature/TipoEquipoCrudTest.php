<?php

namespace Tests\Feature;

use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TipoEquipoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_tipo_equipo(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($user)->post(route('tipos-equipos.store'), [
            'nombre' => 'Monitor Multiparametro',
            'descripcion' => 'Equipo para monitoreo de signos vitales.',
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));
        $this->assertDatabaseHas('tipos_equipos', [
            'nombre' => 'Monitor Multiparametro',
        ]);
    }

    public function test_user_can_create_tipo_equipo_with_png_image(): void
    {
        Storage::fake('public');
        $user = $this->createUser(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($user)->post(route('tipos-equipos.store'), [
            'nombre' => 'Monitor con imagen',
            'descripcion' => 'Tipo con imagen.',
            'imagen_png' => UploadedFile::fake()->image('monitor.png', 320, 320),
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));

        $tipoEquipo = TipoEquipo::query()->where('nombre', 'Monitor con imagen')->firstOrFail();

        $this->assertNotNull($tipoEquipo->image_path);
        Storage::disk('public')->assertExists($tipoEquipo->image_path);
    }

    public function test_user_can_update_tipo_equipo(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Bomba de infusion',
            'descripcion' => 'Descripcion inicial.',
        ]);

        $response = $this->actingAs($user)->put(route('tipos-equipos.update', $tipoEquipo), [
            'nombre' => 'Bomba de infusion volumetrica',
            'descripcion' => 'Descripcion actualizada.',
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));
        $this->assertDatabaseHas('tipos_equipos', [
            'id' => $tipoEquipo->id,
            'nombre' => 'Bomba de infusion volumetrica',
        ]);
    }

    public function test_user_can_replace_tipo_equipo_png_image(): void
    {
        Storage::fake('public');
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        $oldPath = UploadedFile::fake()->image('old.png', 200, 200)->store('tipos-equipos', 'public');

        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Bomba con imagen',
            'descripcion' => 'Descripcion inicial.',
            'image_path' => $oldPath,
        ]);

        $response = $this->actingAs($user)->put(route('tipos-equipos.update', $tipoEquipo), [
            'nombre' => 'Bomba con imagen',
            'descripcion' => 'Descripcion actualizada.',
            'imagen_png' => UploadedFile::fake()->image('new.png', 200, 200),
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));

        $tipoEquipo->refresh();

        $this->assertNotNull($tipoEquipo->image_path);
        $this->assertNotSame($oldPath, $tipoEquipo->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($tipoEquipo->image_path);
    }

    public function test_user_can_remove_tipo_equipo_png_image(): void
    {
        Storage::fake('public');
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        $oldPath = UploadedFile::fake()->image('old.png', 200, 200)->store('tipos-equipos', 'public');

        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Ecografo',
            'descripcion' => 'Con imagen.',
            'image_path' => $oldPath,
        ]);

        $response = $this->actingAs($user)->put(route('tipos-equipos.update', $tipoEquipo), [
            'nombre' => 'Ecografo',
            'descripcion' => 'Sin imagen.',
            'remove_imagen_png' => '1',
        ]);

        $response->assertRedirect(route('tipos-equipos.index'));

        $tipoEquipo->refresh();

        $this->assertNull($tipoEquipo->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_tipo_equipo_resuelve_imagenes_legacy_con_url_publica_versionada(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tipos-equipos/monitor-cctv.png', 'png');

        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Camara CCTV',
            'descripcion' => 'Tipo con ruta legacy.',
            'image_path' => 'storage/app/public/tipos-equipos/monitor-cctv.png',
        ]);

        $tipoEquipo->refresh();

        $this->assertSame('tipos-equipos/monitor-cctv.png', $tipoEquipo->image_path);
        $this->assertStringStartsWith('/storage/tipos-equipos/monitor-cctv.png?v=', $tipoEquipo->image_url);
    }

    public function test_user_can_delete_tipo_equipo(): void
    {
        $user = $this->createUser(User::ROLE_SUPERADMIN);
        $tipoEquipo = TipoEquipo::create([
            'nombre' => 'Electrocardiografo',
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

