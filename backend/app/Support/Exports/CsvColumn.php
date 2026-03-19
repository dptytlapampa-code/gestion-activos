<?php

namespace App\Support\Exports;

use Closure;
use DateTimeInterface;
use Stringable;

final readonly class CsvColumn
{
    public function __construct(
        public string $heading,
        private Closure $resolver,
    ) {}

    public function value(mixed $row): string
    {
        $value = ($this->resolver)($row);

        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Si' : 'No',
            $value instanceof DateTimeInterface => $value->format('d/m/Y H:i'),
            $value instanceof Stringable, is_scalar($value) => trim((string) $value),
            default => $this->normalizeComplexValue($value),
        };
    }

    private function normalizeComplexValue(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }
}
