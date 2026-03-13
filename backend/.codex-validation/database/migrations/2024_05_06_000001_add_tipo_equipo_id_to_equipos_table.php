<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table): void {
            $table->foreignId('tipo_equipo_id')
                ->nullable()
                ->after('tipo')
                ->constrained('tipos_equipos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tipo_equipo_id');
        });
    }
};
