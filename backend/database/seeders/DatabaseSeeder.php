<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionScopeService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CentralInstitutionSeeder::class,
            EquipoStatusSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        $centralInstitution = app(InstitutionScopeService::class)->ensureCentralInstitution();

        User::updateOrCreate([
            'email' => 'admin@local.test',
        ], [
            'name' => 'Administrador',
            'password' => '123456',
            'role' => User::ROLE_SUPERADMIN,
            'institution_id' => $centralInstitution->id,
            'is_active' => true,
        ]);

        Institution::firstOrCreate([
            'nombre' => 'Hospital General Central',
        ], [
            'descripcion' => 'Institucion de referencia para la red de salud.',
            'scope_type' => Institution::SCOPE_INSTITUTIONAL,
        ]);
    }
}

