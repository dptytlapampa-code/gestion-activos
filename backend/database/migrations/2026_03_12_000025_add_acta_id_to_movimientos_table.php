<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('movimientos', 'acta_id')) {
            Schema::table('movimientos', function (Blueprint $table): void {
                $table->foreignId('acta_id')->nullable()->after('user_id')->constrained('actas')->nullOnDelete();
                $table->index(['acta_id', 'fecha'], 'movimientos_acta_fecha_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('movimientos', 'acta_id')) {
            Schema::table('movimientos', function (Blueprint $table): void {
                $table->dropIndex('movimientos_acta_fecha_idx');
                $table->dropConstrainedForeignId('acta_id');
            });
        }
    }
};

