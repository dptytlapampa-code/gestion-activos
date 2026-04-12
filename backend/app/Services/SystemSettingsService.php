<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\Auditing\AuditLogService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        'sidebar_header_description' => 'Sistema de Gestion de Activos',
        'sidebar_header_subtitle' => 'Panel administrativo',
    ];

    private ?stdClass $cachedSettings = null;

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly PublicMediaService $publicMediaService,
    ) {}

    public function getCurrentSettings(): stdClass
    {
        if ($this->cachedSettings !== null) {
            return $this->cachedSettings;
        }

        $setting = $this->readSingletonForDisplay();
        $logoInstitucionalPath = $this->publicMediaService->normalizeStoredPath(
            $setting?->logo_institucional ?: $setting?->logo_path
        );
        $logoPdfPath = $this->publicMediaService->normalizeStoredPath($setting?->logo_pdf);
        $siteName = trim((string) ($setting?->site_name ?: self::DEFAULTS['site_name']));

        if ($siteName === '') {
            $siteName = self::DEFAULTS['site_name'];
        }
        $primaryColor = $this->normalizeColor((string) ($setting?->primary_color ?: self::DEFAULTS['primary_color']), self::DEFAULTS['primary_color']);
        $sidebarColor = $this->normalizeColor((string) ($setting?->sidebar_color ?: self::DEFAULTS['sidebar_color']), self::DEFAULTS['sidebar_color']);

        return $this->cachedSettings = (object) [
            'nombre_sistema' => $siteName,
            'site_name' => $siteName,
            'color_primario' => $primaryColor,
            'primary_color' => $primaryColor,
            'color_sidebar' => $sidebarColor,
            'sidebar_color' => $sidebarColor,
            'logo' => $logoInstitucionalPath,
            'logo_path' => $logoInstitucionalPath,
            'logo_institucional' => $logoInstitucionalPath,
            'logo_pdf' => $logoPdfPath,
            'logo_url' => $this->publicMediaService->url($logoInstitucionalPath),
            'logo_institucional_url' => $this->publicMediaService->url($logoInstitucionalPath),
            'logo_pdf_url' => $this->publicMediaService->url($logoPdfPath),
            'logo_institucional_file_path' => $this->publicMediaService->path($logoInstitucionalPath),
            'logo_pdf_file_path' => $this->publicMediaService->path($logoPdfPath),
            'system_logo_url' => asset('images/system/logo-sistema.png'),
            'sidebar_header_title' => $siteName,
            'sidebar_header_description' => self::DEFAULTS['sidebar_header_description'],
            'sidebar_header_subtitle' => self::DEFAULTS['sidebar_header_subtitle'],
            'primary_color_rgb' => $this->hexToRgbCsv($primaryColor),
            'sidebar_color_rgb' => $this->hexToRgbCsv($sidebarColor),
        ];
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
                'primary_color' => $this->normalizeColor((string) $input['primary_color'], self::DEFAULTS['primary_color']),
                'sidebar_color' => $this->normalizeColor((string) $input['sidebar_color'], self::DEFAULTS['sidebar_color']),
            ];

            if ($logoInstitucional !== null) {
                $newPath = $this->publicMediaService->storeUploadedFileAs(
                    $logoInstitucional,
                    'logos',
                    'institucional.png',
                    'logo_institucional',
                );

                $payload['logo_path'] = $newPath;
                $payload['logo_institucional'] = $newPath;
                $this->cleanupOldPaths([$setting->logo_path, $setting->logo_institucional], $newPath);
            }

            if ($logoPdf !== null) {
                $newPath = $this->publicMediaService->storeUploadedFileAs(
                    $logoPdf,
                    'logos',
                    'pdf.png',
                    'logo_pdf',
                );

                $payload['logo_pdf'] = $newPath;
                $this->cleanupOldPaths([$setting->logo_pdf], $newPath);
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

    /**
     * @param  array<int, mixed>  $oldPaths
     */
    private function cleanupOldPaths(array $oldPaths, string $newPath): void
    {
        foreach ($oldPaths as $oldPath) {
            $this->publicMediaService->delete($oldPath, $newPath);
        }
    }

    private function normalizeColor(string $value, string $fallback): string
    {
        $hex = strtoupper(ltrim(trim($value), '#'));

        if (preg_match('/^[A-F0-9]{3}$/', $hex) === 1) {
            return sprintf('#%1$s%1$s%2$s%2$s%3$s%3$s', $hex[0], $hex[1], $hex[2]);
        }

        if (preg_match('/^[A-F0-9]{6}$/', $hex) === 1) {
            return '#'.$hex;
        }

        return $fallback;
    }

    private function hexToRgbCsv(string $value): string
    {
        $hex = ltrim($this->normalizeColor($value, self::DEFAULTS['primary_color']), '#');

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
        $logoInstitucional = $this->publicMediaService->normalizeStoredPath($setting?->logo_institucional ?: $setting?->logo_path);
        $logoPdf = $this->publicMediaService->normalizeStoredPath($setting?->logo_pdf);

        return [
            'site_name' => $setting?->site_name ?: self::DEFAULTS['site_name'],
            'primary_color' => $this->normalizeColor((string) ($setting?->primary_color ?: self::DEFAULTS['primary_color']), self::DEFAULTS['primary_color']),
            'sidebar_color' => $this->normalizeColor((string) ($setting?->sidebar_color ?: self::DEFAULTS['sidebar_color']), self::DEFAULTS['sidebar_color']),
            'logo_institucional' => $logoInstitucional !== null ? 'Configurado' : 'No configurado',
            'logo_pdf' => $logoPdf !== null ? 'Configurado' : 'No configurado',
        ];
    }
}
