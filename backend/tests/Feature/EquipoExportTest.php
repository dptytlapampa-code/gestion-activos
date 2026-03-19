<?php

namespace Tests\Feature;

use App\Enums\ExportScope;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EquipoExportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_superadmin_puede_exportar_resultados(): void
    {
        Carbon::setTestNow('2026-03-19 14:30:00');

        [, $office] = $this->crearUbicacion('Hospital Central', 'Laboratorio', 'Oficina 1');
        $tipo = TipoEquipo::create(['nombre' => 'Monitor']);

        $esperado = $this->crearEquipo($office, $tipo, [
            'marca' => 'Philips',
            'modelo' => 'MX-100',
            'numero_serie' => 'EXP-SUP-001',
            'bien_patrimonial' => 'BP-EXP-SUP-001',
            'codigo_interno' => 'CI-001',
            'mac_address' => 'AA:BB:CC:DD:EE:01',
        ]);

        $this->crearEquipo($office, $tipo, [
            'marca' => 'Drager',
            'modelo' => 'Vista 120',
            'numero_serie' => 'EXP-SUP-002',
            'bien_patrimonial' => 'BP-EXP-SUP-002',
        ]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($superadmin)->get(route('equipos.export.csv', [
            'scope' => ExportScope::RESULTS->value,
            'search' => 'Philips',
            'tipo' => 'Monitor',
            'estado' => Equipo::ESTADO_OPERATIVO,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            'equipos_filtrados_2026-03-19_14-30.csv',
            (string) $response->headers->get('content-disposition')
        );

        $rows = $this->parseCsvResponse($response);

        $this->assertSame([
            'ID',
            'UUID',
            'Tipo de equipo',
            'Marca',
            'Modelo',
            'Numero de serie',
            'Bien patrimonial',
            'Codigo interno',
            'Direccion MAC',
            'Estado',
            'Estado tecnico',
            'Institucion',
            'Servicio',
            'Oficina',
            'Ubicacion completa',
            'Fecha de ingreso',
            'Fecha de creacion',
            'Fecha de ultima actualizacion',
        ], $rows[0]);

        $this->assertCount(2, $rows);
        $this->assertSame((string) $esperado->id, $rows[1][0]);
        $this->assertSame((string) $esperado->uuid, $rows[1][1]);
        $this->assertSame('Philips', $rows[1][3]);
        $this->assertSame('EXP-SUP-001', $rows[1][5]);
        $this->assertSame('Hospital Central / Laboratorio / Oficina 1', $rows[1][14]);
    }

    public function test_admin_puede_exportar_resultados_de_su_alcance(): void
    {
        [$institutionA, $officeA] = $this->crearUbicacion('Hospital A', 'Clinica', 'Oficina A');
        [, $officeB] = $this->crearUbicacion('Hospital B', 'Guardia', 'Oficina B');
        $tipo = TipoEquipo::create(['nombre' => 'Ecografo']);

        $equipoPermitido = $this->crearEquipo($officeA, $tipo, [
            'numero_serie' => 'ADM-OK-001',
            'bien_patrimonial' => 'BP-ADM-OK-001',
        ]);

        $equipoAjeno = $this->crearEquipo($officeB, $tipo, [
            'numero_serie' => 'ADM-NO-001',
            'bien_patrimonial' => 'BP-ADM-NO-001',
        ]);

        $admin = $this->crearUsuario(User::ROLE_ADMIN, $institutionA);

        $response = $this->actingAs($admin)->get(route('equipos.export.csv', [
            'scope' => ExportScope::RESULTS->value,
        ]));

        $response->assertOk();

        $rows = $this->parseCsvResponse($response);
        $seriales = array_column(array_slice($rows, 1), 5);

        $this->assertContains($equipoPermitido->numero_serie, $seriales);
        $this->assertNotContains($equipoAjeno->numero_serie, $seriales);
    }

    public function test_usuario_no_autorizado_no_puede_exportar_y_no_ve_acciones(): void
    {
        [$institution, $office] = $this->crearUbicacion('Hospital Tecnico', 'Clinica', 'Oficina 1');
        $tipo = TipoEquipo::create(['nombre' => 'Notebook']);

        $this->crearEquipo($office, $tipo, [
            'numero_serie' => 'TEC-001',
            'bien_patrimonial' => 'BP-TEC-001',
        ]);

        $tecnico = $this->crearUsuario(User::ROLE_TECNICO, $institution);

        $indexResponse = $this->actingAs($tecnico)->get(route('equipos.index'));

        $indexResponse->assertOk();
        $indexResponse->assertDontSee('Exportar resultados');
        $indexResponse->assertDontSee('Exportar todo');

        $exportResponse = $this->actingAs($tecnico)->get(route('equipos.export.csv', [
            'scope' => ExportScope::RESULTS->value,
        ]));

        $exportResponse->assertForbidden();
        $exportResponse->assertSee('No tiene permisos para esta accion');
    }

    public function test_exportar_resultados_respeta_filtros_activos(): void
    {
        [, $office] = $this->crearUbicacion('Hospital Filtros', 'UTI', 'Sala 4');
        $monitor = TipoEquipo::create(['nombre' => 'Monitor']);
        $bomba = TipoEquipo::create(['nombre' => 'Bomba de infusion']);

        $equipoValido = $this->crearEquipo($office, $monitor, [
            'marca' => 'Philips',
            'modelo' => 'MX-400',
            'numero_serie' => 'FILTRO-OK-001',
            'bien_patrimonial' => 'BP-FILTRO-OK-001',
        ]);

        $this->crearEquipo($office, $monitor, [
            'marca' => 'Philips',
            'modelo' => 'MX-500',
            'numero_serie' => 'FILTRO-NO-001',
            'bien_patrimonial' => 'BP-FILTRO-NO-001',
            'estado' => Equipo::ESTADO_BAJA,
        ]);

        $this->crearEquipo($office, $bomba, [
            'marca' => 'Philips',
            'modelo' => 'MX-400',
            'numero_serie' => 'FILTRO-NO-002',
            'bien_patrimonial' => 'BP-FILTRO-NO-002',
        ]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($superadmin)->get(route('equipos.export.csv', [
            'scope' => ExportScope::RESULTS->value,
            'search' => 'Philips',
            'tipo' => 'Monitor',
            'modelo' => 'MX-400',
            'estado' => Equipo::ESTADO_OPERATIVO,
        ]));

        $rows = $this->parseCsvResponse($response);

        $this->assertCount(2, $rows);
        $this->assertSame($equipoValido->numero_serie, $rows[1][5]);
    }

    public function test_exportar_todo_no_depende_de_la_paginacion_actual(): void
    {
        [, $office] = $this->crearUbicacion('Hospital Pagina', 'Imagenes', 'Deposito');
        $tipo = TipoEquipo::create(['nombre' => 'Monitor']);

        for ($i = 1; $i <= 21; $i++) {
            $this->crearEquipo($office, $tipo, [
                'numero_serie' => 'TODO-'.$i,
                'bien_patrimonial' => 'BP-TODO-'.$i,
            ]);
        }

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($superadmin)->get(route('equipos.export.csv', [
            'scope' => ExportScope::ALL->value,
            'search' => 'inexistente',
            'page' => 2,
            'per_page' => 5,
        ]));

        $response->assertOk();
        $rows = $this->parseCsvResponse($response);

        $this->assertCount(22, $rows);
        $this->assertContains('TODO-1', array_column(array_slice($rows, 1), 5));
        $this->assertContains('TODO-21', array_column(array_slice($rows, 1), 5));
    }

    public function test_el_csv_contiene_registros_coherentes_con_la_consulta_y_bom_utf8(): void
    {
        [, $office] = $this->crearUbicacion('Hospital CSV', 'Cardiologia', 'Consultorio 3');
        $tipo = TipoEquipo::create(['nombre' => 'Holter']);

        $equipo = $this->crearEquipo($office, $tipo, [
            'marca' => 'GE',
            'modelo' => 'CardioDay',
            'numero_serie' => 'CSV-001',
            'bien_patrimonial' => 'BP-CSV-001',
            'codigo_interno' => 'INT-CSV-001',
            'mac_address' => 'AA:AA:AA:AA:AA:AA',
        ]);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);

        $response = $this->actingAs($superadmin)->get(route('equipos.export.csv', [
            'scope' => ExportScope::RESULTS->value,
            'search' => 'CSV-001',
        ]));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $rows = $this->parseCsvContent($content);

        $this->assertSame((string) $equipo->id, $rows[1][0]);
        $this->assertSame('GE', $rows[1][3]);
        $this->assertSame('CardioDay', $rows[1][4]);
        $this->assertSame('BP-CSV-001', $rows[1][6]);
        $this->assertSame('INT-CSV-001', $rows[1][7]);
        $this->assertSame('AA:AA:AA:AA:AA:AA', $rows[1][8]);
        $this->assertSame('Hospital CSV', $rows[1][11]);
    }

    public function test_policy_de_exportacion_se_integra_con_gate(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Policy']);

        $superadmin = $this->crearUsuario(User::ROLE_SUPERADMIN);
        $admin = $this->crearUsuario(User::ROLE_ADMIN, $institution);
        $tecnico = $this->crearUsuario(User::ROLE_TECNICO, $institution);

        $this->assertTrue($superadmin->can('export', Equipo::class));
        $this->assertTrue($admin->can('export', Equipo::class));
        $this->assertFalse($tecnico->can('export', Equipo::class));
    }

    /**
     * @return array{0: Institution, 1: Office}
     */
    private function crearUbicacion(string $institucionNombre, string $servicioNombre, string $oficinaNombre): array
    {
        $institution = Institution::create(['nombre' => $institucionNombre]);
        $service = Service::create([
            'nombre' => $servicioNombre,
            'institution_id' => $institution->id,
        ]);
        $office = Office::create([
            'nombre' => $oficinaNombre,
            'service_id' => $service->id,
        ]);

        return [$institution, $office];
    }

    /**
     * @param array{
     *     marca?:string,
     *     modelo?:string,
     *     numero_serie?:string,
     *     bien_patrimonial?:string,
     *     codigo_interno?:string|null,
     *     mac_address?:string|null,
     *     estado?:string,
     *     fecha_ingreso?:string
     * } $attributes
     */
    private function crearEquipo(Office $office, TipoEquipo $tipo, array $attributes = []): Equipo
    {
        return Equipo::create(array_merge([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Dell',
            'modelo' => 'Linea Base',
            'numero_serie' => 'SER-'.uniqid(),
            'bien_patrimonial' => 'BP-'.uniqid(),
            'codigo_interno' => null,
            'mac_address' => null,
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => '2026-03-19',
            'oficina_id' => $office->id,
        ], $attributes));
    }

    private function crearUsuario(string $role, ?Institution $institution = null): User
    {
        return User::create([
            'name' => 'Usuario '.$role,
            'email' => uniqid($role, true).'@test.com',
            'password' => 'password',
            'role' => $role,
            'institution_id' => $institution?->id,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsvResponse(TestResponse $response): array
    {
        return $this->parseCsvContent($response->streamedContent());
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsvContent(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\n|\r/', trim($content)) ?: [];

        return array_map(
            static fn (string $line): array => str_getcsv($line, ';'),
            array_values(array_filter($lines, static fn (string $line): bool => $line !== ''))
        );
    }
}
