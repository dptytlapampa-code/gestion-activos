<?php

namespace Database\Seeders;

use App\Services\InstitutionScopeService;
use Illuminate\Database\Seeder;

class CentralInstitutionSeeder extends Seeder
{
    public function run(): void
    {
        app(InstitutionScopeService::class)->ensureCentralInstitution();
    }
}
