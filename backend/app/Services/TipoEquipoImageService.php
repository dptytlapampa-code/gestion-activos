<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TipoEquipoImageService
{
    private const DISK = 'public';

    private const DIRECTORY = 'tipos-equipos';

    public function storeUploadedImage(UploadedFile $image): string
    {
        $path = $image->store(self::DIRECTORY, self::DISK);

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'imagen_png' => 'No fue posible guardar la imagen PNG seleccionada.',
            ]);
        }

        return $path;
    }

    public function deleteImage(?string $path, ?string $exceptPath = null): void
    {
        if ($path === null || $path === '' || $path === $exceptPath) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
