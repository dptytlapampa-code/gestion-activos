<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'Gestion de Activos',
            'primary_color' => '#4F46E5',
            'sidebar_color' => '#4338CA',
            'logo_path' => null,
            'logo_institucional' => null,
            'logo_pdf' => null,
        ];

        $setting = SystemSetting::query()->orderBy('id')->first();

        if ($setting === null) {
            SystemSetting::query()->create($defaults);

            return;
        }

        $logoInstitucional = $setting->logo_institucional ?: $setting->logo_path;

        $setting->fill([
            'site_name' => $setting->site_name ?: $defaults['site_name'],
            'primary_color' => $setting->primary_color ?: $defaults['primary_color'],
            'sidebar_color' => $setting->sidebar_color ?: $defaults['sidebar_color'],
            'logo_path' => $logoInstitucional,
            'logo_institucional' => $logoInstitucional,
            'logo_pdf' => $setting->logo_pdf,
        ])->save();

        SystemSetting::query()
            ->whereKeyNot($setting->id)
            ->delete();
    }
}
