<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

if (! function_exists('system_config')) {
    function system_config(): object
    {
        return Cache::remember(system_config_cache_key(), now()->addHour(), static function (): object {
            $defaults = [
                'nombre_sistema' => 'Gestion de Activos',
                'color_primario' => '#1f2937',
                'color_sidebar' => '#1f2937',
                'logo_institucional' => null,
                'logo_pdf' => null,
            ];

            $row = system_config_read_row();

            $nombreSistema = system_config_string(
                $row?->nombre_sistema ?? $row?->site_name ?? null,
                $defaults['nombre_sistema']
            );
            $colorPrimario = system_config_normalize_color(
                $row?->color_primario ?? $row?->primary_color ?? null,
                $defaults['color_primario']
            );
            $colorSidebar = system_config_normalize_color(
                $row?->color_sidebar ?? $row?->sidebar_color ?? null,
                $defaults['color_sidebar']
            );

            $logoInstitucional = system_config_nullable_string(
                $row?->logo_institucional ?? $row?->logo_path ?? null
            );
            $logoPdf = system_config_nullable_string($row?->logo_pdf ?? null);

            $logoInstitucionalUrl = system_config_logo_url($logoInstitucional);
            $logoPdfUrl = system_config_logo_url($logoPdf);
            $logoInstitucionalFilePath = system_config_logo_file_path($logoInstitucional);
            $logoPdfFilePath = system_config_logo_file_path($logoPdf);

            return (object) [
                'nombre_sistema' => $nombreSistema,
                'site_name' => $nombreSistema,
                'color_primario' => $colorPrimario,
                'primary_color' => $colorPrimario,
                'color_sidebar' => $colorSidebar,
                'sidebar_color' => $colorSidebar,
                'logo' => $logoInstitucional,
                'logo_path' => $logoInstitucional,
                'logo_institucional' => $logoInstitucional,
                'logo_pdf' => $logoPdf,
                'logo_url' => $logoInstitucionalUrl,
                'logo_institucional_url' => $logoInstitucionalUrl,
                'logo_pdf_url' => $logoPdfUrl,
                'logo_institucional_file_path' => $logoInstitucionalFilePath,
                'logo_pdf_file_path' => $logoPdfFilePath,
                'system_logo_url' => asset('images/system/logo-sistema.png'),
                'primary_color_rgb' => system_config_hex_to_rgb_csv($colorPrimario),
                'sidebar_color_rgb' => system_config_hex_to_rgb_csv($colorSidebar),
            ];
        });
    }
}

if (! function_exists('system_config_cache_key')) {
    function system_config_cache_key(): string
    {
        return 'system_config.current';
    }
}

if (! function_exists('system_config_read_row')) {
    function system_config_read_row(): ?object
    {
        try {
            if (Schema::hasTable('configuracion_general')) {
                return DB::table('configuracion_general')
                    ->orderBy('id')
                    ->first();
            }

            if (Schema::hasTable('system_settings')) {
                return DB::table('system_settings')
                    ->orderBy('id')
                    ->first();
            }
        } catch (QueryException) {
            return null;
        }

        return null;
    }
}

if (! function_exists('system_config_string')) {
    function system_config_string(mixed $value, string $fallback): string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : $fallback;
    }
}

if (! function_exists('system_config_nullable_string')) {
    function system_config_nullable_string(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }
}

if (! function_exists('system_config_normalize_color')) {
    function system_config_normalize_color(mixed $value, string $fallback): string
    {
        $hex = strtoupper(ltrim(trim((string) ($value ?? '')), '#'));

        if (preg_match('/^[A-F0-9]{3}$/', $hex) === 1) {
            return sprintf('#%1$s%1$s%2$s%2$s%3$s%3$s', $hex[0], $hex[1], $hex[2]);
        }

        if (preg_match('/^[A-F0-9]{6}$/', $hex) === 1) {
            return '#'.$hex;
        }

        return system_config_normalize_color($fallback, '#1f2937');
    }
}

if (! function_exists('system_config_logo_url')) {
    function system_config_logo_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('system_config_logo_file_path')) {
    function system_config_logo_file_path(?string $path): ?string
    {
        if ($path === null || $path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }
}

if (! function_exists('system_config_hex_to_rgb_csv')) {
    function system_config_hex_to_rgb_csv(string $hexColor): string
    {
        $hex = ltrim(system_config_normalize_color($hexColor, '#1f2937'), '#');

        return sprintf(
            '%d, %d, %d',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }
}
