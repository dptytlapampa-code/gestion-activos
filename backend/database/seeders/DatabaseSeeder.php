<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@gestion-activos.local'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => User::ROLE_SUPERADMIN,
                'institution_id' => null,
                'is_active' => true,
            ]
        );
    }
}
