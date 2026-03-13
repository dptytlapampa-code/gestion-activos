<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas', function (Blueprint $table): void {
            $table->foreignId('institution_destino_id')->nullable()->after('institution_id')->constrained('institutions')->nullOnDelete();
            $table->foreignId('service_origen_id')->nullable()->after('institution_destino_id')->constrained('services')->nullOnDelete();
            $table->foreignId('office_origen_id')->nullable()->after('service_origen_id')->constrained('offices')->nullOnDelete();
            $table->foreignId('service_destino_id')->nullable()->after('office_origen_id')->constrained('services')->nullOnDelete();
            $table->foreignId('office_destino_id')->nullable()->after('service_destino_id')->constrained('offices')->nullOnDelete();
            $table->string('motivo_baja', 255)->nullable()->after('receptor_dependencia');
            $table->json('evento_payload')->nullable()->after('motivo_baja');

            $table->index(['service_origen_id', 'office_origen_id'], 'actas_origen_idx');
            $table->index(['service_destino_id', 'office_destino_id'], 'actas_destino_idx');
            $table->index(['institution_id', 'institution_destino_id'], 'actas_instituciones_idx');
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table): void {
            $table->dropIndex('actas_origen_idx');
            $table->dropIndex('actas_destino_idx');
            $table->dropIndex('actas_instituciones_idx');
            $table->dropColumn('evento_payload');
            $table->dropColumn('motivo_baja');
            $table->dropConstrainedForeignId('office_destino_id');
            $table->dropConstrainedForeignId('service_destino_id');
            $table->dropConstrainedForeignId('office_origen_id');
            $table->dropConstrainedForeignId('service_origen_id');
            $table->dropConstrainedForeignId('institution_destino_id');
        });
    }
};
