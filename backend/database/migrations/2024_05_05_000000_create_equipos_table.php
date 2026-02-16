<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 100);
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('nro_serie', 120)->unique();
            $table->string('bien_patrimonial', 120)->unique();
            $table->enum('estado', ['operativo', 'mantenimiento', 'baja'])->default('operativo');
            $table->date('fecha_ingreso');
            $table->foreignId('oficina_id')->constrained('offices')->restrictOnDelete();
            $table->timestamps();

            $table->index('tipo');
            $table->index('marca');
            $table->index('modelo');
            $table->index('estado');
            $table->index('oficina_id');
            $table->index(['tipo', 'marca', 'modelo', 'estado'], 'equipos_busqueda_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
