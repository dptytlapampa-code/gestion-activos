<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActiveInstitutionContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sets_primary_institution_as_active_by_default(): void
    {
        [$primary, $secondary] = $this->crearInstituciones();

        $user = User::factory()->create([
            'email' => 'activo@local.test',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
            'institution_id' => $primary->id,
            'is_active' => true,
        ]);

        $user->permittedInstitutions()->sync([$secondary->id]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($primary->id, session(ActiveInstitutionContext::SESSION_KEY));
    }

    public function test_user_can_switch_active_institution_to_an_enabled_institution(): void
    {
        [$primary, $secondary] = $this->crearInstituciones();
        $user = $this->crearUsuarioConPermisos($primary, [$secondary]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->actingAs($user)
            ->put(route('session.active-institution.update'), [
                'institution_id' => $secondary->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($secondary->id, session(ActiveInstitutionContext::SESSION_KEY));
    }

    public function test_user_cannot_switch_active_institution_to_a_non_enabled_institution(): void
    {
        [$primary, $secondary, $forbidden] = $this->crearInstituciones(includeThird: true);
        $user = $this->crearUsuarioConPermisos($primary, [$secondary]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertSame($primary->id, session(ActiveInstitutionContext::SESSION_KEY));

        $this->actingAs($user)
            ->put(route('session.active-institution.update'), [
                'institution_id' => $forbidden->id,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'No tiene permisos para operar en la institucion seleccionada.');

        $this->assertSame($primary->id, session(ActiveInstitutionContext::SESSION_KEY));
    }

    public function test_equipos_index_shows_only_the_active_institution(): void
    {
        [$primary, $secondary] = $this->crearInstituciones();
        [$officePrimary, $officeSecondary] = $this->crearUbicaciones($primary, $secondary);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Notebook institucional']);
        $equipoPrimary = $this->crearEquipo($officePrimary, $tipoEquipo, 'SER-PRIMARY', 'BP-PRIMARY');
        $equipoSecondary = $this->crearEquipo($officeSecondary, $tipoEquipo, 'SER-SECONDARY', 'BP-SECONDARY');
        $user = $this->crearUsuarioConPermisos($primary, [$secondary]);

        $this->actingAs($user)
            ->get(route('equipos.index'))
            ->assertOk()
            ->assertSee($equipoPrimary->numero_serie)
            ->assertDontSee($equipoSecondary->numero_serie);

        $this->actingAs($user)
            ->put(route('session.active-institution.update'), [
                'institution_id' => $secondary->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('equipos.index'))
            ->assertOk()
            ->assertSee($equipoSecondary->numero_serie)
            ->assertDontSee($equipoPrimary->numero_serie);
    }

    public function test_operational_access_is_blocked_until_the_user_changes_context(): void
    {
        [$primary, $secondary] = $this->crearInstituciones();
        [, $officeSecondary] = $this->crearUbicaciones($primary, $secondary);
        $tipoEquipo = TipoEquipo::create(['nombre' => 'Monitor']);
        $equipoSecondary = $this->crearEquipo($officeSecondary, $tipoEquipo, 'SER-BLOCKED', 'BP-BLOCKED');
        $user = $this->crearUsuarioConPermisos($primary, [$secondary]);

        $this->actingAs($user)
            ->get(route('equipos.show', $equipoSecondary))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('session.active-institution.update'), [
                'institution_id' => $secondary->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('equipos.show', $equipoSecondary))
            ->assertOk();
    }

    /**
     * @return array{0:Institution,1:Institution,2:Institution|null}
     */
    private function crearInstituciones(bool $includeThird = false): array
    {
        $primary = Institution::create(['nombre' => 'Hospital Principal']);
        $secondary = Institution::create(['nombre' => 'Hospital Secundario']);
        $third = $includeThird ? Institution::create(['nombre' => 'Hospital Restringido']) : null;

        return [$primary, $secondary, $third];
    }

    /**
     * @param  array<int, Institution>  $additionalInstitutions
     */
    private function crearUsuarioConPermisos(Institution $primary, array $additionalInstitutions = []): User
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'institution_id' => $primary->id,
            'is_active' => true,
        ]);

        $user->permittedInstitutions()->sync(collect($additionalInstitutions)->pluck('id')->all());

        return $user;
    }

    /**
     * @return array{0:Office,1:Office}
     */
    private function crearUbicaciones(Institution $primary, Institution $secondary): array
    {
        $servicePrimary = Service::create([
            'nombre' => 'Servicio Principal',
            'institution_id' => $primary->id,
        ]);

        $serviceSecondary = Service::create([
            'nombre' => 'Servicio Secundario',
            'institution_id' => $secondary->id,
        ]);

        return [
            Office::create(['nombre' => 'Oficina Principal', 'service_id' => $servicePrimary->id]),
            Office::create(['nombre' => 'Oficina Secundaria', 'service_id' => $serviceSecondary->id]),
        ];
    }

    private function crearEquipo(Office $office, TipoEquipo $tipoEquipo, string $serie, string $bien): Equipo
    {
        return Equipo::create([
            'tipo' => $tipoEquipo->nombre,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => $serie,
            'bien_patrimonial' => $bien,
            'estado' => Equipo::ESTADO_OPERATIVO,
            'fecha_ingreso' => now()->toDateString(),
            'oficina_id' => $office->id,
        ]);
    }
}
