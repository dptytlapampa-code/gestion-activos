<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('tipo', 20);
            $table->date('fecha');
            $table->string('receptor_nombre');
            $table->string('receptor_dni')->nullable();
            $table->string('receptor_cargo')->nullable();
            $table->string('receptor_dependencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'fecha']);
            $table->index(['institution_id', 'tipo']);
        });

        Schema::create('acta_equipo', function (Blueprint $table): void {
            $table->foreignId('acta_id')->constrained('actas')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->text('accesorios')->nullable();

            $table->primary(['acta_id', 'equipo_id']);
            $table->index('equipo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acta_equipo');
        Schema::dropIfExists('actas');
    }
};
