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
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_limita_los_widgets_recientes_a_cinco_registros(): void
    {
        $institution = Institution::create(['nombre' => 'Hospital Dashboard']);
        $service = Service::create(['nombre' => 'Guardia', 'institution_id' => $institution->id]);
        $office = Office::create(['nombre' => 'Oficina Principal', 'service_id' => $service->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Monitor']);
        $user = User::create([
            'name' => 'Admin Dashboard',
            'email' => 'dashboard-'.uniqid().'@test.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 7; $i++) {
            $equipo = Equipo::create([
                'tipo' => $tipo->nombre,
                'tipo_equipo_id' => $tipo->id,
                'marca' => 'Marca '.$i,
                'modelo' => 'Modelo '.$i,
                'numero_serie' => 'SER-DASH-'.$i,
                'bien_patrimonial' => 'BP-DASH-'.$i,
                'estado' => Equipo::ESTADO_OPERATIVO,
                'fecha_ingreso' => now()->toDateString(),
                'oficina_id' => $office->id,
            ]);

            Equipo::query()->whereKey($equipo->id)->update([
                'created_at' => now()->subMinutes(8 - $i),
                'updated_at' => now()->subMinutes(8 - $i),
            ]);

            Acta::create([
                'institution_id' => $institution->id,
                'tipo' => Acta::TIPO_ENTREGA,
                'fecha' => now()->subDays(7 - $i)->toDateString(),
                'receptor_nombre' => 'Receptor '.$i,
                'created_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $this->assertSame(
            ['SER-DASH-7', 'SER-DASH-6', 'SER-DASH-5', 'SER-DASH-4', 'SER-DASH-3'],
            collect($response->viewData('equiposRecientes'))->pluck('numero_serie')->all()
        );

        $this->assertSame(
            ['Receptor 7', 'Receptor 6', 'Receptor 5', 'Receptor 4', 'Receptor 3'],
            collect($response->viewData('actas'))->pluck('receptor_nombre')->all()
        );
    }

    public function test_dashboard_en_nivel_central_consolida_el_universo_completo(): void
    {
        $nivelCentral = Institution::query()
            ->where('scope_type', Institution::SCOPE_GLOBAL)
            ->firstOrFail();
        $institutionA = Institution::create(['nombre' => 'Hospital Norte']);
        $institutionB = Institution::create(['nombre' => 'Hospital Sur']);

        $serviceA = Service::create(['nombre' => 'Guardia Norte', 'institution_id' => $institutionA->id]);
        $serviceB = Service::create(['nombre' => 'Guardia Sur', 'institution_id' => $institutionB->id]);
        $officeA = Office::create(['nombre' => 'Oficina Norte', 'service_id' => $serviceA->id]);
        $officeB = Office::create(['nombre' => 'Oficina Sur', 'service_id' => $serviceB->id]);
        $tipo = TipoEquipo::create(['nombre' => 'Respirador']);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Marca Norte',
            'modelo' => 'Modelo Norte',
            'numero_serie' => 'SER-GLB-01',
            'bien_patrimonial' => 'BP-GLB-01',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $officeA->id,
        ]);

        Equipo::create([
            'tipo' => $tipo->nombre,
            'tipo_equipo_id' => $tipo->id,
            'marca' => 'Marca Sur',
            'modelo' => 'Modelo Sur',
            'numero_serie' => 'SER-GLB-02',
            'bien_patrimonial' => 'BP-GLB-02',
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $officeB->id,
        ]);

        $adminCentral = User::create([
            'name' => 'Admin Central',
            'email' => 'dashboard-central-'.uniqid().'@test.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'institution_id' => $nivelCentral->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminCentral)
            ->withSession(['active_institution_id' => $nivelCentral->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('Alcance global del sistema', $response->viewData('dashboardContext')['scopeLabel']);
        $this->assertSame('2', str_replace(['.', ','], '', (string) $response->viewData('kpiCards')[0]['value']));
        $this->assertTrue((bool) $response->viewData('dashboardContext')['showInstitutionContext']);
    }
}
