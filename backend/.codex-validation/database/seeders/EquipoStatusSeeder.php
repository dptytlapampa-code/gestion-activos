<?php

namespace Database\Seeders;

use App\Models\EquipoStatus;
use Illuminate\Database\Seeder;

class EquipoStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach (EquipoStatus::canonicalDefinitions() as $status) {
            EquipoStatus::query()->updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
