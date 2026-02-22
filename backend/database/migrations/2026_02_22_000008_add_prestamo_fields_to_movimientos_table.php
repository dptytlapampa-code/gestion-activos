<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table): void {
            $table->string('receptor_nombre')->nullable()->after('oficina_destino_id');
            $table->string('receptor_dni', 50)->nullable()->after('receptor_nombre');
            $table->string('receptor_cargo')->nullable()->after('receptor_dni');
            $table->date('fecha_inicio_prestamo')->nullable()->after('receptor_cargo');
            $table->date('fecha_estimada_devolucion')->nullable()->after('fecha_inicio_prestamo');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table): void {
            $table->dropColumn([
                'receptor_nombre',
                'receptor_dni',
                'receptor_cargo',
                'fecha_inicio_prestamo',
                'fecha_estimada_devolucion',
            ]);
        });
    }
};
