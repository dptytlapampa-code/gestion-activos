<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS recepciones_tecnicas_equipo_abierto_idx');
        DB::statement("
            CREATE UNIQUE INDEX recepciones_tecnicas_equipo_abierto_idx
            ON recepciones_tecnicas (coalesce(equipo_id, equipo_creado_id))
            WHERE coalesce(equipo_id, equipo_creado_id) IS NOT NULL
              AND estado IN ('recibido', 'en_diagnostico', 'en_reparacion', 'en_espera_repuesto', 'listo_para_entregar')
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS recepciones_tecnicas_equipo_abierto_idx');
        DB::statement("
            CREATE UNIQUE INDEX recepciones_tecnicas_equipo_abierto_idx
            ON recepciones_tecnicas (equipo_id)
            WHERE equipo_id IS NOT NULL
              AND estado IN ('recibido', 'en_diagnostico', 'en_reparacion', 'en_espera_repuesto', 'listo_para_entregar')
        ");
    }
};
