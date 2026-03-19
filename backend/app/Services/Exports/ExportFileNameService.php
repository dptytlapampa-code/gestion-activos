<?php

namespace App\Services\Exports;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ExportFileNameService
{
    public function csv(string $prefix, ?CarbonInterface $timestamp = null): string
    {
        $safePrefix = Str::of($prefix)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        $formattedTimestamp = ($timestamp ?? now())->format('Y-m-d_H-i');

        return sprintf('%s_%s.csv', $safePrefix, $formattedTimestamp);
    }
}
