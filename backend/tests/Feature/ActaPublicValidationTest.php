<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActaPublicValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_acta_publica_se_consulta_por_uuid_sin_exponer_datos_sensibles_del_receptor(): void
    {
        [$user, $institution, $service, $office] = $this->crearEscenarioBase();

        $acta = Acta::query()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'service_origen_id' => $service->id,
            'office_origen_id' => $office->id,
            'tipo' => Acta::TIPO_PRESTAMO,
            'fecha' => now()->toDateString(),
            'receptor_nombre' => 'Carla Gomez',
            'receptor_dni' => '30111222',
            'receptor_cargo' => 'Administrativa',
            'receptor_dependencia' => 'Mesa de entradas',
            'observaciones' => 'Observacion interna que no debe publicarse',
            'created_by' => $user->id,
            'status' => Acta::STATUS_ACTIVA,
            'evento_payload' => [
                'institution_id' => $institution->id,
                'institution_name' => $institution->nombre,
                'origen_multiple' => false,
                'instituciones_origen_ids' => [$institution->id],
                'origenes_por_equipo' => [],
            ],
        ]);

        $equipo = $this->crearEquipo($office);

        $acta->equipos()->attach($equipo->id, [
            'cantidad' => 1,
            'institucion_origen_id' => $institution->id,
            'institucion_origen_nombre' => $institution->nombre,
            'servicio_origen_id' => $service->id,
            'servicio_origen_nombre' => $service->nombre,
            'oficina_origen_id' => $office->id,
            'oficina_origen_nombre' => $office->nombre,
        ]);

        $response = $this->get(route('actas.public.show', ['uuid' => $acta->uuid]));

        $response->assertOk()
            ->assertSee($acta->codigo)
            ->assertSee('Validacion publica de acta')
            ->assertSee($equipo->codigo_interno)
            ->assertSee(route('equipos.public.show', ['uuid' => $equipo->uuid]))
            ->assertDontSee('Carla Gomez')
            ->assertDontSee('30111222')
            ->assertDontSee('Administrativa')
            ->assertDontSee('Mesa de entradas')
            ->assertDontSee('Observacion interna que no debe publicarse');
    }

    private function crearEscenarioBase(): array
    {
        $institution = Institution::create(['nombre' => 'Hospital Publico']);
        $service = Service::create(['nombre' => 'Servicio Clinico', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Clinica', 'service_id' => $service->id]);

        $user = User::create([
            'name' => 'Admin Publico',
            'email' => uniqid('admin_publico_', true).'@test.com',
            'password' => '123456',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        return [$user, $institution, $service, $office];
    }

    private function crearEquipo(Office $office): Equipo
    {
        $tipo = TipoEquipo::firstOrCreate(['nombre' => 'Notebook']);

        return Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude 5420',
            'numero_serie' => 'SERIE-PUBLICA',
            'bien_patrimonial' => 'BP-PUBLICA',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
