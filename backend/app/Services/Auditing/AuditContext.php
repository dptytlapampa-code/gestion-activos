<?php

namespace App\Services\Auditing;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditContext
{
    private const REQUEST_ATTRIBUTE = 'audit.correlation_id';

    private ?string $fallbackCorrelationId = null;

    public function initialize(?Request $request = null): string
    {
        $request ??= request();

        if ($request instanceof Request) {
            $existing = $request->attributes->get(self::REQUEST_ATTRIBUTE);

            if (is_string($existing) && $existing !== '') {
                return $existing;
            }

            $correlationId = (string) Str::uuid();
            $request->attributes->set(self::REQUEST_ATTRIBUTE, $correlationId);

            return $correlationId;
        }

        return $this->fallbackCorrelationId ??= (string) Str::uuid();
    }

    public function current(): string
    {
        return $this->initialize();
    }

    public function use(string $correlationId): void
    {
        $request = request();

        if ($request instanceof Request) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, $correlationId);

            return;
        }

        $this->fallbackCorrelationId = $correlationId;
    }
}
