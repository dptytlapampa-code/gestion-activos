<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
