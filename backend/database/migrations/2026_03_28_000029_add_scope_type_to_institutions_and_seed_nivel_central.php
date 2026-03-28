<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SCOPE_INSTITUTIONAL = 'institutional';

    private const SCOPE_GLOBAL = 'global';

    private const CENTRAL_CODE = 'NIVEL-CENTRAL';

    private const CENTRAL_NAME = 'Nivel Central';

    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->string('scope_type', 30)
                ->default(self::SCOPE_INSTITUTIONAL)
                ->after('estado');
        });

        DB::table('institutions')
            ->whereNull('scope_type')
            ->update(['scope_type' => self::SCOPE_INSTITUTIONAL]);

        $timestamp = Carbon::now()->toDateTimeString();

        $centralInstitutionId = DB::table('institutions')
            ->where('scope_type', self::SCOPE_GLOBAL)
            ->value('id');

        if ($centralInstitutionId === null) {
            $candidate = DB::table('institutions')
                ->where(function ($query): void {
                    $query
                        ->where('codigo', self::CENTRAL_CODE)
                        ->orWhereRaw('lower(nombre) = ?', [
                            function_exists('mb_strtolower')
                                ? mb_strtolower(self::CENTRAL_NAME)
                                : strtolower(self::CENTRAL_NAME),
                        ]);
                })
                ->orderBy('id')
                ->first();

            if ($candidate !== null) {
                $centralInstitutionId = (int) $candidate->id;

                DB::table('institutions')
                    ->where('id', $centralInstitutionId)
                    ->update([
                        'codigo' => self::CENTRAL_CODE,
                        'nombre' => self::CENTRAL_NAME,
                        'descripcion' => 'Institucion madre para la administracion global del sistema.',
                        'tipo' => 'otro',
                        'estado' => 'activo',
                        'scope_type' => self::SCOPE_GLOBAL,
                        'updated_at' => $timestamp,
                    ]);
            } else {
                $centralInstitutionId = DB::table('institutions')->insertGetId([
                    'codigo' => self::CENTRAL_CODE,
                    'nombre' => self::CENTRAL_NAME,
                    'descripcion' => 'Institucion madre para la administracion global del sistema.',
                    'tipo' => 'otro',
                    'estado' => 'activo',
                    'scope_type' => self::SCOPE_GLOBAL,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        DB::table('institutions')
            ->where('id', '!=', $centralInstitutionId)
            ->where('scope_type', self::SCOPE_GLOBAL)
            ->update([
                'scope_type' => self::SCOPE_INSTITUTIONAL,
                'updated_at' => $timestamp,
            ]);

        DB::statement("create unique index institutions_scope_type_global_unique on institutions (scope_type) where scope_type = '".self::SCOPE_GLOBAL."'");

        DB::table('users')
            ->where('role', 'superadmin')
            ->update(['institution_id' => $centralInstitutionId]);
    }

    public function down(): void
    {
        DB::statement('drop index if exists institutions_scope_type_global_unique');

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn('scope_type');
        });
    }
};
