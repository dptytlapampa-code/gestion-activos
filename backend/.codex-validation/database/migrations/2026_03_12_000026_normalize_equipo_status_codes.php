<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipo_statuses')) {
            return;
        }

        $definitions = [
            'OPERATIVA' => ['name' => 'Operativa', 'color' => 'green', 'is_terminal' => false],
            'PRESTADO' => ['name' => 'Prestado', 'color' => 'blue', 'is_terminal' => false],
            'EN_SERVICIO_TECNICO' => ['name' => 'En Servicio Tecnico', 'color' => 'yellow', 'is_terminal' => false],
            'FUERA_DE_SERVICIO' => ['name' => 'Fuera de Servicio', 'color' => 'orange', 'is_terminal' => false],
            'BAJA' => ['name' => 'Baja', 'color' => 'red', 'is_terminal' => true],
        ];

        $aliases = [
            'OPERATIVA' => ['OPERATIVA', 'OPERATIVO'],
            'PRESTADO' => ['PRESTADO', 'PRESTADA'],
            'EN_SERVICIO_TECNICO' => ['EN_SERVICIO_TECNICO'],
            'FUERA_DE_SERVICIO' => ['FUERA_DE_SERVICIO'],
            'BAJA' => ['BAJA'],
        ];

        DB::transaction(function () use ($definitions, $aliases): void {
            $now = now();

            foreach ($definitions as $canonicalCode => $meta) {
                $statuses = DB::table('equipo_statuses')->select(['id', 'code'])->orderBy('id')->get();

                $candidateCodes = array_map(
                    static fn (string $code): string => strtoupper(trim($code)),
                    $aliases[$canonicalCode] ?? [$canonicalCode]
                );

                $matches = $statuses
                    ->filter(static fn ($status): bool => in_array(strtoupper(trim((string) $status->code)), $candidateCodes, true))
                    ->values();

                if ($matches->isEmpty()) {
                    DB::table('equipo_statuses')->insert([
                        'code' => $canonicalCode,
                        'name' => $meta['name'],
                        'color' => $meta['color'],
                        'is_terminal' => $meta['is_terminal'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    continue;
                }

                $canonical = $matches->first(
                    static fn ($status): bool => trim((string) $status->code) === $canonicalCode
                )
                    ?? $matches->first(
                        static fn ($status): bool => strtoupper(trim((string) $status->code)) === $canonicalCode
                    )
                    ?? $matches->first();

                if ($canonical === null) {
                    continue;
                }

                DB::table('equipo_statuses')
                    ->where('id', $canonical->id)
                    ->update([
                        'code' => $canonicalCode,
                        'name' => $meta['name'],
                        'color' => $meta['color'],
                        'is_terminal' => $meta['is_terminal'],
                        'updated_at' => $now,
                    ]);

                $legacyIds = $matches
                    ->pluck('id')
                    ->filter(static fn ($id): bool => (int) $id !== (int) $canonical->id)
                    ->values();

                if ($legacyIds->isEmpty()) {
                    continue;
                }

                if (Schema::hasTable('equipos') && Schema::hasColumn('equipos', 'equipo_status_id')) {
                    DB::table('equipos')
                        ->whereIn('equipo_status_id', $legacyIds->all())
                        ->update(['equipo_status_id' => $canonical->id]);
                }

                if (Schema::hasTable('mantenimientos') && Schema::hasColumn('mantenimientos', 'estado_resultante_id')) {
                    DB::table('mantenimientos')
                        ->whereIn('estado_resultante_id', $legacyIds->all())
                        ->update(['estado_resultante_id' => $canonical->id]);
                }

                DB::table('equipo_statuses')
                    ->whereIn('id', $legacyIds->all())
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        // No-op: mantener normalizacion de codigos de estado.
    }
};

