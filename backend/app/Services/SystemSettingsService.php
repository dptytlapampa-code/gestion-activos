<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SystemSettingsService
{
    private const DEFAULTS = [
        'site_name' => 'Gestion de Activos',
        'primary_color' => '#4F46E5',
        'sidebar_color' => '#4338CA',
        'logo_path' => null,
    ];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cachedSettings = null;

    public function getCurrentSettings(): array
    {
        if ($this->cachedSettings !== null) {
            return $this->cachedSettings;
        }

        $setting = $this->readSingletonForDisplay();

        $settings = [
            'site_name' => $setting?->site_name ?: self::DEFAULTS['site_name'],
            'primary_color' => $this->normalizeColor($setting?->primary_color ?: self::DEFAULTS['primary_color']),
            'sidebar_color' => $this->normalizeColor($setting?->sidebar_color ?: self::DEFAULTS['sidebar_color']),
            'logo_path' => $setting?->logo_path,
        ];

        $settings['logo_url'] = $this->resolveLogoUrl($settings['logo_path']);
        $settings['primary_color_rgb'] = $this->hexToRgbCsv($settings['primary_color']);
        $settings['sidebar_color_rgb'] = $this->hexToRgbCsv($settings['sidebar_color']);

        return $this->cachedSettings = $settings;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(array $input, ?UploadedFile $logo = null): array
    {
        DB::transaction(function () use ($input, $logo): void {
            $setting = $this->lockSingletonForUpdate();

            $payload = [
                'site_name' => trim((string) $input['site_name']) ?: self::DEFAULTS['site_name'],
                'primary_color' => $this->normalizeColor((string) $input['primary_color']),
                'sidebar_color' => $this->normalizeColor((string) $input['sidebar_color']),
            ];

            if ($logo !== null) {
                $newLogoPath = $logo->store('system-settings/logos', 'public');

                if (! is_string($newLogoPath) || $newLogoPath === '') {
                    throw ValidationException::withMessages([
                        'logo' => 'No fue posible guardar el logo seleccionado.',
                    ]);
                }

                $oldLogoPath = $setting->logo_path;
                $payload['logo_path'] = $newLogoPath;

                if (
                    $oldLogoPath !== null
                    && $oldLogoPath !== $newLogoPath
                    && Storage::disk('public')->exists($oldLogoPath)
                ) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
            }

            $setting->fill($payload)->save();

            SystemSetting::query()
                ->whereKeyNot($setting->id)
                ->delete();
        });

        $this->cachedSettings = null;

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
}

