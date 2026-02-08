<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'admin@gestion-activos.local',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPERADMIN,
        ]);

        Institution::firstOrCreate([
            'nombre' => 'Hospital General Central',
        ], [
            'descripcion' => 'Institución de referencia para la red de salud.',
        ]);
    }
}
