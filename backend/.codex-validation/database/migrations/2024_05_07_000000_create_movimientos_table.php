<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('equipo_id');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('tipo_movimiento', 50)->index();
            $table->timestamp('fecha')->index();

            $table->unsignedBigInteger('institucion_origen_id')->nullable();
            $table->unsignedBigInteger('servicio_origen_id')->nullable();
            $table->unsignedBigInteger('oficina_origen_id')->nullable();

            $table->unsignedBigInteger('institucion_destino_id')->nullable();
            $table->unsignedBigInteger('servicio_destino_id')->nullable();
            $table->unsignedBigInteger('oficina_destino_id')->nullable();

            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['equipo_id', 'fecha']);

            $table->foreign('equipo_id', 'fk_movimientos_equipo')
                ->references('id')
                ->on('equipos')
                ->onDelete('cascade');

            $table->foreign('user_id', 'fk_movimientos_user')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('institucion_origen_id', 'fk_movimientos_inst_orig')
                ->references('id')
                ->on('institutions')
                ->onDelete('set null');

            $table->foreign('servicio_origen_id', 'fk_movimientos_serv_orig')
                ->references('id')
                ->on('services')
                ->onDelete('set null');

            $table->foreign('oficina_origen_id', 'fk_movimientos_ofic_orig')
                ->references('id')
                ->on('offices')
                ->onDelete('set null');

            $table->foreign('institucion_destino_id', 'fk_movimientos_inst_dest')
                ->references('id')
                ->on('institutions')
                ->onDelete('set null');

            $table->foreign('servicio_destino_id', 'fk_movimientos_serv_dest')
                ->references('id')
                ->on('services')
                ->onDelete('set null');

            $table->foreign('oficina_destino_id', 'fk_movimientos_ofic_dest')
                ->references('id')
                ->on('offices')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
