<?php

namespace App\Enums;

enum ExportScope: string
{
    case RESULTS = 'results';
    case ALL = 'all';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $scope): string => $scope->value,
            self::cases()
        );
    }

    public function isAll(): bool
    {
        return $this === self::ALL;
    }
}
