<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Mantenimiento;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_subir_documento_en_su_hospital(): void
    {
        Storage::fake();
        [$admin, $equipo] = $this->crearEscenario(User::ROLE_ADMIN, true);

        $this->actingAs($admin)->post(route('equipos.documents.store', $equipo), [
            'type' => 'factura',
            'note' => 'Factura compra',
            'file' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', ['documentable_type' => Equipo::class, 'documentable_id' => $equipo->id]);
        $this->assertDatabaseHas('equipo_documentos', [
            'equipo_id' => $equipo->id,
            'origen_tipo' => 'directo',
            'tipo_documento' => 'factura',
            'nombre_original' => 'factura.pdf',
        ]);
    }


    public function test_admin_puede_subir_imagen_jpg(): void
    {
        Storage::fake();
        [$admin, $equipo] = $this->crearEscenario(User::ROLE_ADMIN, true);

        $this->actingAs($admin)->post(route('equipos.documents.store', $equipo), [
            'type' => 'nota',
            'note' => 'Foto del equipo',
            'file' => UploadedFile::fake()->image('equipo.jpg')->size(3000),
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Equipo::class,
            'documentable_id' => $equipo->id,
            'original_name' => 'equipo.jpg',
        ]);
        $this->assertDatabaseHas('equipo_documentos', [
            'equipo_id' => $equipo->id,
            'origen_tipo' => 'directo',
            'nombre_original' => 'equipo.jpg',
        ]);
    }

    public function test_subida_fuera_de_alcance_da_403(): void
    {
        Storage::fake();
        [$admin, $equipo] = $this->crearEscenario(User::ROLE_ADMIN, false);

        $this->actingAs($admin)->post(route('equipos.documents.store', $equipo), [
            'type' => 'factura',
            'file' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_viewer_no_puede_subir_pero_puede_descargar(): void
    {
        Storage::fake();
        [$viewer, $equipo] = $this->crearEscenario(User::ROLE_VIEWER, true);

        $this->actingAs($viewer)->post(route('equipos.documents.store', $equipo), [
            'type' => 'nota',
            'file' => UploadedFile::fake()->create('n.pdf', 10, 'application/pdf'),
        ])->assertForbidden();

        $document = $equipo->documents()->create([
            'uploaded_by' => $viewer->id,
            'type' => 'nota',
            'file_path' => 'documents/2026/01/n.pdf',
            'original_name' => 'n.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ]);
        Storage::put($document->file_path, 'hola');

        $this->actingAs($viewer)->get(route('documents.download', $document))->assertOk();
    }

    public function test_documento_subido_en_movimiento_queda_visible_en_movimiento_y_en_legajo_del_equipo(): void
    {
        Storage::fake();
        [$admin, $equipo] = $this->crearEscenario(User::ROLE_ADMIN, true);
        $movimiento = $this->crearMovimiento($equipo, $admin);

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('movimientos.documents.store', $movimiento), [
                'type' => 'nota',
                'note' => 'Adjunto del traslado',
                'file' => UploadedFile::fake()->create('movimiento.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $document = Document::query()
            ->where('documentable_type', Movimiento::class)
            ->where('documentable_id', $movimiento->id)
            ->firstOrFail();

        $this->assertDatabaseHas('equipo_documentos', [
            'equipo_id' => $equipo->id,
            'document_id' => $document->id,
            'origen_tipo' => 'movimiento',
            'origen_id' => $movimiento->id,
            'nombre_original' => 'movimiento.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('equipos.show', $equipo));

        $response->assertOk();
        $response->assertSee('movimiento.pdf');
        $response->assertSee('Movimiento');
        $response->assertSee('#movimiento-'.$movimiento->id, false);
    }

    public function test_documento_subido_en_mantenimiento_queda_visible_en_mantenimiento_y_en_legajo_del_equipo(): void
    {
        Storage::fake();
        [$admin, $equipo] = $this->crearEscenario(User::ROLE_ADMIN, true);
        $mantenimiento = $this->crearMantenimiento($equipo, $admin);

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('mantenimientos.documents.store', $mantenimiento), [
                'type' => 'presupuesto',
                'note' => 'Informe del service',
                'file' => UploadedFile::fake()->create('mantenimiento.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $document = Document::query()
            ->where('documentable_type', Mantenimiento::class)
            ->where('documentable_id', $mantenimiento->id)
            ->firstOrFail();

        $this->assertDatabaseHas('equipo_documentos', [
            'equipo_id' => $equipo->id,
            'document_id' => $document->id,
            'origen_tipo' => 'mantenimiento',
            'origen_id' => $mantenimiento->id,
            'nombre_original' => 'mantenimiento.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('equipos.show', $equipo));

        $response->assertOk();
        $response->assertSee('mantenimiento.pdf');
        $response->assertSee('Mantenimiento');
        $response->assertSee('#mantenimiento-'.$mantenimiento->id, false);
    }

    public function test_pestana_documentos_consolida_documentos_directos_de_movimiento_y_mantenimiento(): void
    {
        Storage::fake();
        [$admin, $equipo] = $this->crearEscenario(User::ROLE_ADMIN, true);
        $movimiento = $this->crearMovimiento($equipo, $admin);
        $mantenimiento = $this->crearMantenimiento($equipo, $admin);

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('equipos.documents.store', $equipo), [
                'type' => 'factura',
                'note' => 'Documento directo',
                'file' => UploadedFile::fake()->create('directo.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('movimientos.documents.store', $movimiento), [
                'type' => 'nota',
                'note' => 'Documento movimiento',
                'file' => UploadedFile::fake()->create('mov.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $this->actingAs($admin)
            ->from(route('equipos.show', $equipo))
            ->post(route('mantenimientos.documents.store', $mantenimiento), [
                'type' => 'otro',
                'note' => 'Documento mantenimiento',
                'file' => UploadedFile::fake()->create('mto.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('equipos.show', $equipo));

        $response = $this->actingAs($admin)->get(route('equipos.show', $equipo));

        $response->assertOk();
        $response->assertSee('directo.pdf');
        $response->assertSee('mov.pdf');
        $response->assertSee('mto.pdf');
        $response->assertSee('Directo');
        $response->assertSee('Movimiento');
        $response->assertSee('Mantenimiento');

        $this->assertSame(
            3,
            DB::table('equipo_documentos')->where('equipo_id', $equipo->id)->count()
        );
    }

    private function crearEscenario(string $role, bool $sameInstitution): array
    {
        $instA = Institution::create(['nombre' => 'A']);
        $instB = Institution::create(['nombre' => 'B']);
        $serviceA = Service::create(['nombre' => 'SA', 'institution_id' => $instA->id]);
        $serviceB = Service::create(['nombre' => 'SB', 'institution_id' => $instB->id]);
        $officeA = Office::create(['nombre' => 'OA', 'service_id' => $serviceA->id]);
        $officeB = Office::create(['nombre' => 'OB', 'service_id' => $serviceB->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Laptop']);

        $equipo = Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'A',
            'modelo' => 'B',
            'numero_serie' => uniqid('ns'),
            'bien_patrimonial' => uniqid('bp'),
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $sameInstitution ? $officeA->id : $officeB->id,
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => uniqid().'@test.com',
            'password' => '123456',
            'role' => $role,
            'institution_id' => $instA->id,
            'is_active' => true,
        ]);

        return [$user, $equipo];
    }

    private function crearMovimiento(Equipo $equipo, User $user): Movimiento
    {
        return Movimiento::create([
            'equipo_id' => $equipo->id,
            'user_id' => $user->id,
            'tipo_movimiento' => Movimiento::TIPO_TRASLADO,
            'fecha' => now(),
            'observacion' => 'Traslado documentado',
        ]);
    }

    private function crearMantenimiento(Equipo $equipo, User $user): Mantenimiento
    {
        $equipo->loadMissing('oficina.service.institution');

        return Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'institution_id' => $equipo->oficina?->service?->institution?->id,
            'created_by' => $user->id,
            'fecha' => now()->toDateString(),
            'tipo' => Mantenimiento::TIPO_INTERNO,
            'titulo' => 'Revision tecnica',
            'detalle' => 'Chequeo preventivo',
        ]);
    }
}
