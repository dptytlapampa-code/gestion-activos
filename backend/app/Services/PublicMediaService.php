<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicMediaService
{
    private const DISK = 'public';

    public function normalizeStoredPath(mixed $value): ?string
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
        } elseif (Str::startsWith($normalized, '/storage/')) {
            $relativePath = Str::after($normalized, '/storage/');
        } elseif (Str::startsWith($normalized, 'storage/')) {
            $relativePath = Str::after($normalized, 'storage/');
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

    public function exists(mixed $path): bool
    {
        $normalizedPath = $this->normalizeStoredPath($path);

        return $normalizedPath !== null && Storage::disk(self::DISK)->exists($normalizedPath);
    }

    public function url(mixed $path): ?string
    {
        $normalizedPath = $this->normalizeStoredPath($path);

        if ($normalizedPath === null || ! Storage::disk(self::DISK)->exists($normalizedPath)) {
            return null;
        }

        $url = Storage::disk(self::DISK)->url($normalizedPath);
        $version = $this->lastModified($normalizedPath);

        if ($version === null) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.$version;
    }

    public function path(mixed $path): ?string
    {
        $normalizedPath = $this->normalizeStoredPath($path);

        if ($normalizedPath === null || ! Storage::disk(self::DISK)->exists($normalizedPath)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($normalizedPath);
    }

    public function storeUploadedFile(UploadedFile $file, string $directory, string $validationField): string
    {
        $storedPath = $file->store($directory, self::DISK);

        return $this->assertStoredPath($storedPath, $validationField);
    }

    public function storeUploadedFileAs(UploadedFile $file, string $directory, string $filename, string $validationField): string
    {
        $storedPath = $file->storeAs($directory, $filename, self::DISK);

        return $this->assertStoredPath($storedPath, $validationField);
    }

    public function delete(mixed $path, mixed $exceptPath = null): void
    {
        $normalizedPath = $this->normalizeStoredPath($path);
        $normalizedExceptPath = $this->normalizeStoredPath($exceptPath);

        if ($normalizedPath === null || $normalizedPath === $normalizedExceptPath) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($normalizedPath)) {
            Storage::disk(self::DISK)->delete($normalizedPath);
        }
    }

    private function assertStoredPath(mixed $storedPath, string $validationField): string
    {
        $normalizedPath = $this->normalizeStoredPath($storedPath);

        if ($normalizedPath === null) {
            throw ValidationException::withMessages([
                $validationField => 'No fue posible guardar el archivo seleccionado.',
            ]);
        }

        return $normalizedPath;
    }

    private function lastModified(string $path): ?int
    {
        try {
            return Storage::disk(self::DISK)->lastModified($path);
        } catch (\Throwable) {
            return null;
        }
    }
}
