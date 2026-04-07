<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recepciones_tecnicas', function (Blueprint $table): void {
            $table->foreignId('recibido_por')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('cerrado_por')
                ->nullable()
                ->after('anulada_por')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('ingresado_at')->nullable()->after('fecha_recepcion');
            $table->text('diagnostico')->nullable()->after('observaciones_internas');
            $table->text('accion_realizada')->nullable()->after('diagnostico');
            $table->text('solucion_aplicada')->nullable()->after('accion_realizada');
            $table->text('informe_tecnico')->nullable()->after('solucion_aplicada');
            $table->text('observaciones_cierre')->nullable()->after('informe_tecnico');
            $table->string('persona_retiro_nombre', 150)->nullable()->after('observaciones_cierre');
            $table->string('persona_retiro_documento', 50)->nullable()->after('persona_retiro_nombre');
            $table->string('persona_retiro_cargo', 150)->nullable()->after('persona_retiro_documento');
            $table->string('condicion_egreso', 40)->nullable()->after('persona_retiro_cargo');
        });

        $this->normalizeLegacyStatuses();
        $this->backfillReceptionOperatorsAndTimestamps();
        $this->createIndexes();
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS recepciones_tecnicas_equipo_abierto_idx');
        DB::statement('DROP INDEX IF EXISTS recepciones_tecnicas_ingresado_at_idx');
        DB::statement('DROP INDEX IF EXISTS recepciones_tecnicas_entregada_at_idx');

        Schema::table('recepciones_tecnicas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cerrado_por');
            $table->dropConstrainedForeignId('recibido_por');
            $table->dropColumn([
                'ingresado_at',
                'diagnostico',
                'accion_realizada',
                'solucion_aplicada',
                'informe_tecnico',
                'observaciones_cierre',
                'persona_retiro_nombre',
                'persona_retiro_documento',
                'persona_retiro_cargo',
                'condicion_egreso',
            ]);
        });
    }

    private function normalizeLegacyStatuses(): void
    {
        $mappings = [
            'en_revision' => 'en_diagnostico',
            'pendiente_repuesto' => 'en_espera_repuesto',
            'reparado' => 'listo_para_entregar',
            'anulado' => 'cancelado',
        ];

        foreach ($mappings as $legacy => $normalized) {
            DB::table('recepciones_tecnicas')
                ->where('estado', $legacy)
                ->update(['estado' => $normalized]);
        }
    }

    private function backfillReceptionOperatorsAndTimestamps(): void
    {
        DB::table('recepciones_tecnicas')
            ->whereNull('recibido_por')
            ->update(['recibido_por' => DB::raw('created_by')]);

        DB::table('recepciones_tecnicas')
            ->select(['id', 'fecha_recepcion', 'created_at'])
            ->orderBy('id')
            ->each(function (object $recepcion): void {
                $ingresadoAt = $recepcion->fecha_recepcion !== null
                    ? Carbon::parse((string) $recepcion->fecha_recepcion)->startOfDay()
                    : ($recepcion->created_at !== null
                        ? Carbon::parse((string) $recepcion->created_at)
                        : now());

                DB::table('recepciones_tecnicas')
                    ->where('id', $recepcion->id)
                    ->update(['ingresado_at' => $ingresadoAt]);
            });
    }

    private function createIndexes(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS recepciones_tecnicas_ingresado_at_idx ON recepciones_tecnicas (ingresado_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS recepciones_tecnicas_entregada_at_idx ON recepciones_tecnicas (entregada_at)');
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS recepciones_tecnicas_equipo_abierto_idx
            ON recepciones_tecnicas (equipo_id)
            WHERE equipo_id IS NOT NULL
              AND estado IN ('recibido', 'en_diagnostico', 'en_reparacion', 'en_espera_repuesto', 'listo_para_entregar')
        ");
    }
};
