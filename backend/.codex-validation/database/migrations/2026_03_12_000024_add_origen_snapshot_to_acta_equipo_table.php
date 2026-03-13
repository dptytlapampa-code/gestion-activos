<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acta_equipo', function (Blueprint $table): void {
            $table->foreignId('institucion_origen_id')
                ->nullable()
                ->after('equipo_id')
                ->constrained('institutions')
                ->nullOnDelete();
            $table->string('institucion_origen_nombre', 255)->nullable()->after('institucion_origen_id');
            $table->foreignId('servicio_origen_id')
                ->nullable()
                ->after('institucion_origen_nombre')
                ->constrained('services')
                ->nullOnDelete();
            $table->string('servicio_origen_nombre', 255)->nullable()->after('servicio_origen_id');
            $table->foreignId('oficina_origen_id')
                ->nullable()
                ->after('servicio_origen_nombre')
                ->constrained('offices')
                ->nullOnDelete();
            $table->string('oficina_origen_nombre', 255)->nullable()->after('oficina_origen_id');

            $table->index(
                ['acta_id', 'institucion_origen_id', 'servicio_origen_id', 'oficina_origen_id'],
                'acta_equipo_origen_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('acta_equipo', function (Blueprint $table): void {
            $table->dropIndex('acta_equipo_origen_idx');
            $table->dropColumn('oficina_origen_nombre');
            $table->dropConstrainedForeignId('oficina_origen_id');
            $table->dropColumn('servicio_origen_nombre');
            $table->dropConstrainedForeignId('servicio_origen_id');
            $table->dropColumn('institucion_origen_nombre');
            $table->dropConstrainedForeignId('institucion_origen_id');
        });
    }
};

