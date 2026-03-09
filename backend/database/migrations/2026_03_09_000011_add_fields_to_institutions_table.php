<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->string('localidad', 150)->nullable();
            $table->string('provincia', 150)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('responsable', 255)->nullable();
            $table->enum('tipo', [
                'hospital',
                'centro_salud',
                'administrativo',
                'deposito',
                'otro',
            ])->default('otro');
            $table->enum('estado', [
                'activo',
                'inactivo',
            ])->default('activo');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn([
                'localidad',
                'provincia',
                'direccion',
                'telefono',
                'email',
                'responsable',
                'tipo',
                'estado',
            ]);
        });
    }
};
