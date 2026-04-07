<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Mantenimiento;
use App\Models\Office;
use App\Models\RecepcionTecnica;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\RecepcionTecnicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecepcionTecnicaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_ingreso_tecnico_sin_mover_patrimonio_ni_generar_actas(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office);

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.create'))
            ->post(route('mesa-tecnica.recepciones-tecnicas.store'), $this->payloadIngresoTecnico($equipo, $institution, $service, $office))
            ->assertRedirect();

        $recepcion = RecepcionTecnica::query()->firstOrFail();

        $equipo->refresh()->load('oficina.service.institution');

        $this->assertSame($equipo->id, $recepcion->equipo_id);
        $this->assertSame(RecepcionTecnica::ESTADO_RECIBIDO, $recepcion->estado);
        $this->assertSame($admin->id, $recepcion->recibido_por);
        $this->assertNotNull($recepcion->codigo);
        $this->assertNotNull($recepcion->ingresado_at);
        $this->assertSame($office->id, $equipo->oficina_id);
        $this->assertSame($institution->id, $equipo->oficina?->service?->institution?->id);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
            'action' => 'ingreso_tecnico_creado',
            'module' => 'mesa_tecnica',
        ]);
        $this->assertDatabaseMissing('mantenimientos', [
            'recepcion_tecnica_id' => $recepcion->id,
        ]);
    }

    public function test_impide_dos_ingresos_tecnicos_abiertos_para_el_mismo_equipo(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office);

        $payload = $this->payloadIngresoTecnico($equipo, $institution, $service, $office);

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.create'))
            ->post(route('mesa-tecnica.recepciones-tecnicas.store'), $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.create'))
            ->post(route('mesa-tecnica.recepciones-tecnicas.store'), $payload)
            ->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.create'))
            ->assertSessionHasErrors([
                'equipo_id' => 'Este equipo ya tiene un ingreso tecnico abierto.',
            ]);

        $this->assertSame(
            1,
            RecepcionTecnica::query()
                ->where('equipo_id', $equipo->id)
                ->whereIn('estado', RecepcionTecnica::ESTADOS_ABIERTOS)
                ->count()
        );
    }

    public function test_cierra_ingreso_tecnico_correctamente_y_genera_historial_de_mantenimiento(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office);

        $recepcion = $this->registrarIngresoTecnico(
            $admin,
            $equipo,
            $institution,
            $service,
            $office,
            ['fecha_hora_ingreso' => now()->subHours(6)->format('Y-m-d H:i:s')]
        );

        $fechaEntrega = now()->format('Y-m-d H:i:s');

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), [
                'estado_cierre' => RecepcionTecnica::ESTADO_ENTREGADO,
                'fecha_entrega_real' => $fechaEntrega,
                'persona_retiro_nombre' => 'Chofer Hospital',
                'persona_retiro_documento' => '20111222',
                'persona_retiro_cargo' => 'Logistica',
                'diagnostico' => 'Falla en la fuente de alimentacion.',
                'accion_realizada' => 'Se reemplazo la fuente y se realizaron pruebas.',
                'solucion_aplicada' => 'Equipo reparado y validado.',
                'informe_tecnico' => 'El equipo quedo operativo luego del reemplazo.',
                'observaciones_finales' => 'Se entrega al mismo origen patrimonial.',
                'condicion_egreso' => RecepcionTecnica::CONDICION_REPARADO,
            ])
            ->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion));

        $recepcion->refresh();
        $mantenimiento = Mantenimiento::query()->where('recepcion_tecnica_id', $recepcion->id)->firstOrFail();

        $this->assertSame(RecepcionTecnica::ESTADO_ENTREGADO, $recepcion->estado);
        $this->assertSame($admin->id, $recepcion->cerrado_por);
        $this->assertNotNull($recepcion->entregada_at);
        $this->assertSame(Mantenimiento::TIPO_MESA_TECNICA, $mantenimiento->tipo);
        $this->assertSame($equipo->id, $mantenimiento->equipo_id);
        $this->assertSame($admin->id, $mantenimiento->tecnico_responsable_id);
        $this->assertSame($recepcion->diagnostico, $mantenimiento->diagnostico);
        $this->assertSame($recepcion->solucion_aplicada, $mantenimiento->solucion_aplicada);
        $this->assertSame($recepcion->informe_tecnico, $mantenimiento->informe_tecnico);
        $this->assertSame($recepcion->condicion_egreso, $mantenimiento->condicion_egreso);
        $this->assertSame($recepcion->ingresado_at?->toDateString(), $mantenimiento->fecha_ingreso_st?->toDateString());
        $this->assertSame($recepcion->entregada_at?->toDateString(), $mantenimiento->fecha_egreso_st?->toDateString());
        $this->assertNotNull($mantenimiento->duracion_minutos);
        $this->assertGreaterThan(0, (int) $mantenimiento->duracion_minutos);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
            'action' => 'ingreso_tecnico_cerrado',
            'module' => 'mesa_tecnica',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'equipo',
            'entity_id' => $equipo->id,
            'action' => 'mantenimiento_desde_ingreso_tecnico_registrado',
            'module' => 'mantenimientos',
        ]);
    }

    public function test_retorno_al_mismo_origen_cierra_el_ingreso_sin_movimiento_patrimonial(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office);

        $recepcion = $this->registrarIngresoTecnico($admin, $equipo, $institution, $service, $office);

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), [
                'estado_cierre' => RecepcionTecnica::ESTADO_ENTREGADO,
                'fecha_entrega_real' => now()->addHour()->format('Y-m-d H:i:s'),
                'persona_retiro_nombre' => 'Transportista institucional',
                'persona_retiro_documento' => '30999111',
                'persona_retiro_cargo' => 'Chofer',
                'diagnostico' => 'Disco con errores de arranque.',
                'accion_realizada' => 'Se reemplazo el disco y se reinstalo el sistema.',
                'solucion_aplicada' => 'Equipo operativo.',
                'informe_tecnico' => 'Se realizaron pruebas de encendido y funcionamiento.',
                'observaciones_finales' => 'Retorna al mismo origen.',
                'condicion_egreso' => RecepcionTecnica::CONDICION_REPARADO,
            ])
            ->assertRedirect();

        $equipo->refresh()->load('oficina.service.institution');

        $this->assertSame($office->id, $equipo->oficina_id);
        $this->assertSame($institution->id, $equipo->oficina?->service?->institution?->id);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_cierre_tecnico_no_acepta_cambio_patrimonial_y_exige_flujo_de_actas(): void
    {
        [$admin, $institutionA, $serviceA, $officeA] = $this->crearEscenarioBase('A');
        [, $institutionB, $serviceB, $officeB] = $this->crearEscenarioBase('B');
        $admin->permittedInstitutions()->sync([$institutionB->id]);

        $equipo = $this->crearEquipo($officeA, 'A');
        $recepcion = $this->registrarIngresoTecnico($admin, $equipo, $institutionA, $serviceA, $officeA);

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), [
                'estado_cierre' => RecepcionTecnica::ESTADO_ENTREGADO,
                'fecha_entrega_real' => now()->addHour()->format('Y-m-d H:i:s'),
                'persona_retiro_nombre' => 'Retiro no valido',
                'diagnostico' => 'Prueba',
                'accion_realizada' => 'Prueba',
                'solucion_aplicada' => 'Prueba',
                'informe_tecnico' => 'Prueba',
                'condicion_egreso' => RecepcionTecnica::CONDICION_REPARADO,
                'institution_destino_id' => $institutionB->id,
                'service_destino_id' => $serviceB->id,
                'office_destino_id' => $officeB->id,
            ])
            ->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHasErrors([
                'estado_cierre' => 'El cierre tecnico no cambia la ubicacion patrimonial. Si el equipo debe quedar en otro destino, use Actas.',
            ]);

        $recepcion->refresh();
        $equipo->refresh();

        $this->assertSame(RecepcionTecnica::ESTADO_RECIBIDO, $recepcion->estado);
        $this->assertNull($recepcion->entregada_at);
        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseMissing('mantenimientos', [
            'recepcion_tecnica_id' => $recepcion->id,
        ]);
    }

    public function test_no_permite_cerrar_un_ingreso_ya_cerrado(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office);
        $recepcion = $this->registrarIngresoTecnico($admin, $equipo, $institution, $service, $office);

        $payloadCierre = [
            'estado_cierre' => RecepcionTecnica::ESTADO_ENTREGADO,
            'fecha_entrega_real' => now()->addHour()->format('Y-m-d H:i:s'),
            'persona_retiro_nombre' => 'Primer retiro',
            'persona_retiro_documento' => '12312312',
            'persona_retiro_cargo' => 'Chofer',
            'diagnostico' => 'Diagnostico final',
            'accion_realizada' => 'Accion realizada',
            'solucion_aplicada' => 'Solucion aplicada',
            'informe_tecnico' => 'Informe final',
            'observaciones_finales' => 'Entrega correcta',
            'condicion_egreso' => RecepcionTecnica::CONDICION_REPARADO,
        ];

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), $payloadCierre)
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), array_merge($payloadCierre, [
                'persona_retiro_nombre' => 'Segundo intento',
            ]))
            ->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHasErrors([
                'estado_cierre' => 'Este ingreso tecnico ya se encuentra cerrado.',
            ]);
    }

    public function test_permisos_de_acceso_bloquean_a_viewer_y_a_usuarios_fuera_de_alcance(): void
    {
        [$adminA, $institutionA, $serviceA, $officeA] = $this->crearEscenarioBase('A');
        [$adminB] = $this->crearEscenarioBase('B');
        $equipo = $this->crearEquipo($officeA, 'SEC');
        $recepcion = $this->registrarIngresoTecnico($adminA, $equipo, $institutionA, $serviceA, $officeA);

        $viewer = User::create([
            'name' => 'Viewer MT',
            'email' => uniqid('viewer_mt_', true).'@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $institutionA->id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)
            ->post(route('mesa-tecnica.recepciones-tecnicas.store'), $this->payloadIngresoTecnico($equipo, $institutionA, $serviceA, $officeA))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), [
                'estado_cierre' => RecepcionTecnica::ESTADO_ENTREGADO,
                'fecha_entrega_real' => now()->addHour()->format('Y-m-d H:i:s'),
                'persona_retiro_nombre' => 'No autorizado',
                'diagnostico' => 'x',
                'accion_realizada' => 'x',
                'solucion_aplicada' => 'x',
                'informe_tecnico' => 'x',
                'condicion_egreso' => RecepcionTecnica::CONDICION_REPARADO,
            ])
            ->assertForbidden();

        $this->actingAs($adminB)
            ->get(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertForbidden();

        $this->actingAs($adminB)
            ->post(route('mesa-tecnica.recepciones-tecnicas.close', $recepcion), [
                'estado_cierre' => RecepcionTecnica::ESTADO_ENTREGADO,
                'fecha_entrega_real' => now()->addHour()->format('Y-m-d H:i:s'),
                'persona_retiro_nombre' => 'Fuera de alcance',
                'diagnostico' => 'x',
                'accion_realizada' => 'x',
                'solucion_aplicada' => 'x',
                'informe_tecnico' => 'x',
                'condicion_egreso' => RecepcionTecnica::CONDICION_REPARADO,
            ])
            ->assertForbidden();
    }

    private function registrarIngresoTecnico(
        User $user,
        Equipo $equipo,
        Institution $institution,
        Service $service,
        Office $office,
        array $overrides = []
    ): RecepcionTecnica {
        $this->actingAs($user)
            ->from(route('mesa-tecnica.recepciones-tecnicas.create'))
            ->post(
                route('mesa-tecnica.recepciones-tecnicas.store'),
                array_merge($this->payloadIngresoTecnico($equipo, $institution, $service, $office), $overrides)
            )
            ->assertRedirect();

        return RecepcionTecnica::query()->latest('id')->firstOrFail();
    }

    private function payloadIngresoTecnico(
        Equipo $equipo,
        Institution $institution,
        Service $service,
        Office $office
    ): array {
        return [
            'modo_equipo' => RecepcionTecnicaService::MODO_EQUIPO_EXISTENTE,
            'equipo_id' => $equipo->id,
            'fecha_hora_ingreso' => now()->format('Y-m-d H:i:s'),
            'sector_receptor' => 'Mesa Tecnica / Nivel Central',
            'procedencia_institution_id' => $institution->id,
            'procedencia_service_id' => $service->id,
            'procedencia_office_id' => $office->id,
            'persona_nombre' => 'Chofer Hospital',
            'persona_documento' => '20111222',
            'persona_telefono' => '2994000000',
            'persona_area' => 'Logistica',
            'persona_institucion' => $institution->nombre,
            'persona_relacion_equipo' => 'Chofer',
            'falla_motivo' => 'No enciende',
            'descripcion_falla' => 'El equipo no da video y no completa el arranque.',
            'accesorios_entregados' => 'Fuente de alimentacion',
            'estado_fisico_inicial' => 'Sin danos visibles',
            'observaciones_recepcion' => 'Recepcionado para diagnostico.',
            'observaciones_internas' => 'Pendiente de revision inicial.',
        ];
    }

    /**
     * @return array{0:User,1:Institution,2:Service,3:Office}
     */
    private function crearEscenarioBase(string $suffix = 'A'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('recepcion_admin_', true).$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $service, $office];
    }

    private function crearEquipo(Office $office, string $suffix = 'A'): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => uniqid('ser-'.$suffix.'-', true),
            'bien_patrimonial' => uniqid('bp-'.$suffix.'-', true),
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
