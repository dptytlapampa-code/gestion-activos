<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Services\PublicMediaService;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'DPTYT',
            'primary_color' => '#1f2937',
            'sidebar_color' => '#1f2937',
            'logo_path' => null,
            'logo_institucional' => null,
            'logo_pdf' => null,
        ];

        $setting = SystemSetting::query()->orderBy('id')->first();

        if ($setting === null) {
            SystemSetting::query()->create($defaults);

            return;
        }

        $publicMediaService = app(PublicMediaService::class);
        $logoInstitucional = $publicMediaService->normalizeStoredPath($setting->logo_institucional ?: $setting->logo_path);
        $logoPdf = $publicMediaService->normalizeStoredPath($setting->logo_pdf);

        $setting->fill([
            'site_name' => $setting->site_name ?: $defaults['site_name'],
            'primary_color' => $setting->primary_color ?: $defaults['primary_color'],
            'sidebar_color' => $setting->sidebar_color ?: $defaults['sidebar_color'],
            'logo_path' => $logoInstitucional,
            'logo_institucional' => $logoInstitucional,
            'logo_pdf' => $logoPdf,
        ])->save();

        SystemSetting::query()
            ->whereKeyNot($setting->id)
            ->delete();
    }
}
