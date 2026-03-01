<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE equipos DROP CONSTRAINT IF EXISTS equipos_estado_check');
        DB::statement("ALTER TABLE equipos ADD CONSTRAINT equipos_estado_check CHECK (estado::text = ANY (ARRAY['operativo'::character varying, 'mantenimiento'::character varying, 'baja'::character varying]::text[]))");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE equipos DROP CONSTRAINT IF EXISTS equipos_estado_check');
        DB::statement("ALTER TABLE equipos ADD CONSTRAINT equipos_estado_check CHECK (estado::text = ANY (ARRAY['operativo'::character varying, 'mantenimiento'::character varying, 'baja'::character varying]::text[]))");
    }
};
