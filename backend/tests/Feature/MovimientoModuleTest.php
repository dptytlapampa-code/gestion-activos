<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimientoModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_crear_equipo_se_crea_movimiento_de_ingreso(): void
    {
        [$institution, $service, $office] = $this->crearUbicacion('Hospital Norte', 'Clínica', 'Consultorio 1');
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Laptop']);
        $usuario = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $payload = [
            'institution_id' => $institution->id,
            'service_id' => $service->id,
            'oficina_id' => $office->id,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => 'NS-100',
            'bien_patrimonial' => 'BP-100',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
        ];

        $this->actingAs($usuario)
            ->post(route('equipos.store'), $payload)
            ->assertRedirect(route('equipos.index'));

        $equipo = Equipo::query()->firstOrFail();
        $movimiento = Movimiento::query()->firstOrFail();

        $this->assertSame($equipo->id, $movimiento->equipo_id);
        $this->assertSame($usuario->id, $movimiento->user_id);
        $this->assertSame('ingreso', $movimiento->tipo_movimiento);
        $this->assertSame($institution->id, $movimiento->institucion_destino_id);
        $this->assertSame($service->id, $movimiento->servicio_destino_id);
        $this->assertSame($office->id, $movimiento->oficina_destino_id);
        $this->assertSame('Ingreso de equipo', $movimiento->observacion);
    }

    public function test_al_cambiar_oficina_se_crea_movimiento_de_traslado(): void
    {
        [$institutionOrigen, $serviceOrigen, $officeOrigen] = $this->crearUbicacion('Hospital Sur', 'Guardia', 'Oficina A');
        [$institutionDestino, $serviceDestino, $officeDestino] = $this->crearUbicacion('Hospital Centro', 'Laboratorio', 'Oficina B');

        $tipoEquipo = TipoEquipo::create(['nombre' => 'Monitor']);
        $equipo = $this->crearEquipo($officeOrigen, $tipoEquipo);
        Movimiento::query()->truncate();

        $usuario = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $payload = [
            'institution_id' => $institutionDestino->id,
            'service_id' => $serviceDestino->id,
            'oficina_id' => $officeDestino->id,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => $equipo->marca,
            'modelo' => $equipo->modelo,
            'numero_serie' => $equipo->numero_serie,
            'bien_patrimonial' => $equipo->bien_patrimonial,
            'estado' => $equipo->estado,
            'fecha_ingreso' => $equipo->fecha_ingreso?->toDateString(),
        ];

        $this->actingAs($usuario)
            ->put(route('equipos.update', $equipo), $payload)
            ->assertRedirect(route('equipos.index'));

        $movimiento = Movimiento::query()->latest('id')->firstOrFail();

        $this->assertSame('traslado', $movimiento->tipo_movimiento);
        $this->assertSame($usuario->id, $movimiento->user_id);
        $this->assertSame($institutionOrigen->id, $movimiento->institucion_origen_id);
        $this->assertSame($serviceOrigen->id, $movimiento->servicio_origen_id);
        $this->assertSame($officeOrigen->id, $movimiento->oficina_origen_id);
        $this->assertSame($institutionDestino->id, $movimiento->institucion_destino_id);
        $this->assertSame($serviceDestino->id, $movimiento->servicio_destino_id);
        $this->assertSame($officeDestino->id, $movimiento->oficina_destino_id);
        $this->assertSame('Traslado de ubicación', $movimiento->observacion);
    }

    public function test_registro_manual_de_movimiento(): void
    {
        [$institution, $service, $office] = $this->crearUbicacion('Hospital Oeste', 'Pediatría', 'Sala 3');

        $tipoEquipo = TipoEquipo::create(['nombre' => 'Impresora']);
        $equipo = $this->crearEquipo($office, $tipoEquipo);
        $usuario = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $this->actingAs($usuario)
            ->post(route('equipos.movimientos.store', $equipo), [
                'tipo_movimiento' => 'mantenimiento',
                'observacion' => 'Cambio de fuente de poder',
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $this->assertDatabaseHas('movimientos', [
            'equipo_id' => $equipo->id,
            'user_id' => $usuario->id,
            'tipo_movimiento' => 'mantenimiento',
            'institucion_origen_id' => $institution->id,
            'servicio_origen_id' => $service->id,
            'oficina_origen_id' => $office->id,
            'institucion_destino_id' => null,
            'servicio_destino_id' => null,
            'oficina_destino_id' => null,
            'observacion' => 'Cambio de fuente de poder',
        ]);
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

    private function crearEquipo(Office $office, TipoEquipo $tipoEquipo): Equipo
    {
        return Equipo::create([
            'tipo' => $tipoEquipo->nombre,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'HP',
            'modelo' => 'Model X',
            'numero_serie' => 'SER-'.uniqid(),
            'bien_patrimonial' => 'BP-'.uniqid(),
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }

    /**
     * @return array{0:Institution,1:Service,2:Office}
     */
    private function crearUbicacion(string $nombreInstitucion, string $nombreServicio, string $nombreOficina): array
    {
        $institution = Institution::create(['nombre' => $nombreInstitucion]);
        $service = Service::create([
            'nombre' => $nombreServicio,
            'institution_id' => $institution->id,
        ]);
        $office = Office::create([
            'nombre' => $nombreOficina,
            'service_id' => $service->id,
        ]);

        return [$institution, $service, $office];
    }
}
