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

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.mantenimientos.store', $equipo), [
                'fecha' => now()->toDateString(),
                'tipo' => Mantenimiento::TIPO_EXTERNO,
                'titulo' => 'Envio a service externo',
                'detalle' => 'Falla en fuente',
                'proveedor' => 'Proveedor X',
                'fecha_ingreso_st' => now()->toDateString(),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $equipo->refresh()->load('equipoStatus');

        $this->assertSame(EquipoStatus::CODE_EN_SERVICIO_TECNICO, $equipo->equipoStatus?->code);
        $this->assertDatabaseHas('mantenimientos', [
            'equipo_id' => $equipo->id,
            'institution_id' => $institution->id,
            'tipo' => Mantenimiento::TIPO_EXTERNO,
            'fecha_ingreso_st' => now()->toDateString(),
            'fecha_egreso_st' => null,
            'mantenimiento_externo_id' => null,
        ]);
    }

    public function test_admin_crea_alta_y_cierra_correctamente_el_mantenimiento_externo(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->crearMantenimientoExterno($admin, $equipo, now()->subDays(5)->toDateString());

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.mantenimientos.store', $equipo), [
                'fecha' => now()->toDateString(),
                'tipo' => Mantenimiento::TIPO_ALTA,
                'titulo' => 'Retorno del equipo',
                'detalle' => 'Equipo operativo nuevamente',
                'fecha_egreso_st' => now()->toDateString(),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $equipo->refresh()->load('equipoStatus');
        $externo = Mantenimiento::query()->where('equipo_id', $equipo->id)->where('tipo', Mantenimiento::TIPO_EXTERNO)->firstOrFail();
        $alta = Mantenimiento::query()->where('equipo_id', $equipo->id)->where('tipo', Mantenimiento::TIPO_ALTA)->firstOrFail();

        $this->assertSame(EquipoStatus::CODE_OPERATIVA, $equipo->equipoStatus?->code);
        $this->assertSame(now()->toDateString(), $externo->fecha_egreso_st?->toDateString());
        $this->assertSame(5, $externo->dias_en_servicio);
        $this->assertSame($externo->id, $alta->mantenimiento_externo_id);
        $this->assertSame(5, $alta->dias_en_servicio);
    }

    public function test_baja_cierra_el_mantenimiento_externo_y_pasa_el_equipo_a_baja(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->crearMantenimientoExterno($admin, $equipo, now()->subDays(2)->toDateString());

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.mantenimientos.store', $equipo), [
                'fecha' => now()->toDateString(),
                'tipo' => Mantenimiento::TIPO_BAJA,
                'titulo' => 'Equipo fuera de servicio',
                'detalle' => 'No admite reparacion',
                'fecha_egreso_st' => now()->toDateString(),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $equipo->refresh()->load('equipoStatus');
        $externo = Mantenimiento::query()->where('equipo_id', $equipo->id)->where('tipo', Mantenimiento::TIPO_EXTERNO)->firstOrFail();
        $baja = Mantenimiento::query()->where('equipo_id', $equipo->id)->where('tipo', Mantenimiento::TIPO_BAJA)->firstOrFail();

        $this->assertSame(EquipoStatus::CODE_BAJA, $equipo->equipoStatus?->code);
        $this->assertNotNull($externo->fecha_egreso_st);
        $this->assertSame($externo->id, $baja->mantenimiento_externo_id);
    }

    public function test_no_permited_alta_huerfana_y_devuelve_mensaje_amigable_aun_con_debug_activo(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        config()->set('app.debug', true);

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.mantenimientos.store', $equipo), [
                'fecha' => now()->toDateString(),
                'tipo' => Mantenimiento::TIPO_ALTA,
                'titulo' => 'Alta sin externo',
                'detalle' => 'No deberia guardarse',
                'fecha_egreso_st' => now()->toDateString(),
            ])
            ->assertRedirect(route('equipos.show', $equipo))
            ->assertSessionHasErrors([
                'mantenimiento' => 'No se puede dar el alta porque este equipo no tiene un mantenimiento externo abierto registrado.',
            ]);

        $this->assertDatabaseMissing('mantenimientos', [
            'equipo_id' => $equipo->id,
            'tipo' => Mantenimiento::TIPO_ALTA,
        ]);
    }

    public function test_no_permited_dos_mantenimientos_externos_abiertos_para_el_mismo_equipo(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->crearMantenimientoExterno($admin, $equipo, now()->subDay()->toDateString());

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.mantenimientos.store', $equipo), [
                'fecha' => now()->toDateString(),
                'tipo' => Mantenimiento::TIPO_EXTERNO,
                'titulo' => 'Nuevo envio',
                'detalle' => 'No deberia permitirse',
                'proveedor' => 'Otro proveedor',
                'fecha_ingreso_st' => now()->toDateString(),
            ])
            ->assertRedirect(route('equipos.show', $equipo))
            ->assertSessionHasErrors([
                'mantenimiento' => 'No se puede registrar un nuevo mantenimiento externo porque ya existe uno abierto para este equipo.',
            ]);

        $this->assertSame(
            1,
            Mantenimiento::query()
                ->where('equipo_id', $equipo->id)
                ->where('tipo', Mantenimiento::TIPO_EXTERNO)
                ->whereNull('fecha_egreso_st')
                ->count()
        );
    }

    public function test_registros_que_afectan_trazabilidad_no_se_pueden_editar_ni_eliminar(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->crearMantenimientoExterno($admin, $equipo, now()->subDay()->toDateString());

        $mantenimiento = Mantenimiento::query()
            ->where('equipo_id', $equipo->id)
            ->where('tipo', Mantenimiento::TIPO_EXTERNO)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('mantenimientos.edit', $mantenimiento))
            ->assertRedirect(route('equipos.show', $equipo))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->delete(route('mantenimientos.destroy', $mantenimiento))
            ->assertRedirect(route('equipos.show', $equipo))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('mantenimientos', ['id' => $mantenimiento->id]);
    }

    public function test_no_se_puede_marcar_manualmenta_el_equipo_en_mantenimiento_sin_respaldo_tecnico(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $payload = $this->payloadEquipo($equipo, Equipo::ESTADO_MANTENIMIENTO);

        $this->actingAs($admin)
            ->put(route('equipos.update', $equipo), $payload)
            ->assertSessionHasErrors([
                'estado' => 'El equipo solo puede quedar en Mantenimiento si existe un mantenimiento externo abierto registrado desde su ficha.',
            ]);

        $equipo->refresh();
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
    }

    public function test_no_se_puede_sacar_manualmenta_el_equipo_de_mantenimiento_si_hay_un_externo_abierto(): void
    {
        [$admin, $equipo] = $this->crearEscenario();

        $this->crearMantenimientoExterno($admin, $equipo, now()->subDay()->toDateString());

        $equipo->refresh();

        $payload = $this->payloadEquipo($equipo, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)
            ->put(route('equipos.update', $equipo), $payload)
            ->assertSessionHasErrors([
                'estado' => 'No puede cambiar manualmente el estado mientras exista un mantenimiento externo abierto. Registre el alta o la baja desde la ficha del equipo.',
            ]);

        $equipo->refresh();
        $this->assertSame(Equipo::ESTADO_MANTENIMIENTO, $equipo->estado);
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
            'detalle' => 'Se cambio toner',
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

    private function crearMantenimientoExterno(User $admin, Equipo $equipo, string $fechaIngreso): void
    {
        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.mantenimientos.store', $equipo), [
                'fecha' => $fechaIngreso,
                'tipo' => Mantenimiento::TIPO_EXTERNO,
                'titulo' => 'Salida a servicio tecnico',
                'detalle' => 'Revision externa',
                'proveedor' => 'Proveedor externo',
                'fecha_ingreso_st' => $fechaIngreso,
            ])
            ->assertRedirect(route('equipos.show', $equipo));
    }

    private function payloadEquipo(Equipo $equipo, string $estado): array
    {
        $equipo->loadMissing('oficina.service.institution', 'tipoEquipo');

        return [
            'institution_id' => $equipo->oficina?->service?->institution?->id,
            'service_id' => $equipo->oficina?->service?->id,
            'office_id' => $equipo->oficina?->id,
            'tipo_equipo_id' => $equipo->tipo_equipo_id,
            'marca' => $equipo->marca,
            'modelo' => $equipo->modelo,
            'numero_serie' => $equipo->numero_serie,
            'bien_patrimonial' => $equipo->bien_patrimonial,
            'estado' => $estado,
            'fecha_ingreso' => $equipo->fecha_ingreso?->toDateString() ?? now()->toDateString(),
        ];
    }

    private function seedEstados(): void
    {
        EquipoStatus::query()->updateOrCreate(['code' => EquipoStatus::CODE_OPERATIVA], ['name' => 'Operativa', 'color' => 'green', 'is_terminal' => false]);
        EquipoStatus::query()->updateOrCreate(['code' => EquipoStatus::CODE_EN_SERVICIO_TECNICO], ['name' => 'En Servicio Tecnico', 'color' => 'yellow', 'is_terminal' => false]);
        EquipoStatus::query()->updateOrCreate(['code' => EquipoStatus::CODE_BAJA], ['name' => 'Baja', 'color' => 'red', 'is_terminal' => true]);
    }
}
