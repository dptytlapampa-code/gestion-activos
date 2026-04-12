<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class TipoEquipoImageService
{
    private const DIRECTORY = 'tipos-equipos';

    public function __construct(private readonly PublicMediaService $publicMediaService)
    {
    }

    public function storeUploadedImage(UploadedFile $image): string
    {
        return $this->publicMediaService->storeUploadedFile($image, self::DIRECTORY, 'imagen_png');
    }

    public function deleteImage(?string $path, ?string $exceptPath = null): void
    {
        $this->publicMediaService->delete($path, $exceptPath);
    }
}
