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
            $table->foreignId('office_id')
                ->constrained('offices')
                ->restrictOnDelete();
            $table->string('tipo_equipo');
            $table->string('marca');
            $table->string('modelo');
            $table->string('numero_serie')->unique();
            $table->string('bien_patrimonial')->unique();
            $table->text('descripcion')->nullable();
            $table->string('estado');
            $table->date('fecha_ingreso');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['office_id', 'estado']);
            $table->index('tipo_equipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
