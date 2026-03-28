<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use App\Services\ActaTraceabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ActaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_acta_entrega_y_actualiza_equipo_historial_movimiento_y_pdf(): void
    {
        Storage::fake();

        [$admin, $instA, , $officeA] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');
        $admin->permittedInstitutions()->sync([$instB->id]);

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $response = $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $instB->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'receptor_nombre' => 'Ana Perez',
            'receptor_dni' => '12345678',
            'receptor_cargo' => 'Enfermeria',
            'observaciones' => 'Entrega operativa',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1, 'accesorios' => 'Fuente'],
            ],
        ]);

        $response->assertRedirect();

        $acta = Acta::query()->firstOrFail();
        $equipo->refresh();

        $this->assertSame($officeB->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);

        $this->assertDatabaseHas('equipo_historial', [
            'equipo_id' => $equipo->id,
            'acta_id' => $acta->id,
            'tipo_evento' => Acta::TIPO_ENTREGA,
            'estado_nuevo' => Equipo::ESTADO_OPERATIVO,
            'institucion_anterior' => $instA->id,
            'institucion_nueva' => $instB->id,
        ]);

        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'tipo_movimiento' => Movimiento::TIPO_TRASLADO,
            'acta_id' => $acta->id,
            'institucion_origen_id' => $instA->id,
            'institucion_destino_id' => $instB->id,
            'oficina_destino_id' => $officeB->id,
        ]);

        $this->assertDatabaseHas('acta_equipo', [
            'acta_id' => $acta->id,
            'equipo_id' => $equipo->id,
            'institucion_origen_id' => $instA->id,
            'servicio_origen_id' => $officeA->service_id,
            'oficina_origen_id' => $officeA->id,
        ]);

        $document = Document::query()->where('documentable_type', Acta::class)->first();
        $this->assertNotNull($document);
        Storage::assertExists($document->file_path);
        $this->assertDatabaseHas('equipo_documentos', [
            'equipo_id' => $equipo->id,
            'document_id' => $document->id,
            'origen_tipo' => 'acta',
            'origen_id' => $acta->id,
        ]);
    }

    public function test_acta_rechaza_creacion_si_los_equipos_pertenecen_a_otra_institucion_habilitada_distinta_de_la_activa(): void
    {
        Storage::fake();

        [$adminA, $instA] = $this->crearEscenarioBase('A');
        [, $instB, , $officeB] = $this->crearEscenarioBase('B');

        $adminA->permittedInstitutions()->sync([$instB->id]);
        $equipoB = $this->crearEquipo($officeB, Equipo::ESTADO_OPERATIVO, 'B');

        $response = $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Prestamo fuera de contexto',
            'receptor_dni' => '20111222',
            'equipos' => [
                ['equipo_id' => $equipoB->id, 'cantidad' => 1, 'accesorios' => 'Fuente B'],
            ],
        ]);

        $response->assertSessionHasErrors('equipos');
        $this->assertStringContainsString($instA->nombre, session('errors')->first('equipos'));
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_acta_prestamo_actualiza_estado_a_prestado_sin_mover_ubicacion(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Carlos Gomez',
            'receptor_dni' => '22333444',
            'receptor_cargo' => 'Chofer',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_PRESTADO, $equipo->estado);
        $this->assertDatabaseHas('equipo_historial', [
            'equipo_id' => $equipo->id,
            'tipo_evento' => Acta::TIPO_PRESTAMO,
            'estado_nuevo' => Equipo::ESTADO_PRESTADO,
            'oficina_anterior' => $officeA->id,
            'oficina_nueva' => $officeA->id,
        ]);
        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'tipo_movimiento' => Movimiento::TIPO_PRESTAMO,
        ]);
    }

    public function test_acta_prestamo_resuelve_codigo_legacy_prestada_si_existe_historico(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        DB::table('equipo_statuses')->where('code', 'PRESTADO')->delete();

        $legacyPrestadaId = DB::table('equipo_statuses')->insertGetId([
            'code' => 'PRESTADA',
            'name' => 'Prestada Legacy',
            'color' => 'blue',
            'is_terminal' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Carlos Gomez',
            'receptor_dni' => '22333444',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame(Equipo::ESTADO_PRESTADO, $equipo->estado);
        $this->assertSame($legacyPrestadaId, (int) $equipo->equipo_status_id);
    }

    public function test_acta_traslado_actualiza_ubicacion_dentro_de_la_misma_institucion(): void
    {
        Storage::fake();

        [$admin, $inst, , $officeA, $serviceB, $officeB] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_TRASLADO,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $inst->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'observaciones' => 'Traslado interno',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame($officeB->id, $equipo->oficina_id);
        $this->assertDatabaseHas('equipo_historial', [
            'equipo_id' => $equipo->id,
            'tipo_evento' => Acta::TIPO_TRASLADO,
            'institucion_anterior' => $inst->id,
            'institucion_nueva' => $inst->id,
            'oficina_anterior' => $officeA->id,
            'oficina_nueva' => $officeB->id,
        ]);
    }

    public function test_traslado_permite_mover_entre_instituciones_con_destino_unico(): void
    {
        Storage::fake();

        [$adminA, $instA, , $officeA] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');
        $adminA->permittedInstitutions()->sync([$instB->id]);

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_TRASLADO,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $instB->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();
        $this->assertSame($officeB->id, $equipo->oficina_id);
    }

    public function test_traslado_rechaza_servicio_fuera_de_la_institucion_destino_indicada(): void
    {
        Storage::fake();

        [$adminA, $instA, , $officeA] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_TRASLADO,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $instA->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('service_destino_id');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_acta_baja_actualiza_estado(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_BAJA,
            'fecha' => now()->toDateString(),
            'motivo_baja' => 'Obsolescencia',
            'observaciones' => 'Sin reparacion viable',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame(Equipo::ESTADO_BAJA, $equipo->estado);
    }

    public function test_acta_rechaza_cantidad_mayor_a_uno(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $officeA->service->institution_id,
            'service_destino_id' => $officeA->service_id,
            'office_destino_id' => $officeA->id,
            'receptor_nombre' => 'Responsable',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasErrors('equipos.0.cantidad');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
    }

    public function test_acta_rechaza_equipo_duplicado_en_la_misma_acta(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Persona prueba',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('equipos.1.equipo_id');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
    }
    public function test_acta_mantenimiento_se_rechaza_por_no_ser_un_tipo_patrimonial(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_MANTENIMIENTO,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Ingreso al taller',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('tipo');

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_acta_devolucion_vuelve_estado_operativo(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_PRESTADO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_DEVOLUCION,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retorna a inventario',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $equipo->refresh();

        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
        $this->assertSame($officeA->id, $equipo->oficina_id);
    }

    public function test_traceability_service_rechaza_payload_con_cantidad_distinta_de_uno(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        try {
            app(ActaTraceabilityService::class)->crear($admin, [
                'tipo' => Acta::TIPO_PRESTAMO,
                'fecha' => now()->toDateString(),
                'receptor_nombre' => 'Persona prueba',
                'equipos' => [
                    ['equipo_id' => $equipo->id, 'cantidad' => 3],
                ],
            ]);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Cada equipo del acta debe registrarse con cantidad fija 1.'],
                $exception->errors()['equipos.0.cantidad'] ?? null
            );
        }

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
    }
    public function test_traceability_service_rechaza_payload_con_equipo_duplicado(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        try {
            app(ActaTraceabilityService::class)->crear($admin, [
                'tipo' => Acta::TIPO_PRESTAMO,
                'fecha' => now()->toDateString(),
                'receptor_nombre' => 'Persona prueba',
                'equipos' => [
                    ['equipo_id' => $equipo->id, 'cantidad' => 1],
                    ['equipo_id' => $equipo->id, 'cantidad' => 1],
                ],
            ]);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['No puede repetir el mismo equipo dentro de la misma acta.'],
                $exception->errors()['equipos'] ?? null
            );
        }

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
    }

    public function test_traceability_service_hace_rollback_si_falla_la_persistencia_del_acta(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $dispatcher = Acta::getEventDispatcher();
        Acta::flushEventListeners();
        Acta::creating(function (): void {
            throw new RuntimeException('fallo_acta');
        });

        try {
            app(ActaTraceabilityService::class)->crear($admin, [
                'tipo' => Acta::TIPO_PRESTAMO,
                'fecha' => now()->toDateString(),
                'receptor_nombre' => 'Persona prueba',
                'equipos' => [
                    ['equipo_id' => $equipo->id, 'cantidad' => 1],
                ],
            ]);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('fallo_acta', $exception->getMessage());
        } finally {
            Acta::flushEventListeners();
            Acta::setEventDispatcher($dispatcher);
        }

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_traceability_service_hace_rollback_si_falla_el_movimiento_derivado(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $dispatcher = Movimiento::getEventDispatcher();
        Movimiento::flushEventListeners();
        Movimiento::creating(function (): void {
            throw new RuntimeException('fallo_movimiento');
        });

        try {
            app(ActaTraceabilityService::class)->crear($admin, [
                'tipo' => Acta::TIPO_PRESTAMO,
                'fecha' => now()->toDateString(),
                'receptor_nombre' => 'Persona prueba',
                'equipos' => [
                    ['equipo_id' => $equipo->id, 'cantidad' => 1],
                ],
            ]);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('fallo_movimiento', $exception->getMessage());
        } finally {
            Movimiento::flushEventListeners();
            Movimiento::setEventDispatcher($dispatcher);
        }

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertSame(Equipo::ESTADO_OPERATIVO, $equipo->estado);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('acta_equipo', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }
    public function test_admin_bloquea_solo_equipo_fuera_de_alcance_con_mensaje_claro(): void
    {
        Storage::fake();

        [$adminA, , , $officeA] = $this->crearEscenarioBase('A');
        [, , , $officeB] = $this->crearEscenarioBase('B');

        $equipoA = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO, 'A');
        $equipoB = $this->crearEquipo($officeB, Equipo::ESTADO_OPERATIVO, 'B');

        $response = $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $officeA->service->institution_id,
            'service_destino_id' => $officeA->service_id,
            'office_destino_id' => $officeA->id,
            'receptor_nombre' => 'Prueba',
            'receptor_dni' => '99888777',
            'equipos' => [
                ['equipo_id' => $equipoA->id, 'cantidad' => 1],
                ['equipo_id' => $equipoB->id, 'cantidad' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('equipos');
        $error = session('errors')->first('equipos');
        $this->assertStringContainsString($equipoB->numero_serie, $error);

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_acta_rechaza_institucion_destino_fuera_del_alcance_del_usuario(): void
    {
        Storage::fake();

        [$adminA, , , $officeA] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($adminA)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $instB->id,
            'service_destino_id' => $serviceB->id,
            'office_destino_id' => $officeB->id,
            'receptor_nombre' => 'Destino no permitido',
            'receptor_dni' => '10020030',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('institution_destino_id');

        $equipo->refresh();

        $this->assertSame($officeA->id, $equipo->oficina_id);
        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseCount('equipo_historial', 0);
    }

    public function test_tecnico_puede_acceder_a_create_y_store_para_actas_patrimoniales(): void
    {
        Storage::fake();

        [, $institution, , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $tecnico = User::create([
            'name' => 'Tecnico Actas',
            'email' => uniqid('tecnico_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_TECNICO,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $this->actingAs($tecnico)
            ->get(route('actas.create'))
            ->assertOk();

        $this->actingAs($tecnico)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Tecnico habilitado',
            'receptor_dni' => '30111222',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('actas', [
            'tipo' => Acta::TIPO_PRESTAMO,
            'created_by' => $tecnico->id,
        ]);
        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'tipo_movimiento' => Movimiento::TIPO_PRESTAMO,
        ]);
    }

    public function test_create_expone_solo_la_institucion_activa_como_origen_y_mantiene_destinos_accesibles(): void
    {
        [$admin, $instA] = $this->crearEscenarioBase('A');
        [, $instB] = $this->crearEscenarioBase('B');

        $admin->permittedInstitutions()->sync([$instB->id]);

        $response = $this->actingAs($admin)->get(route('actas.create'));

        $response->assertOk()
            ->assertViewHas('originInstitutions', function ($institutions) use ($instA): bool {
                return collect($institutions)->pluck('id')->values()->all() === [$instA->id];
            })
            ->assertViewHas('destinationInstitutions', function ($institutions) use ($instA, $instB): bool {
                return collect($institutions)->pluck('id')->sort()->values()->all() === [$instA->id, $instB->id];
            });
    }

    public function test_acta_recien_creada_se_puede_visualizar_inmediatamente_para_todos_los_tipos_patrimoniales(): void
    {
        Storage::fake();

        [$admin, $instA, , $officeA, $serviceA2, $officeA2] = $this->crearEscenarioBase('A');
        [, $instB, , , $serviceB, $officeB] = $this->crearEscenarioBase('B');

        $admin->permittedInstitutions()->sync([$instB->id]);

        foreach (Acta::creatableTypes() as $tipo) {
            $equipo = $this->crearEquipo(
                $officeA,
                $tipo === Acta::TIPO_DEVOLUCION ? Equipo::ESTADO_PRESTADO : Equipo::ESTADO_OPERATIVO,
                strtoupper($tipo)
            );

            $payload = match ($tipo) {
                Acta::TIPO_ENTREGA => [
                    'tipo' => $tipo,
                    'fecha' => now()->toDateString(),
                    'institution_destino_id' => $instB->id,
                    'service_destino_id' => $serviceB->id,
                    'office_destino_id' => $officeB->id,
                    'receptor_nombre' => 'Destino autorizado',
                    'receptor_dni' => '10101010',
                    'equipos' => [
                        ['equipo_id' => $equipo->id, 'cantidad' => 1],
                    ],
                ],
                Acta::TIPO_TRASLADO => [
                    'tipo' => $tipo,
                    'fecha' => now()->toDateString(),
                    'institution_destino_id' => $instA->id,
                    'service_destino_id' => $serviceA2->id,
                    'office_destino_id' => $officeA2->id,
                    'equipos' => [
                        ['equipo_id' => $equipo->id, 'cantidad' => 1],
                    ],
                ],
                Acta::TIPO_PRESTAMO => [
                    'tipo' => $tipo,
                    'fecha' => now()->toDateString(),
                    'receptor_nombre' => 'Prestamo autorizado',
                    'receptor_dni' => '20202020',
                    'equipos' => [
                        ['equipo_id' => $equipo->id, 'cantidad' => 1],
                    ],
                ],
                Acta::TIPO_BAJA => [
                    'tipo' => $tipo,
                    'fecha' => now()->toDateString(),
                    'motivo_baja' => 'Fin de vida util',
                    'equipos' => [
                        ['equipo_id' => $equipo->id, 'cantidad' => 1],
                    ],
                ],
                Acta::TIPO_DEVOLUCION => [
                    'tipo' => $tipo,
                    'fecha' => now()->toDateString(),
                    'observaciones' => 'Devuelto al inventario activo',
                    'equipos' => [
                        ['equipo_id' => $equipo->id, 'cantidad' => 1],
                    ],
                ],
            };

            $response = $this->actingAs($admin)->post(route('actas.store'), $payload);
            $acta = Acta::query()->latest('id')->firstOrFail();

            $response->assertRedirect(route('actas.show', $acta));
            $this->assertSame($instA->id, $acta->institution_id);

            $this->actingAs($admin)
                ->get(route('actas.show', $acta))
                ->assertOk();
        }
    }

    public function test_show_de_acta_creada_mantiene_restriccion_real_si_el_usuario_cambia_la_institucion_activa(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase('A');
        [, $instB] = $this->crearEscenarioBase('B');
        $admin->permittedInstitutions()->sync([$instB->id]);

        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Cambio de contexto',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('actas.show', $acta))
            ->assertOk();

        $this->actingAs($admin)
            ->withSession([ActiveInstitutionContext::SESSION_KEY => $instB->id])
            ->get(route('actas.show', $acta))
            ->assertForbidden();
    }

    public function test_viewer_no_puede_crear_actas(): void
    {
        Storage::fake();

        [, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $viewer = User::create([
            'name' => 'Viewer',
            'email' => uniqid('viewer_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $officeA->service->institution_id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)
            ->get(route('actas.create'))
            ->assertForbidden()
            ->assertSee('No tiene permisos para generar actas.');

        $this->actingAs($viewer)
            ->from(route('actas.index'))
            ->post(route('actas.store'), [
            'tipo' => Acta::TIPO_ENTREGA,
            'fecha' => now()->toDateString(),
            'institution_destino_id' => $officeA->service->institution_id,
            'service_destino_id' => $officeA->service_id,
            'office_destino_id' => $officeA->id,
            'receptor_nombre' => 'No permitido',
            'receptor_dni' => '1111',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect(route('actas.index'))
            ->assertSessionHas('error', 'No tiene permisos para generar actas.');

        $this->assertDatabaseCount('actas', 0);
        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_admin_puede_anular_acta_y_registra_auditoria(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Persona prueba',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $this->actingAs($admin)->post(route('actas.anular', $acta), [
            'motivo_anulacion' => 'Error administrativo en la carga',
        ])->assertRedirect(route('actas.show', $acta));

        $this->assertDatabaseHas('actas', [
            'id' => $acta->id,
            'status' => Acta::STATUS_ANULADA,
            'anulada_por' => $admin->id,
            'motivo_anulacion' => 'Error administrativo en la carga',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'acta_anulada',
            'entity_type' => 'acta',
            'entity_id' => $acta->id,
            'module' => 'actas',
        ]);

        $log = AuditLog::query()->where('action', 'acta_anulada')->first();
        $this->assertSame('Anulada', $log?->after['status'] ?? null);
    }

    public function test_anulacion_requiere_motivo(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Persona prueba',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $this->actingAs($admin)->post(route('actas.anular', $acta), [
            'motivo_anulacion' => '',
        ])->assertSessionHasErrors('motivo_anulacion');

        $this->assertDatabaseHas('actas', [
            'id' => $acta->id,
            'status' => Acta::STATUS_ACTIVA,
        ]);
    }

    public function test_viewer_no_puede_anular_acta(): void
    {
        Storage::fake();

        [$admin, , , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Persona prueba',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $viewer = User::create([
            'name' => 'Viewer',
            'email' => uniqid('viewer_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_VIEWER,
            'institution_id' => $officeA->service->institution_id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->post(route('actas.anular', $acta), [
            'motivo_anulacion' => 'No autorizado',
        ])->assertForbidden();

        $this->assertDatabaseHas('actas', [
            'id' => $acta->id,
            'status' => Acta::STATUS_ACTIVA,
        ]);
    }

    public function test_tecnico_puede_descargar_pdf_pero_no_anular_acta(): void
    {
        Storage::fake();

        [$admin, $institution, , $officeA] = $this->crearEscenarioBase();
        $equipo = $this->crearEquipo($officeA, Equipo::ESTADO_OPERATIVO);

        $this->actingAs($admin)->post(route('actas.store'), [
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Persona prueba',
            'equipos' => [
                ['equipo_id' => $equipo->id, 'cantidad' => 1],
            ],
        ])->assertRedirect();

        $acta = Acta::query()->firstOrFail();

        $tecnico = User::create([
            'name' => 'Tecnico PDF',
            'email' => uniqid('tecnico_pdf_').'@test.com',
            'password' => '123456',
            'role' => User::ROLE_TECNICO,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $this->actingAs($tecnico)
            ->get(route('actas.download', $acta))
            ->assertOk();

        $this->actingAs($tecnico)
            ->from(route('actas.show', $acta))
            ->post(route('actas.anular', $acta), [
                'motivo_anulacion' => 'No autorizado',
            ])
            ->assertRedirect(route('actas.show', $acta))
            ->assertSessionHas('error', 'No tiene permisos para anular actas.');

        $this->assertDatabaseHas('actas', [
            'id' => $acta->id,
            'status' => Acta::STATUS_ACTIVA,
        ]);
    }

    public function test_logging_single_channel_crea_el_archivo_laravel_log(): void
    {
        $logPath = storage_path('logs/laravel.log');

        File::ensureDirectoryExists(dirname($logPath));
        File::delete($logPath);

        Log::channel('single')->warning('actas_log_test');

        $this->assertFileExists($logPath);
        $this->assertStringContainsString('actas_log_test', File::get($logPath));
    }

    public function test_listado_de_actas_aplica_busqueda_por_codigo_y_per_page_whitelist(): void
    {
        [$admin, $institution] = $this->crearEscenarioBase();

        for ($i = 1; $i <= 21; $i++) {
            Acta::create([
                'institution_id' => $institution->id,
                'tipo' => $i % 2 === 0 ? Acta::TIPO_ENTREGA : Acta::TIPO_TRASLADO,
                'fecha' => now()->subDays(21 - $i)->toDateString(),
                'receptor_nombre' => 'Receptor '.$i,
                'observaciones' => 'Observacion '.$i,
                'created_by' => $admin->id,
            ]);
        }

        $defaultResponse = $this->actingAs($admin)->get(route('actas.index'));

        $defaultResponse->assertOk();
        $this->assertSame(20, $defaultResponse->viewData('actas')->perPage());
        $this->assertCount(20, $defaultResponse->viewData('actas')->items());

        $target = Acta::query()->where('receptor_nombre', 'Receptor 21')->firstOrFail();

        $searchResponse = $this->actingAs($admin)->get(route('actas.index', ['search' => $target->codigo]));

        $searchResponse->assertOk();

        $searchPaginator = $searchResponse->viewData('actas');

        $this->assertSame(1, $searchPaginator->total());
        $this->assertSame($target->id, collect($searchPaginator->items())->first()?->id);

        $invalidResponse = $this->actingAs($admin)->get(route('actas.index', ['per_page' => 999]));

        $invalidResponse->assertOk();
        $this->assertSame(20, $invalidResponse->viewData('actas')->perPage());
    }

    private function crearEscenarioBase(string $suffix = 'A'): array
    {
        $institution = Institution::create(['nombre' => 'Hospital '.$suffix]);
        $serviceA = Service::create(['nombre' => 'Servicio '.$suffix.'-1', 'institution_id' => $institution->id]);
        $officeA = Office::create(['nombre' => 'Oficina '.$suffix.'-1', 'service_id' => $serviceA->id]);
        $serviceB = Service::create(['nombre' => 'Servicio '.$suffix.'-2', 'institution_id' => $institution->id]);
        $officeB = Office::create(['nombre' => 'Oficina '.$suffix.'-2', 'service_id' => $serviceB->id]);

        $admin = User::create([
            'name' => 'Admin '.$suffix,
            'email' => uniqid('admin_').$suffix.'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$admin, $institution, $serviceA, $officeA, $serviceB, $officeB];
    }

    private function crearEquipo(Office $office, string $estado, string $suffix = 'A'): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook '.$suffix]);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => uniqid('ser-'.$suffix.'-'),
            'bien_patrimonial' => uniqid('bp-'.$suffix.'-'),
            'estado' => $estado,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
