<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\Auditing\AuditLogService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use stdClass;

class SystemSettingsService
{
    private const DEFAULTS = [
        'site_name' => 'DPTYT',
        'primary_color' => '#1f2937',
        'sidebar_color' => '#1f2937',
        'logo_path' => null,
        'logo_institucional' => null,
        'logo_pdf' => null,
    ];

    private ?stdClass $cachedSettings = null;

    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function getCurrentSettings(): stdClass
    {
        if ($this->cachedSettings !== null) {
            return $this->cachedSettings;
        }

        $setting = $this->readSingletonForDisplay();
        $logoInstitucionalPath = $setting?->logo_institucional ?: $setting?->logo_path;
        $logoPdfPath = $setting?->logo_pdf;

        $settings = [
            'site_name' => $setting?->site_name ?: self::DEFAULTS['site_name'],
            'primary_color' => $this->normalizeColor($setting?->primary_color ?: self::DEFAULTS['primary_color']),
            'sidebar_color' => $this->normalizeColor($setting?->sidebar_color ?: self::DEFAULTS['sidebar_color']),
            'logo_path' => $logoInstitucionalPath,
            'logo_institucional' => $logoInstitucionalPath,
            'logo_pdf' => $logoPdfPath,
        ];

        $settings['logo_url'] = $this->resolveLogoUrl($logoInstitucionalPath);
        $settings['logo_institucional_url'] = $settings['logo_url'];
        $settings['logo_pdf_url'] = $this->resolveLogoUrl($logoPdfPath);
        $settings['logo_institucional_file_path'] = $this->resolveLogoFilePath($logoInstitucionalPath);
        $settings['logo_pdf_file_path'] = $this->resolveLogoFilePath($logoPdfPath);
        $settings['system_logo_url'] = asset('images/system/logo-sistema.png');
        $settings['primary_color_rgb'] = $this->hexToRgbCsv($settings['primary_color']);
        $settings['sidebar_color_rgb'] = $this->hexToRgbCsv($settings['sidebar_color']);

        return $this->cachedSettings = (object) $settings;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(array $input, ?UploadedFile $logoInstitucional = null, ?UploadedFile $logoPdf = null): stdClass
    {
        DB::transaction(function () use ($input, $logoInstitucional, $logoPdf): void {
            $setting = $this->lockSingletonForUpdate();
            $before = $this->settingsAuditSnapshot($setting);

            $payload = [
                'site_name' => trim((string) $input['site_name']) ?: self::DEFAULTS['site_name'],
                'primary_color' => $this->normalizeColor((string) $input['primary_color']),
                'sidebar_color' => $this->normalizeColor((string) $input['sidebar_color']),
            ];

            if ($logoInstitucional !== null) {
                $newPath = $this->storeFixedLogo($logoInstitucional, 'institucional.png', 'logo_institucional');
                $oldPaths = array_filter([$setting->logo_path, $setting->logo_institucional]);

                $payload['logo_path'] = $newPath;
                $payload['logo_institucional'] = $newPath;

                $this->cleanupOldPaths($oldPaths, $newPath);
            }

            if ($logoPdf !== null) {
                $newPath = $this->storeFixedLogo($logoPdf, 'pdf.png', 'logo_pdf');
                $oldPaths = array_filter([$setting->logo_pdf]);

                $payload['logo_pdf'] = $newPath;

                $this->cleanupOldPaths($oldPaths, $newPath);
            }

            $setting->fill($payload)->save();

            SystemSetting::query()
                ->whereKeyNot($setting->id)
                ->delete();

            $after = $this->settingsAuditSnapshot($setting->fresh());
            $changes = $this->auditLogService->diff($before, $after, [
                'site_name' => 'Nombre del sistema',
                'primary_color' => 'Color principal',
                'sidebar_color' => 'Color lateral',
                'logo_institucional' => 'Logo institucional',
                'logo_pdf' => 'Logo PDF',
            ]);

            if ($changes !== []) {
                $this->auditLogService->record([
                    'user' => auth()->user(),
                    'module' => 'configuracion',
                    'action' => 'configuracion_general_actualizada',
                    'entity_type' => 'configuracion',
                    'entity_id' => $setting->id,
                    'summary' => 'Se actualizo la configuracion general del sistema.',
                    'before' => $before,
                    'after' => $after,
                    'metadata' => [
                        'details' => $after,
                        'changes' => $changes,
                    ],
                    'level' => AuditLog::LEVEL_CRITICAL,
                    'is_critical' => true,
                ]);
            }
        });

        $this->cachedSettings = null;
        Cache::forget(system_config_cache_key());

        return $this->getCurrentSettings();
    }

    private function lockSingletonForUpdate(): SystemSetting
    {
        $setting = SystemSetting::query()
            ->lockForUpdate()
            ->orderBy('id')
            ->first();

        if ($setting !== null) {
            return $setting;
        }

        return SystemSetting::query()->create([
            'site_name' => self::DEFAULTS['site_name'],
            'primary_color' => self::DEFAULTS['primary_color'],
            'sidebar_color' => self::DEFAULTS['sidebar_color'],
            'logo_path' => self::DEFAULTS['logo_path'],
            'logo_institucional' => self::DEFAULTS['logo_institucional'],
            'logo_pdf' => self::DEFAULTS['logo_pdf'],
        ]);
    }

    private function readSingletonForDisplay(): ?SystemSetting
    {
        try {
            return SystemSetting::query()->orderBy('id')->first();
        } catch (QueryException $exception) {
            if ($this->isMissingTableException($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    private function isMissingTableException(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'system_settings')
            && (
                str_contains($message, 'no such table')
                || str_contains($message, 'does not exist')
                || str_contains($message, 'undefined table')
            );
    }

    private function storeFixedLogo(UploadedFile $logo, string $filename, string $field): string
    {
        $newLogoPath = $logo->storeAs('logos', $filename, 'public');

        if (! is_string($newLogoPath) || $newLogoPath === '') {
            throw ValidationException::withMessages([
                $field => 'No fue posible guardar el archivo seleccionado.',
            ]);
        }

        return $newLogoPath;
    }

    /**
     * @param  array<int, string>  $oldPaths
     */
    private function cleanupOldPaths(array $oldPaths, string $newPath): void
    {
        foreach (array_unique($oldPaths) as $oldPath) {
            if ($oldPath === '' || $oldPath === $newPath) {
                continue;
            }

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }

    private function resolveLogoUrl(?string $logoPath): ?string
    {
        if ($logoPath === null || $logoPath === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        return Storage::disk('public')->url($logoPath);
    }

    private function resolveLogoFilePath(?string $logoPath): ?string
    {
        if ($logoPath === null || $logoPath === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        return Storage::disk('public')->path($logoPath);
    }

    private function normalizeColor(string $value): string
    {
        $hex = strtoupper(ltrim(trim($value), '#'));

        if (strlen($hex) === 3) {
            $hex = sprintf('%s%s%s%s%s%s', $hex[0], $hex[0], $hex[1], $hex[1], $hex[2], $hex[2]);
        }

        return '#'.$hex;
    }

    private function hexToRgbCsv(string $value): string
    {
        $hex = ltrim($this->normalizeColor($value), '#');

        return sprintf(
            '%d, %d, %d',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    /**
     * @return array<string, string>
     */
    private function settingsAuditSnapshot(?SystemSetting $setting): array
    {
        return [
            'site_name' => $setting?->site_name ?: self::DEFAULTS['site_name'],
            'primary_color' => $this->normalizeColor((string) ($setting?->primary_color ?: self::DEFAULTS['primary_color'])),
            'sidebar_color' => $this->normalizeColor((string) ($setting?->sidebar_color ?: self::DEFAULTS['sidebar_color'])),
            'logo_institucional' => $setting?->logo_institucional ? 'Configurado' : 'No configurado',
            'logo_pdf' => $setting?->logo_pdf ? 'Configurado' : 'No configurado',
        ];
    }
}
