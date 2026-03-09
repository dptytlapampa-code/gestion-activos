<?php

namespace Database\Seeders;

use App\Models\EquipoStatus;
use Illuminate\Database\Seeder;

class EquipoStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => EquipoStatus::CODE_OPERATIVA, 'name' => 'Operativa', 'color' => 'green', 'is_terminal' => false],
            ['code' => EquipoStatus::CODE_PRESTADA, 'name' => 'Prestada', 'color' => 'blue', 'is_terminal' => false],
            ['code' => EquipoStatus::CODE_EN_SERVICIO_TECNICO, 'name' => 'En Servicio Tecnico', 'color' => 'yellow', 'is_terminal' => false],
            ['code' => EquipoStatus::CODE_FUERA_DE_SERVICIO, 'name' => 'Fuera de Servicio', 'color' => 'orange', 'is_terminal' => false],
            ['code' => EquipoStatus::CODE_BAJA, 'name' => 'Baja', 'color' => 'red', 'is_terminal' => true],
        ];

        foreach ($statuses as $status) {
            EquipoStatus::query()->updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
