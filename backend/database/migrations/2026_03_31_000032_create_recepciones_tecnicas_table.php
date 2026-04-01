<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepciones_tecnicas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo', 40)->unique();

            $table->foreignId('institution_id')->constrained('institutions');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('equipo_creado_id')->nullable()->constrained('equipos')->nullOnDelete();

            $table->foreignId('procedencia_institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('procedencia_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('procedencia_office_id')->nullable()->constrained('offices')->nullOnDelete();

            $table->date('fecha_recepcion');
            $table->string('estado', 40)->default('recibido');
            $table->string('sector_receptor', 120)->default('Mesa Tecnica');

            $table->string('referencia_equipo', 255)->nullable();
            $table->string('tipo_equipo_texto', 120)->nullable();
            $table->string('marca', 120)->nullable();
            $table->string('modelo', 120)->nullable();
            $table->string('numero_serie', 120)->nullable();
            $table->string('bien_patrimonial', 120)->nullable();
            $table->string('codigo_interno_equipo', 120)->nullable();
            $table->string('procedencia_hospital', 150)->nullable();
            $table->string('procedencia_libre', 255)->nullable();

            $table->string('persona_nombre', 150);
            $table->string('persona_documento', 50)->nullable();
            $table->string('persona_telefono', 50)->nullable();
            $table->string('persona_area', 150)->nullable();
            $table->string('persona_institucion', 150)->nullable();
            $table->string('persona_relacion_equipo', 80)->nullable();

            $table->string('falla_motivo', 255)->nullable();
            $table->text('descripcion_falla')->nullable();
            $table->text('accesorios_entregados')->nullable();
            $table->text('estado_fisico_inicial')->nullable();
            $table->text('observaciones_recepcion')->nullable();
            $table->text('observaciones_internas')->nullable();
            $table->text('motivo_anulacion')->nullable();

            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('entregada_at')->nullable();
            $table->timestamp('anulada_at')->nullable();

            $table->timestamps();

            $table->index(['institution_id', 'fecha_recepcion'], 'recepciones_tecnicas_institution_fecha_index');
            $table->index(['estado', 'fecha_recepcion'], 'recepciones_tecnicas_estado_fecha_index');
            $table->index('numero_serie', 'recepciones_tecnicas_numero_serie_index');
            $table->index('bien_patrimonial', 'recepciones_tecnicas_bien_patrimonial_index');
            $table->index('codigo_interno_equipo', 'recepciones_tecnicas_codigo_interno_equipo_index');
            $table->index('persona_nombre', 'recepciones_tecnicas_persona_nombre_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepciones_tecnicas');
    }
};
