<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $needsLogoInstitucional = ! Schema::hasColumn('system_settings', 'logo_institucional');
        $needsLogoPdf = ! Schema::hasColumn('system_settings', 'logo_pdf');

        if ($needsLogoInstitucional || $needsLogoPdf) {
            Schema::table('system_settings', function (Blueprint $table) use ($needsLogoInstitucional, $needsLogoPdf): void {
                if ($needsLogoInstitucional) {
                    $table->string('logo_institucional')->nullable()->after('logo_path');
                }

                if ($needsLogoPdf) {
                    $table->string('logo_pdf')->nullable()->after($needsLogoInstitucional ? 'logo_institucional' : 'logo_path');
                }
            });
        }

        DB::table('system_settings')
            ->whereNull('logo_institucional')
            ->whereNotNull('logo_path')
            ->update(['logo_institucional' => DB::raw('logo_path')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $hasLogoInstitucional = Schema::hasColumn('system_settings', 'logo_institucional');
        $hasLogoPdf = Schema::hasColumn('system_settings', 'logo_pdf');

        if (! $hasLogoInstitucional && ! $hasLogoPdf) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) use ($hasLogoInstitucional, $hasLogoPdf): void {
            if ($hasLogoPdf) {
                $table->dropColumn('logo_pdf');
            }

            if ($hasLogoInstitucional) {
                $table->dropColumn('logo_institucional');
            }
        });
    }
};
