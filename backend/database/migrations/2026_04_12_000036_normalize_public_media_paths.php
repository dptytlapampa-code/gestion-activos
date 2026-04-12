<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->select(['id', 'logo_path', 'logo_institucional', 'logo_pdf'])
                ->orderBy('id')
                ->get()
                ->each(function (object $row): void {
                    $normalizedLogoInstitucional = $this->normalizePublicPath($row->logo_institucional ?: $row->logo_path);
                    $normalizedLogoPdf = $this->normalizePublicPath($row->logo_pdf);

                    $payload = [
                        'logo_path' => $normalizedLogoInstitucional,
                        'logo_institucional' => $normalizedLogoInstitucional,
                        'logo_pdf' => $normalizedLogoPdf,
                    ];

                    if (
                        $payload['logo_path'] !== $row->logo_path
                        || $payload['logo_institucional'] !== $row->logo_institucional
                        || $payload['logo_pdf'] !== $row->logo_pdf
                    ) {
                        DB::table('system_settings')
                            ->where('id', $row->id)
                            ->update($payload);
                    }
                });
        }

        if (Schema::hasTable('tipos_equipos')) {
            DB::table('tipos_equipos')
                ->select(['id', 'image_path'])
                ->orderBy('id')
                ->get()
                ->each(function (object $row): void {
                    $normalizedImagePath = $this->normalizePublicPath($row->image_path);

                    if ($normalizedImagePath !== $row->image_path) {
                        DB::table('tipos_equipos')
                            ->where('id', $row->id)
                            ->update(['image_path' => $normalizedImagePath]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Data normalization is intentionally irreversible.
    }

    private function normalizePublicPath(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $rawValue = trim((string) $value);

        if ($rawValue === '') {
            return null;
        }

        $wasUrl = filter_var($rawValue, FILTER_VALIDATE_URL) !== false;
        $normalized = str_replace('\\', '/', rawurldecode($rawValue));

        if ($wasUrl) {
            $parsedPath = parse_url($normalized, PHP_URL_PATH);

            if (! is_string($parsedPath) || trim($parsedPath) === '') {
                return null;
            }

            $normalized = $parsedPath;
        }

        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        $relativePath = null;

        if (preg_match('#(?:^|/)(?:storage/app/public|public/storage)/(.*)$#i', $normalized, $matches) === 1) {
            $relativePath = $matches[1] ?? null;
        } elseif (str_starts_with($normalized, '/storage/')) {
            $relativePath = substr($normalized, strlen('/storage/'));
        } elseif (str_starts_with($normalized, 'storage/')) {
            $relativePath = substr($normalized, strlen('storage/'));
        } elseif (! $wasUrl && preg_match('#^[A-Za-z]:/#', $normalized) !== 1) {
            $relativePath = ltrim($normalized, '/');
        }

        if (! is_string($relativePath) || trim($relativePath) === '') {
            return null;
        }

        $segments = array_values(array_filter(
            explode('/', trim($relativePath, '/')),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.'
        ));

        if ($segments === [] || in_array('..', $segments, true)) {
            return null;
        }

        return implode('/', $segments);
    }
};
