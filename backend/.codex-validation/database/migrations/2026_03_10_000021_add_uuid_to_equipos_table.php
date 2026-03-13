<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipos')) {
            return;
        }

        if (! Schema::hasColumn('equipos', 'uuid')) {
            Schema::table('equipos', function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        DB::table('equipos')
            ->select(['id'])
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(200, function ($equipos): void {
                foreach ($equipos as $equipo) {
                    DB::table('equipos')
                        ->where('id', $equipo->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('equipos', function (Blueprint $table): void {
            $table->unique('uuid', 'equipos_uuid_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipos') || ! Schema::hasColumn('equipos', 'uuid')) {
            return;
        }

        Schema::table('equipos', function (Blueprint $table): void {
            $table->dropUnique('equipos_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
