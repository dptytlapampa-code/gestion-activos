<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@local.test',
        ], [
            'name' => 'Administrador',
            'password' => '123456',
            'role' => User::ROLE_SUPERADMIN,
            'institution_id' => null,
            'is_active' => true,
        ]);

        Institution::firstOrCreate([
            'nombre' => 'Hospital General Central',
        ], [
            'descripcion' => 'Institución de referencia para la red de salud.',
        ]);
    }
}
