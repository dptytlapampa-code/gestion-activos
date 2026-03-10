<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas', function (Blueprint $table): void {
            $table->string('status', 20)->default('activa');
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulada_at')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->index('status', 'actas_status_idx');
        });

        DB::table('actas')->whereNull('status')->update(['status' => 'activa']);
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table): void {
            $table->dropIndex('actas_status_idx');
            $table->dropColumn('motivo_anulacion');
            $table->dropColumn('anulada_at');
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropColumn('status');
        });
    }
};