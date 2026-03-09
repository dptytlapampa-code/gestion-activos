<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('institutions', 'provincia')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('provincia', 150)->nullable();
            });
        }

        if (! Schema::hasColumn('institutions', 'localidad')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('localidad', 150)->nullable();
            });
        }

        if (! Schema::hasColumn('institutions', 'direccion')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('direccion', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('institutions', 'telefono')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('telefono', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('institutions', 'email')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('email', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('institutions', 'responsable')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('responsable', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('institutions', 'estado')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            });
        }

        if (Schema::hasColumn('institutions', 'tipo')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->string('tipo_tmp', 20)->nullable();
            });

            DB::table('institutions')->update([
                'tipo_tmp' => DB::raw("CASE 
                    WHEN tipo IN ('hospital', 'clinica', 'centro_salud', 'otro') THEN tipo
                    ELSE 'otro'
                END"),
            ]);

            Schema::table('institutions', function (Blueprint $table): void {
                $table->dropColumn('tipo');
            });
        }

        Schema::table('institutions', function (Blueprint $table): void {
            if (! Schema::hasColumn('institutions', 'tipo')) {
                $table->enum('tipo', ['hospital', 'clinica', 'centro_salud', 'otro'])->default('otro');
            }
        });

        if (Schema::hasColumn('institutions', 'tipo_tmp')) {
            DB::table('institutions')->whereNotNull('tipo_tmp')->update([
                'tipo' => DB::raw('tipo_tmp'),
            ]);

            Schema::table('institutions', function (Blueprint $table): void {
                $table->dropColumn('tipo_tmp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('institutions', 'tipo')) {
            Schema::table('institutions', function (Blueprint $table): void {
                $table->dropColumn('tipo');
            });
        }

        Schema::table('institutions', function (Blueprint $table): void {
            $table->enum('tipo', [
                'hospital',
                'centro_salud',
                'administrativo',
                'deposito',
                'otro',
            ])->default('otro');
        });
    }
};
