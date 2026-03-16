<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\EquipoHistorial;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquipoModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceso_segun_rol(): void
    {
        $hospital = Institution::create(['nombre' => 'Hospital Norte']);
        $service = Service::create(['nombre' => 'Clinica', 'institution_id' => $hospital->id]);
        $office = Office::create(['nombre' => 'Oficina 1', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Laptop clinica']);
        $equipo = $this->crearEquipo($office, $tipoEquipo);

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
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Monitor']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $this->actingAs($superadmin);

        $payload = [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipoEquipo->id,
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
        $service = Service::create(['nombre' => 'Imagenes', 'institution_id' => $inst->id]);
        $office = Office::create(['nombre' => 'Oficina RX', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Laptop']);
        $tipoEquipo2 = TipoEquipo::create(['nombre' => 'Impresora']);

        for ($i = 1; $i <= 21; $i++) {
            $selectedTipoEquipo = $i % 2 === 0 ? $tipoEquipo : $tipoEquipo2;

            Equipo::create([
                'tipo' => $selectedTipoEquipo->nombre,
                'tipo_equipo_id' => $selectedTipoEquipo->id,
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

        $response = $this->actingAs($user)->get(route('equipos.index'));
        $response->assertOk();

        $paginator = $response->viewData('equipos');

        $this->assertSame(20, $paginator->perPage());
        $this->assertCount(20, $paginator->items());
        $this->assertTrue(collect($paginator->items())->contains(fn (Equipo $equipo): bool => $equipo->numero_serie === 'NS-1'));
        $this->assertFalse(collect($paginator->items())->contains(fn (Equipo $equipo): bool => $equipo->numero_serie === 'NS-21'));

        $filteredResponse = $this->actingAs($user)->get(route('equipos.index', [
            'search' => 'Dell',
            'tipo' => 'Laptop',
            'estado' => Equipo::ESTADO_OPERATIVO,
        ]));

        $filteredResponse->assertOk();

        $filteredPaginator = $filteredResponse->viewData('equipos');

        $this->assertGreaterThan(0, $filteredPaginator->total());
        $this->assertTrue(
            collect($filteredPaginator->items())->every(
                fn (Equipo $equipo): bool => $equipo->tipo === 'Laptop'
                    && $equipo->marca === 'Dell'
                    && $equipo->estado === Equipo::ESTADO_OPERATIVO
            )
        );
    }

    public function test_per_page_invalido_vuelve_a_20_y_la_paginacion_conserva_query_string(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Query']);
        $service = Service::create(['nombre' => 'Clinica', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Sala', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Monitor']);

        for ($i = 1; $i <= 8; $i++) {
            Equipo::create([
                'tipo' => $tipoEquipo->nombre,
                'tipo_equipo_id' => $tipoEquipo->id,
                'marca' => 'Dell',
                'modelo' => 'Serie-'.$i,
                'numero_serie' => 'QUERY-'.$i,
                'bien_patrimonial' => 'BP-QUERY-'.$i,
                'estado' => Equipo::ESTADO_OPERATIVO,
                'fecha_ingreso' => now()->toDateString(),
                'oficina_id' => $office->id,
            ]);
        }

        $user = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $invalidResponse = $this->actingAs($user)->get(route('equipos.index', ['per_page' => 999]));

        $invalidResponse->assertOk();
        $this->assertSame(20, $invalidResponse->viewData('equipos')->perPage());

        $paginatedResponse = $this->actingAs($user)->get(route('equipos.index', [
            'search' => 'Dell',
            'per_page' => 5,
        ]));

        $paginatedResponse->assertOk()
            ->assertSee('search=Dell', false)
            ->assertSee('per_page=5', false)
            ->assertSee('page=2', false);
    }

    public function test_permisos_por_hospital_para_admin_hospital(): void
    {
        $a = Institution::create(['nombre' => 'Hospital A']);
        $b = Institution::create(['nombre' => 'Hospital B']);

        $serviceA = Service::create(['nombre' => 'Cardio', 'institution_id' => $a->id]);
        $serviceB = Service::create(['nombre' => 'Trauma', 'institution_id' => $b->id]);

        $officeA = Office::create(['nombre' => 'Oficina A', 'service_id' => $serviceA->id]);
        $officeB = Office::create(['nombre' => 'Oficina B', 'service_id' => $serviceB->id]);

        $tipoEquipo = TipoEquipo::create(['nombre' => 'Laptop']);

        $equipoA = $this->crearEquipo($officeA, $tipoEquipo);
        $equipoB = $this->crearEquipo($officeB, $tipoEquipo, 'SER-B', 'BP-B');

        $admin = $this->crearUsuario(User::ROLE_ADMIN);
        $admin->institution_id = $a->id;
        $admin->save();

        $this->actingAs($admin)->get(route('equipos.show', $equipoA))->assertOk();
        $this->actingAs($admin)->get(route('equipos.show', $equipoB))->assertForbidden();
        $this->actingAs($admin)->delete(route('equipos.destroy', $equipoA))->assertRedirect();
        $this->actingAs($admin)->delete(route('equipos.destroy', $equipoB))->assertForbidden();
    }


    public function test_creacion_acepta_estado_valido(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Valido']);
        $service = Service::create(['nombre' => 'Servicio Valido', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Valida', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Bomba de infusion']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)->post(route('equipos.store'), [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Mindray',
            'modelo' => 'IP-100',
            'numero_serie' => 'SER-VALIDO-1',
            'bien_patrimonial' => 'BP-VALIDO-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => '2025-02-10',
        ])->assertRedirect(route('equipos.index'));

        $this->assertDatabaseHas('equipos', [
            'numero_serie' => 'SER-VALIDO-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
        ]);
    }

    public function test_creacion_recrea_estado_operativa_si_falta_configuracion_de_status(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Sin Status']);
        $service = Service::create(['nombre' => 'Servicio Sin Status', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Sin Status', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Monitor de Guardia']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        DB::table('equipo_statuses')->delete();

        $this->actingAs($superadmin)->post(route('equipos.store'), [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Philips',
            'modelo' => 'G5',
            'numero_serie' => 'SER-STATUS-1',
            'bien_patrimonial' => 'BP-STATUS-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => '2025-02-10',
        ])->assertRedirect(route('equipos.index'));

        $equipo = Equipo::query()->firstOrFail();

        $this->assertNotSame(0, (int) $equipo->equipo_status_id);
        $this->assertDatabaseHas('equipo_statuses', [
            'id' => $equipo->equipo_status_id,
            'code' => 'OPERATIVA',
        ]);
    }

    public function test_creacion_rechaza_estado_invalido(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Invalido']);
        $service = Service::create(['nombre' => 'Servicio Invalido', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Invalida', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Monitor multiparametrico']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)->post(route('equipos.store'), [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Philips',
            'modelo' => 'V60',
            'numero_serie' => 'SER-INVALIDO-1',
            'bien_patrimonial' => 'BP-INVALIDO-1',
            'estado' => 'prestamo',
            'fecha_ingreso' => '2025-02-10',
        ])->assertSessionHasErrors(['estado']);

        $this->assertDatabaseMissing('equipos', [
            'numero_serie' => 'SER-INVALIDO-1',
        ]);
    }


    public function test_creacion_permite_mac_address_y_codigo_interno_opcionales(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital MAC']);
        $service = Service::create(['nombre' => 'Ingenieria Clinica', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Deposito BIOMED', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'CPU']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($superadmin)->post(route('equipos.store'), [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Dell',
            'modelo' => 'Optiplex',
            'numero_serie' => 'SER-MAC-001',
            'bien_patrimonial' => 'BP-MAC-001',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'codigo_interno' => 'CI-MAC-001',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => '2025-02-11',
        ])->assertRedirect(route('equipos.index'));

        $this->assertDatabaseHas('equipos', [
            'numero_serie' => 'SER-MAC-001',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'codigo_interno' => 'CI-MAC-001',
        ]);
    }

    public function test_uuid_se_genera_automaticamente_al_crear_equipo(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital UUID']);
        $service = Service::create(['nombre' => 'Servicio UUID', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina UUID', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Ecografo']);

        $equipo = Equipo::create([
            'tipo' => $tipoEquipo->nombre,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'GE',
            'modelo' => 'Logiq',
            'numero_serie' => 'SER-UUID-1',
            'bien_patrimonial' => 'BP-UUID-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $this->assertNotNull($equipo->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F-]{36}$/', (string) $equipo->uuid);
    }

    public function test_ficha_publica_se_visualiza_sin_login_y_muestra_solo_datos_seguros(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Publico']);
        $service = Service::create(['nombre' => 'Servicio Publico', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Publica', 'service_id' => $service->id]);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Respirador']);

        $user = User::create([
            'name' => 'Usuario Interno',
            'email' => uniqid('public_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $equipo = Equipo::create([
            'tipo' => $tipoEquipo->nombre,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Philips',
            'modelo' => 'V680',
            'numero_serie' => 'SER-PUBLICO-1',
            'bien_patrimonial' => 'BP-PUBLICO-1',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);

        $acta = Acta::create([
            'institution_id' => $institution->id,
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Receptor Publico',
            'observaciones' => 'Acta publica',
            'created_by' => $user->id,
        ]);

        $acta->equipos()->attach($equipo->id, ['cantidad' => 1]);

        EquipoHistorial::create([
            'equipo_id' => $equipo->id,
            'usuario_id' => $user->id,
            'tipo_evento' => Acta::TIPO_ENTREGA,
            'acta_id' => $acta->id,
            'estado_anterior' => Equipo::ESTADO_OPERATIVO,
            'estado_nuevo' => Equipo::ESTADO_OPERATIVO,
            'institucion_anterior' => $institution->id,
            'institucion_nueva' => $institution->id,
            'servicio_anterior' => $service->id,
            'servicio_nuevo' => $service->id,
            'oficina_anterior' => $office->id,
            'oficina_nueva' => $office->id,
            'fecha' => now(),
            'observaciones' => 'Evento publico',
        ]);

        $this->get(route('equipos.public.show', ['uuid' => $equipo->uuid]))
            ->assertOk()
            ->assertSee('Ficha publica del equipo')
            ->assertSee('Respirador')
            ->assertSee('SER-PUBLICO-1')
            ->assertSee('Hospital Publico')
            ->assertSee($acta->codigo)
            ->assertDontSee($user->email);
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

    private function crearEquipo(Office $office, TipoEquipo $tipoEquipo, string $serie = 'SER-01', string $bien = 'BP-01'): Equipo
    {
        return Equipo::create([
            'tipo' => $tipoEquipo->nombre,
            'tipo_equipo_id' => $tipoEquipo->id,
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

