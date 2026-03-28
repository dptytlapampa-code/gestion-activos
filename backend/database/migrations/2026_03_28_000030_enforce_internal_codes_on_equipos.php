<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COUNTER_TABLE = 'internal_code_sequences';
    private const COUNTER_RESOURCE = 'equipos';
    private const INTERNAL_CODE_PREFIX = 'GA-EQ-';
    private const INTERNAL_CODE_PAD = 9;

    public function up(): void
    {
        if (! Schema::hasTable('equipos')) {
            return;
        }

        if (! Schema::hasTable(self::COUNTER_TABLE)) {
            Schema::create(self::COUNTER_TABLE, function (Blueprint $table): void {
                $table->string('resource', 100)->primary();
                $table->unsignedBigInteger('last_value')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('equipos', function (Blueprint $table): void {
            $table->string('numero_serie', 120)->nullable()->change();
            $table->string('bien_patrimonial', 120)->nullable()->change();
        });

        $highestManagedSequence = $this->normalizeExistingInternalCodes();
        $highestManagedSequence = $this->backfillMissingInternalCodes($highestManagedSequence);
        $this->syncCounter($highestManagedSequence);

        if ($this->indexExists('equipos', 'equipos_codigo_interno_index')) {
            Schema::table('equipos', function (Blueprint $table): void {
                $table->dropIndex('equipos_codigo_interno_index');
            });
        }

        Schema::table('equipos', function (Blueprint $table): void {
            $table->string('codigo_interno', 120)->nullable(false)->change();
        });

        if (! $this->indexExists('equipos', 'equipos_codigo_interno_unique')) {
            Schema::table('equipos', function (Blueprint $table): void {
                $table->unique('codigo_interno', 'equipos_codigo_interno_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipos')) {
            Schema::dropIfExists(self::COUNTER_TABLE);

            return;
        }

        if ($this->indexExists('equipos', 'equipos_codigo_interno_unique')) {
            Schema::table('equipos', function (Blueprint $table): void {
                $table->dropUnique('equipos_codigo_interno_unique');
            });
        }

        if (! $this->indexExists('equipos', 'equipos_codigo_interno_index')) {
            Schema::table('equipos', function (Blueprint $table): void {
                $table->index('codigo_interno', 'equipos_codigo_interno_index');
            });
        }

        Schema::table('equipos', function (Blueprint $table): void {
            $table->string('codigo_interno', 120)->nullable()->change();
            $table->string('numero_serie', 120)->nullable(false)->change();
            $table->string('bien_patrimonial', 120)->nullable(false)->change();
        });

        Schema::dropIfExists(self::COUNTER_TABLE);
    }

    private function normalizeExistingInternalCodes(): int
    {
        $highestManagedSequence = 0;
        $seenCodes = [];

        $equipos = DB::table('equipos')
            ->select(['id', 'codigo_interno'])
            ->orderBy('id')
            ->get();

        foreach ($equipos as $equipo) {
            $normalizedCode = $this->normalizeCode($equipo->codigo_interno);

            if ($normalizedCode === null) {
                if ($equipo->codigo_interno !== null) {
                    DB::table('equipos')
                        ->where('id', $equipo->id)
                        ->update(['codigo_interno' => null]);
                }

                continue;
            }

            if (array_key_exists($normalizedCode, $seenCodes)) {
                DB::table('equipos')
                    ->where('id', $equipo->id)
                    ->update(['codigo_interno' => null]);

                continue;
            }

            if ($normalizedCode !== $equipo->codigo_interno) {
                DB::table('equipos')
                    ->where('id', $equipo->id)
                    ->update(['codigo_interno' => $normalizedCode]);
            }

            $seenCodes[$normalizedCode] = true;
            $managedSequence = $this->managedSequenceFromCode($normalizedCode);

            if ($managedSequence !== null) {
                $highestManagedSequence = max($highestManagedSequence, $managedSequence);
            }
        }

        return $highestManagedSequence;
    }

    private function backfillMissingInternalCodes(int $highestManagedSequence): int
    {
        $missingIds = DB::table('equipos')
            ->whereNull('codigo_interno')
            ->orderBy('id')
            ->pluck('id');

        foreach ($missingIds as $id) {
            $highestManagedSequence++;

            DB::table('equipos')
                ->where('id', $id)
                ->update([
                    'codigo_interno' => $this->formatInternalCode($highestManagedSequence),
                ]);
        }

        return $highestManagedSequence;
    }

    private function syncCounter(int $lastValue): void
    {
        $timestamp = now();

        $exists = DB::table(self::COUNTER_TABLE)
            ->where('resource', self::COUNTER_RESOURCE)
            ->exists();

        if ($exists) {
            DB::table(self::COUNTER_TABLE)
                ->where('resource', self::COUNTER_RESOURCE)
                ->update([
                    'last_value' => $lastValue,
                    'updated_at' => $timestamp,
                ]);

            return;
        }

        DB::table(self::COUNTER_TABLE)->insert([
            'resource' => self::COUNTER_RESOURCE,
            'last_value' => $lastValue,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function normalizeCode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function managedSequenceFromCode(string $code): ?int
    {
        $pattern = '/^'.preg_quote(self::INTERNAL_CODE_PREFIX, '/').'(\d{'.self::INTERNAL_CODE_PAD.'})$/';

        if (! preg_match($pattern, $code, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function formatInternalCode(int $sequence): string
    {
        return sprintf('%s%0'.self::INTERNAL_CODE_PAD.'d', self::INTERNAL_CODE_PREFIX, $sequence);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
