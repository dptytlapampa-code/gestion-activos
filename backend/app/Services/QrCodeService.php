<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class QrCodeService
{
    public function svg(?string $url, int $size = 170, int $margin = 1, array $context = []): ?string
    {
        $normalizedUrl = $this->normalizeUrl($url);

        if ($normalizedUrl === null) {
            return null;
        }

        try {
            $result = QrCode::size($size)
                ->margin($margin)
                ->generate($normalizedUrl);
        } catch (Throwable $exception) {
            $this->logFailure($exception, 'svg', $normalizedUrl, $size, $margin, $context);

            return null;
        }

        return is_string($result) && $result !== '' ? $result : null;
    }

    public function pngDataUri(?string $url, int $size = 120, int $margin = 1, array $context = []): ?string
    {
        $normalizedUrl = $this->normalizeUrl($url);

        if ($normalizedUrl === null) {
            return null;
        }

        try {
            $binary = QrCode::format('png')
                ->size($size)
                ->margin($margin)
                ->errorCorrection('M')
                ->generate($normalizedUrl);
        } catch (Throwable $exception) {
            $this->logFailure($exception, 'png', $normalizedUrl, $size, $margin, $context);

            return null;
        }

        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($binary);
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';

        if ($url === '') {
            return null;
        }

        return $url;
    }

    private function logFailure(Throwable $exception, string $format, string $url, int $size, int $margin, array $context): void
    {
        Log::warning('qr generation failed', array_filter([
            'format' => $format,
            'url' => $url,
            'size' => $size,
            'margin' => $margin,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'context' => $context !== [] ? $context : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));
    }
}
