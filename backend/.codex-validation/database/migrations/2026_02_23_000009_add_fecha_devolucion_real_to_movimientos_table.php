<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table): void {
            $table->timestamp('fecha_devolucion_real')->nullable()->after('fecha_estimada_devolucion')->index();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table): void {
            $table->dropColumn('fecha_devolucion_real');
        });
    }
};
