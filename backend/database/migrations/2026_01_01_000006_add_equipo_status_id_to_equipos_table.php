<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->foreignId('equipo_status_id')->nullable()->after('estado')->constrained('equipo_statuses')->restrictOnDelete();
            $table->index('equipo_status_id');
        });

        DB::table('equipos')
            ->whereNull('equipo_status_id')
            ->update(['equipo_status_id' => DB::table('equipo_statuses')->where('code', 'OPERATIVA')->value('id')]);

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE equipos ALTER COLUMN equipo_status_id SET NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE equipos ALTER COLUMN equipo_status_id DROP NOT NULL');
        }

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_status_id');
        });
    }
};
