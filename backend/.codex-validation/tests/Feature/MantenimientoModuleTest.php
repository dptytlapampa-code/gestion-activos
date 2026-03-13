<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Institution;
use App\Models\Mantenimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MantenimientoModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_crea_mantenimiento_externo_y_equipo_pasa_a_servicio_tecnico(): void
    {
        [$admin, $equipo, $institution] = $this->crearEscenario();

        $this->actingAs($admin)->post(route('equipos.mantenimientos.store', $equipo), [
            'fecha' => now()->toDateString(),
            'tipo' => Mantenimiento::TIPO_EXTERNO,
            'titulo' => 'Envío a service externo',
            'detalle' => 'Falla en fuente',
            'proveedor' => 'Proveedor X',
        ])->assertRedirect();

        $equipo->refresh()->load('equipoStatus');
        $this->assertSame(EquipoStatus::CODE_EN_SERVICIO_TECNICO, $equipo->equipoStatus?->code);
        $this->assertDatabaseHas('mantenimientos', [
            'equipo_id' => $equipo->id,
            'institution_id' => $institution->id,
            'tipo' => Mantenimiento::TIPO_EXTERNO,
        ]);
    }

    public function test_admin_crea_alta_y_calcula_dias_en_servicio(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->actingAs($admin)->post(route('equipos.mantenimientos.store', $equipo), [
            'fecha' => now()->subDays(5)->toDateString(),
            'tipo' => Mantenimiento::TIPO_EXTERNO,
            'titulo' => 'Externo',
            'detalle' => 'Salida a ST',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('equipos.mantenimientos.store', $equipo), [
            'fecha' => now()->toDateString(),
            'tipo' => Mantenimiento::TIPO_ALTA,
            'titulo' => 'Retorno',
            'detalle' => 'Equipo operativo',
        ])->assertRedirect();

        $equipo->refresh()->load('equipoStatus');
        $this->assertSame(EquipoStatus::CODE_OPERATIVA, $equipo->equipoStatus?->code);

        $alta = Mantenimiento::query()->where('tipo', Mantenimiento::TIPO_ALTA)->firstOrFail();
        $this->assertNotNull($alta->dias_en_servicio);
    }

    public function test_viewer_no_puede_crear_mantenimiento(): void
    {
        [$admin, $equipo, $institution] = $this->crearEscenario();
        $viewer = User::create([
            'name' => 'Viewer',
            'email' => uniqid().'viewer@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->post(route('equipos.mantenimientos.store', $equipo), [
            'fecha' => now()->toDateString(),
            'tipo' => Mantenimiento::TIPO_INTERNO,
            'titulo' => 'No permitido',
            'detalle' => 'x',
        ])->assertForbidden();
    }

    public function test_admin_de_otra_institucion_no_puede_tocar_equipo_ajeno(): void
    {
        [$adminA, $equipo] = $this->crearEscenario('A');
        [$adminB] = $this->crearEscenario('B');

        $this->actingAs($adminB)->post(route('equipos.mantenimientos.store', $equipo), [
            'fecha' => now()->toDateString(),
            'tipo' => Mantenimiento::TIPO_INTERNO,
            'titulo' => 'Intento',
            'detalle' => 'No permitido',
        ])->assertForbidden();
    }

    public function test_registra_auditoria_al_crear_mantenimiento(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->actingAs($admin)->post(route('equipos.mantenimientos.store', $equipo), [
            'fecha' => now()->toDateString(),
            'tipo' => Mantenimiento::TIPO_INTERNO,
            'titulo' => 'Cambio de toner',
            'detalle' => 'Se cambió toner',
        ])->assertRedirect();

        $this->assertGreaterThan(0, AuditLog::query()->where('auditable_type', Mantenimiento::class)->count());
    }

    private function crearEscenario(string $suffix = 'A'): array
    {
        $this->seedEstados();
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Notebook '.$suffix]);

        $equipo = Equipo::create([
            'tipo' => 'Notebook',
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => uniqid('ser-'),
            'bien_patrimonial' => uniqid('bp-'),
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid().$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $equipo, $institution];
    }

    private function seedEstados(): void
    {
        EquipoStatus::query()->updateOrCreate(['code' => EquipoStatus::CODE_OPERATIVA], ['name' => 'Operativa', 'color' => 'green', 'is_terminal' => false]);
        EquipoStatus::query()->updateOrCreate(['code' => EquipoStatus::CODE_EN_SERVICIO_TECNICO], ['name' => 'En Servicio Técnico', 'color' => 'yellow', 'is_terminal' => false]);
        EquipoStatus::query()->updateOrCreate(['code' => EquipoStatus::CODE_BAJA], ['name' => 'Baja', 'color' => 'red', 'is_terminal' => true]);
    }
}
