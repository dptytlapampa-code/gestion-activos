<?php

namespace App\Services;

use App\Models\Equipo;
use Illuminate\Support\Facades\DB;

class EquipoInternalCodeService
{
    private const COUNTER_TABLE = 'internal_code_sequences';
    private const COUNTER_RESOURCE = 'equipos';

    /**
     * Historical internal codes are preserved as stored for traceability.
     * Only newly generated codes use the current minimum 6-digit padding.
     */
    public function next(): string
    {
        return DB::transaction(function (): string {
            $counter = DB::table(self::COUNTER_TABLE)
                ->where('resource', self::COUNTER_RESOURCE)
                ->lockForUpdate()
                ->first(['last_value']);

            $timestamp = now();
            $currentValue = (int) ($counter->last_value ?? 0);

            if ($counter === null) {
                DB::table(self::COUNTER_TABLE)->insert([
                    'resource' => self::COUNTER_RESOURCE,
                    'last_value' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            $nextValue = $currentValue + 1;

            DB::table(self::COUNTER_TABLE)
                ->where('resource', self::COUNTER_RESOURCE)
                ->update([
                    'last_value' => $nextValue,
                    'updated_at' => $timestamp,
                ]);

            return Equipo::formatCodigoInterno($nextValue);
        }, 3);
    }
}
