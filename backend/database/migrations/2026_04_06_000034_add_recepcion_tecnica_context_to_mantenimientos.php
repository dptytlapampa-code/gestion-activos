<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table): void {
            $table->foreignId('tecnico_responsable_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('problema_reportado')->nullable()->after('detalle');
            $table->text('diagnostico')->nullable()->after('problema_reportado');
            $table->text('solucion_aplicada')->nullable()->after('diagnostico');
            $table->text('informe_tecnico')->nullable()->after('solucion_aplicada');
            $table->foreignId('recepcion_tecnica_id')
                ->nullable()
                ->after('mantenimiento_externo_id')
                ->constrained('recepciones_tecnicas')
                ->nullOnDelete();
            $table->unsignedInteger('duracion_minutos')->nullable()->after('dias_en_servicio');
            $table->string('condicion_egreso', 40)->nullable()->after('duracion_minutos');
        });

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS mantenimientos_recepcion_tecnica_unique_idx
            ON mantenimientos (recepcion_tecnica_id)
            WHERE recepcion_tecnica_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS mantenimientos_recepcion_tecnica_unique_idx');

        Schema::table('mantenimientos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recepcion_tecnica_id');
            $table->dropConstrainedForeignId('tecnico_responsable_id');
            $table->dropColumn([
                'problema_reportado',
                'diagnostico',
                'solucion_aplicada',
                'informe_tecnico',
                'duracion_minutos',
                'condicion_egreso',
            ]);
        });
    }
};
