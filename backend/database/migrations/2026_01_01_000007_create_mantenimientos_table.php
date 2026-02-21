<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('tipo', 20);
            $table->string('titulo', 150);
            $table->text('detalle');
            $table->string('proveedor', 150)->nullable();
            $table->date('fecha_ingreso_st')->nullable();
            $table->date('fecha_egreso_st')->nullable();
            $table->integer('dias_en_servicio')->nullable();
            $table->foreignId('estado_resultante_id')->nullable()->constrained('equipo_statuses')->nullOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'fecha']);
            $table->index(['equipo_id', 'fecha']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
