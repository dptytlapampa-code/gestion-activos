<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $document = Document::create([
            'uploaded_by' => $viewer->id,
            'documentable_type' => Equipo::class,
            'documentable_id' => $equipo->id,
            'type' => 'nota',
            'file_path' => 'documents/2026/01/n.pdf',
            'original_name' => 'n.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ]);
        Storage::put($document->file_path, 'hola');

        $this->actingAs($viewer)->get(route('documents.download', $document))->assertOk();
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
}
