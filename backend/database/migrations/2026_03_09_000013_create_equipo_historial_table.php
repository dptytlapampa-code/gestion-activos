<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_historial', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo_evento', 40);
            $table->foreignId('acta_id')->constrained('actas')->cascadeOnDelete();
            $table->string('estado_anterior', 40)->nullable();
            $table->string('estado_nuevo', 40)->nullable();
            $table->foreignId('institucion_anterior')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('institucion_nueva')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('servicio_anterior')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('servicio_nuevo')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('oficina_anterior')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('oficina_nueva')->nullable()->constrained('offices')->nullOnDelete();
            $table->dateTime('fecha');
            $table->text('observaciones')->nullable();

            $table->index(['equipo_id', 'fecha']);
            $table->index(['acta_id', 'tipo_evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_historial');
    }
};
