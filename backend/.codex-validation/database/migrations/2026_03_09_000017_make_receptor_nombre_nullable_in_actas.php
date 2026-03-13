<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('actas')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE actas ALTER COLUMN receptor_nombre DROP NOT NULL');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE actas MODIFY receptor_nombre VARCHAR(255) NULL');

            return;
        }

        Schema::table('actas', function (Blueprint $table): void {
            $table->string('receptor_nombre')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('actas')) {
            return;
        }

        DB::table('actas')->whereNull('receptor_nombre')->update(['receptor_nombre' => '']);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE actas ALTER COLUMN receptor_nombre SET NOT NULL');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE actas MODIFY receptor_nombre VARCHAR(255) NOT NULL');

            return;
        }

        Schema::table('actas', function (Blueprint $table): void {
            $table->string('receptor_nombre')->nullable(false)->change();
        });
    }
};
