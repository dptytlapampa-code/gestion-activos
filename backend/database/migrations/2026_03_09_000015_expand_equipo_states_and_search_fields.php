<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table): void {
            $table->string('mac_address', 64)->nullable()->after('bien_patrimonial');
            $table->string('codigo_interno', 120)->nullable()->after('mac_address');
            $table->index('mac_address');
            $table->index('codigo_interno');
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE equipos DROP CONSTRAINT IF EXISTS equipos_estado_check');
        DB::statement("ALTER TABLE equipos ADD CONSTRAINT equipos_estado_check CHECK (estado::text = ANY (ARRAY['operativo'::character varying, 'prestado'::character varying, 'mantenimiento'::character varying, 'fuera_de_servicio'::character varying, 'baja'::character varying]::text[]))");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE equipos DROP CONSTRAINT IF EXISTS equipos_estado_check');
            DB::statement("ALTER TABLE equipos ADD CONSTRAINT equipos_estado_check CHECK (estado::text = ANY (ARRAY['operativo'::character varying, 'mantenimiento'::character varying, 'baja'::character varying]::text[]))");
        }

        Schema::table('equipos', function (Blueprint $table): void {
            $table->dropIndex(['mac_address']);
            $table->dropIndex(['codigo_interno']);
            $table->dropColumn(['mac_address', 'codigo_interno']);
        });
    }
};
