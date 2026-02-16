<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->string('tipo_movimiento', 50)->index();
            $table->timestamp('fecha')->index();

            $table->foreignId('institucion_origen_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('servicio_origen_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('oficina_origen_id')->nullable()->constrained('offices')->nullOnDelete();

            $table->foreignId('institucion_destino_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('servicio_destino_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('oficina_destino_id')->nullable()->constrained('offices')->nullOnDelete();

            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['equipo_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
