<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\RecepcionTecnica;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecepcionTecnicaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_modelo_recepcion_tecnica_usa_el_nombre_de_tabla_canonico(): void
    {
        $this->assertSame('recepciones_tecnicas', (new RecepcionTecnica())->getTable());
    }

    public function test_admin_puede_registrar_ingreso_tecnico_para_equipo_existente(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($office, Equipo::ESTADO_OPERATIVO, 'EXIST');

        $response = $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->post(route('mesa-tecnica.recepciones-tecnicas.store'), [
                'modo_equipo' => 'existente',
                'fecha_recepcion' => now()->toDateString(),
                'sector_receptor' => 'Mesa Tecnica',
                'equipo_id' => $equipo->id,
                'procedencia_institution_id' => $institution->id,
                'procedencia_service_id' => $service->id,
                'procedencia_office_id' => $office->id,
                'persona_nombre' => 'Juan Perez',
                'persona_documento' => '30111222',
                'persona_telefono' => '3804123456',
                'persona_area' => 'Laboratorio',
                'persona_institucion' => 'Hospital A',
                'persona_relacion_equipo' => 'usuario',
                'falla_motivo' => 'No enciende',
                'descripcion_falla' => 'Se apaga al iniciar',
                'accesorios_entregados' => 'Fuente de alimentacion',
                'estado_fisico_inicial' => 'Sin golpes visibles',
                'observaciones_recepcion' => 'Prioridad alta',
                'observaciones_internas' => 'Revisar fuente',
            ]);

        $recepcion = RecepcionTecnica::query()->firstOrFail();

        $response->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHas('status', 'Recepcion tecnica registrada correctamente.');

        $this->assertMatchesRegularExpression('/^GA-RT-\d{9}$/', $recepcion->codigo);
        $this->assertSame(RecepcionTecnica::ESTADO_RECIBIDO, $recepcion->estado);
        $this->assertSame($equipo->id, $recepcion->equipo_id);
        $this->assertNull($recepcion->equipo_creado_id);
        $this->assertSame($equipo->codigo_interno, $recepcion->codigo_interno_equipo);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'recepcion_tecnica_creada',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'recepcion_tecnica_equipo_vinculado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
        ]);

        $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->get(route('mesa-tecnica.recepciones-tecnicas.index', ['search' => $recepcion->codigo]))
            ->assertOk()
            ->assertSee($recepcion->codigo)
            ->assertSee('Juan Perez');
    }

    public function test_admin_puede_registrar_ingreso_tecnico_e_incorporar_equipo_nuevo_en_el_mismo_flujo(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase('B');
        $tipo = TipoEquipo::create(['nombre' => 'Impresora']);

        $response = $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->post(route('mesa-tecnica.recepciones-tecnicas.store'), [
                'modo_equipo' => 'nuevo',
                'fecha_recepcion' => now()->toDateString(),
                'sector_receptor' => 'Mesa Tecnica',
                'referencia_equipo' => 'Impresora de admision sin codigo',
                'tipo_equipo_texto' => 'Impresora',
                'marca' => 'HP',
                'modelo' => 'LaserJet Pro',
                'numero_serie' => 'SER-RT-NUEVO-01',
                'bien_patrimonial' => 'BP-RT-NUEVO-01',
                'procedencia_hospital' => 'Hospital B',
                'procedencia_libre' => 'Mesa de entrada',
                'persona_nombre' => 'Maria Gomez',
                'persona_documento' => '28999888',
                'persona_telefono' => '3804555666',
                'persona_area' => 'Admisiones',
                'persona_institucion' => 'Hospital B',
                'persona_relacion_equipo' => 'tecnico',
                'falla_motivo' => 'Atasco de papel',
                'descripcion_falla' => 'No toma hojas desde la bandeja principal',
                'accesorios_entregados' => 'Cable USB y fuente',
                'estado_fisico_inicial' => 'Carcasa con desgaste menor',
                'observaciones_recepcion' => 'Equipo recibido con insumos',
                'observaciones_internas' => 'Validar rodillos',
                'incorporar_equipo' => '1',
                'institution_id' => $institution->id,
                'service_id' => $service->id,
                'office_id' => $office->id,
                'tipo_equipo_id' => $tipo->id,
                'estado' => Equipo::ESTADO_OPERATIVO,
                'fecha_ingreso' => now()->toDateString(),
            ]);

        $recepcion = RecepcionTecnica::query()->firstOrFail();
        $equipo = Equipo::query()->findOrFail((int) $recepcion->equipo_creado_id);

        $response->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHas(
                'status',
                'Recepcion tecnica registrada correctamente. El equipo fue incorporado al sistema y quedo vinculado al ticket de ingreso.'
            );

        $this->assertNull($recepcion->equipo_id);
        $this->assertNotNull($recepcion->equipo_creado_id);
        $this->assertSame('SER-RT-NUEVO-01', $equipo->numero_serie);
        $this->assertSame('BP-RT-NUEVO-01', $equipo->bien_patrimonial);
        $this->assertSame($office->id, $equipo->oficina_id);
        $this->assertSame($equipo->codigo_interno, $recepcion->codigo_interno_equipo);

        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'user_id' => $admin->id,
            'tipo_movimiento' => 'ingreso',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'recepcion_tecnica_equipo_incorporado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
        ]);
    }

    public function test_puede_vincular_posteriormente_un_equipo_existente_desde_el_ticket(): void
    {
        [$admin, $institution, $service, $office] = $this->crearEscenarioBase('C');
        $equipo = $this->crearEquipo($office, Equipo::ESTADO_OPERATIVO, 'POST');
        $recepcion = $this->crearRecepcionTecnica($institution, $admin, [
            'referencia_equipo' => 'CPU sin alta previa',
            'tipo_equipo_texto' => 'CPU',
            'marca' => 'Lenovo',
            'modelo' => 'ThinkCentre',
            'numero_serie' => 'SER-POST-TICKET',
            'bien_patrimonial' => 'BP-POST-TICKET',
            'procedencia_institution_id' => $institution->id,
            'procedencia_service_id' => $service->id,
            'procedencia_office_id' => $office->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->post(route('mesa-tecnica.recepciones-tecnicas.incorporate.store', $recepcion), [
                'modo_incorporacion' => 'existente',
                'equipo_id' => $equipo->id,
            ]);

        $response->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHas('status', 'El equipo existente quedo vinculado correctamente al ticket de ingreso.');

        $recepcion->refresh();

        $this->assertSame($equipo->id, $recepcion->equipo_id);
        $this->assertNull($recepcion->equipo_creado_id);
        $this->assertSame($equipo->codigo_interno, $recepcion->codigo_interno_equipo);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'recepcion_tecnica_equipo_vinculado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
        ]);
    }

    public function test_no_se_puede_anular_una_recepcion_tecnica_ya_entregada(): void
    {
        [$admin, $institution] = $this->crearEscenarioBase('D');
        $recepcion = $this->crearRecepcionTecnica($institution, $admin);

        $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->patch(route('mesa-tecnica.recepciones-tecnicas.status.update', $recepcion), [
                'estado' => RecepcionTecnica::ESTADO_ENTREGADO,
            ])
            ->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHas('status', 'Estado del ingreso tecnico actualizado correctamente.');

        $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->from(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->patch(route('mesa-tecnica.recepciones-tecnicas.status.update', $recepcion), [
                'estado' => RecepcionTecnica::ESTADO_ANULADO,
                'motivo_anulacion' => 'Carga incorrecta',
            ])
            ->assertRedirect(route('mesa-tecnica.recepciones-tecnicas.show', $recepcion))
            ->assertSessionHasErrors([
                'estado' => 'No se pudo anular la recepcion tecnica porque ya fue marcada como entregada.',
            ]);

        $this->assertSame(RecepcionTecnica::ESTADO_ENTREGADO, $recepcion->fresh()->estado);
    }

    public function test_impresion_y_reimpresion_actualizan_trazabilidad_del_ticket(): void
    {
        [$admin, $institution] = $this->crearEscenarioBase('E');
        $recepcion = $this->crearRecepcionTecnica($institution, $admin, [
            'persona_nombre' => 'Rosa Diaz',
            'falla_motivo' => 'No imprime',
        ]);

        $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->get(route('mesa-tecnica.recepciones-tecnicas.print', $recepcion))
            ->assertOk()
            ->assertSee($recepcion->codigo)
            ->assertSee('Copia para quien entrega')
            ->assertSee('Copia para mesa tecnica');

        $recepcion->refresh();
        $this->assertSame(1, (int) $recepcion->print_count);

        $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $institution->id])
            ->get(route('mesa-tecnica.recepciones-tecnicas.print', $recepcion))
            ->assertOk();

        $recepcion->refresh();
        $this->assertSame(2, (int) $recepcion->print_count);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'recepcion_tecnica_impresa',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'recepcion_tecnica_reimpresa',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcion->id,
        ]);
    }

    public function test_seguimiento_publico_muestra_datos_generales_y_oculta_datos_sensibles(): void
    {
        [$admin, $institution] = $this->crearEscenarioBase('F');
        $recepcion = $this->crearRecepcionTecnica($institution, $admin, [
            'persona_nombre' => 'Lucia Torres',
            'persona_documento' => '33444555',
            'persona_telefono' => '3804999888',
            'observaciones_internas' => 'Equipo con antecedentes de reparacion',
            'falla_motivo' => 'No carga sistema operativo',
            'procedencia_hospital' => 'Hospital F',
        ]);

        $this->get(route('mesa-tecnica.recepciones-tecnicas.public.show', $recepcion->uuid))
            ->assertOk()
            ->assertSee('Seguimiento publico')
            ->assertSee($recepcion->codigo)
            ->assertSee('No carga sistema operativo')
            ->assertSee('Hospital F')
            ->assertDontSee('33444555')
            ->assertDontSee('3804999888')
            ->assertDontSee('Equipo con antecedentes de reparacion');
    }

    private function crearEscenarioBase(string $suffix = 'A'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $service = Service::create(['nombre' => 'Servicio '.$suffix, 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina '.$suffix, 'service_id' => $service->id]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('recepcion_admin_').$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $service, $office];
    }

    private function crearEquipo(Office $office, string $estado, string $suffix = 'A'): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude '.$suffix,
            'numero_serie' => 'SER-'.$suffix.'-'.uniqid(),
            'bien_patrimonial' => 'BP-'.$suffix.'-'.uniqid(),
            'estado' => $estado,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function crearRecepcionTecnica(Institution $institution, User $user, array $attributes = []): RecepcionTecnica
    {
        return RecepcionTecnica::query()->create(array_merge([
            'institution_id' => $institution->id,
            'created_by' => $user->id,
            'fecha_recepcion' => now()->toDateString(),
            'estado' => RecepcionTecnica::ESTADO_RECIBIDO,
            'status_changed_at' => now(),
            'sector_receptor' => 'Mesa Tecnica',
            'referencia_equipo' => 'Equipo de prueba',
            'tipo_equipo_texto' => 'Notebook',
            'marca' => 'Lenovo',
            'modelo' => 'ThinkPad',
            'numero_serie' => 'SER-REC-'.uniqid(),
            'bien_patrimonial' => 'BP-REC-'.uniqid(),
            'procedencia_hospital' => 'Hospital de origen',
            'persona_nombre' => 'Pedro Ruiz',
            'persona_documento' => '30123123',
            'persona_telefono' => '3804000000',
            'persona_area' => 'Clinica Medica',
            'persona_institucion' => $institution->nombre,
            'persona_relacion_equipo' => 'usuario',
            'falla_motivo' => 'No responde',
            'descripcion_falla' => 'Descripcion inicial',
            'accesorios_entregados' => 'Cargador',
            'estado_fisico_inicial' => 'Sin detalle',
            'observaciones_recepcion' => 'Observacion visible',
            'observaciones_internas' => 'Observacion interna',
        ], $attributes));
    }
}
