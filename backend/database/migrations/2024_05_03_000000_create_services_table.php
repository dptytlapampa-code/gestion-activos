<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')
                ->constrained('institutions')
                ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
