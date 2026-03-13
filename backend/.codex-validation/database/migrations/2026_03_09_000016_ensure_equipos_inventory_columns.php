<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipos')) {
            return;
        }

        $needsMac = ! Schema::hasColumn('equipos', 'mac_address');
        $needsCodigoInterno = ! Schema::hasColumn('equipos', 'codigo_interno');

        if ($needsMac || $needsCodigoInterno) {
            Schema::table('equipos', function (Blueprint $table) use ($needsMac, $needsCodigoInterno): void {
                if ($needsMac) {
                    $table->string('mac_address', 50)->nullable()->after('bien_patrimonial');
                }

                if ($needsCodigoInterno) {
                    $afterColumn = $needsMac ? 'mac_address' : 'bien_patrimonial';
                    $table->string('codigo_interno', 120)->nullable()->after($afterColumn);
                }
            });
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE equipos DROP CONSTRAINT IF EXISTS equipos_estado_check');
        DB::statement("ALTER TABLE equipos ADD CONSTRAINT equipos_estado_check CHECK (estado::text = ANY (ARRAY['operativo'::character varying, 'prestado'::character varying, 'mantenimiento'::character varying, 'fuera_de_servicio'::character varying, 'baja'::character varying]::text[]))");
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipos')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE equipos DROP CONSTRAINT IF EXISTS equipos_estado_check');
            DB::statement("ALTER TABLE equipos ADD CONSTRAINT equipos_estado_check CHECK (estado::text = ANY (ARRAY['operativo'::character varying, 'mantenimiento'::character varying, 'baja'::character varying]::text[]))");
        }
    }
};

