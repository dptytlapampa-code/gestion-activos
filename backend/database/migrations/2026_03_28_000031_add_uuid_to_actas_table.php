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
        Schema::table('actas', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('actas')
            ->select('id')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(100, function ($actas): void {
                foreach ($actas as $acta) {
                    DB::table('actas')
                        ->where('id', $acta->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('actas', function (Blueprint $table): void {
            $table->unique('uuid', 'actas_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table): void {
            $table->dropUnique('actas_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
