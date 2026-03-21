<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mantenimientos', 'mantenimiento_externo_id')) {
            Schema::table('mantenimientos', function (Blueprint $table): void {
                $table->foreignId('mantenimiento_externo_id')
                    ->nullable()
                    ->after('dias_en_servicio')
                    ->constrained('mantenimientos')
                    ->nullOnDelete();
            });
        }

        $this->normalizarIngresosExternos();
        $this->repararCiclosExternos();
        $this->sincronizarEstadosDeEquipos();
        $this->crearIndices();
        $this->crearChecksPostgres();
    }

    public function down(): void
    {
        $this->eliminarChecksPostgres();

        DB::statement('DROP INDEX IF EXISTS mantenimientos_unico_externo_abierto_idx');
        DB::statement('DROP INDEX IF EXISTS mantenimientos_unico_cierre_externo_idx');

        if (Schema::hasColumn('mantenimientos', 'mantenimiento_externo_id')) {
            Schema::table('mantenimientos', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('mantenimiento_externo_id');
            });
        }
    }

    private function normalizarIngresosExternos(): void
    {
        DB::table('mantenimientos')
            ->where('tipo', 'externo')
            ->whereNull('fecha_ingreso_st')
            ->update(['fecha_ingreso_st' => DB::raw('fecha')]);
    }

    private function repararCiclosExternos(): void
    {
        /** @var Collection<int, Collection<int, object>> $registrosPorEquipo */
        $registrosPorEquipo = DB::table('mantenimientos')
            ->select([
                'id',
                'equipo_id',
                'tipo',
                'fecha',
                'fecha_ingreso_st',
                'fecha_egreso_st',
                'dias_en_servicio',
                'mantenimiento_externo_id',
            ])
            ->whereIn('tipo', ['externo', 'alta', 'baja'])
            ->orderBy('equipo_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->groupBy('equipo_id');

        foreach ($registrosPorEquipo as $registros) {
            $externos = $registros
                ->where('tipo', 'externo')
                ->values();

            $cierres = $registros
                ->filter(fn (object $registro): bool => in_array($registro->tipo, ['alta', 'baja'], true))
                ->values();

            $externosUsados = [];

            foreach ($cierres as $cierre) {
                $externo = $this->buscarExternoParaCierre($externos, $externosUsados, $cierre);

                if ($externo === null) {
                    continue;
                }

                $this->vincularCierreConExterno($cierre, $externo);
                $externosUsados[] = (int) $externo->id;
            }

            $externosAbiertosRestantes = $externos
                ->filter(fn (object $externo): bool => ! in_array((int) $externo->id, $externosUsados, true) && $externo->fecha_egreso_st === null)
                ->values();

            if ($externosAbiertosRestantes->count() <= 1) {
                continue;
            }

            for ($index = 0; $index < $externosAbiertosRestantes->count() - 1; $index++) {
                $externoActual = $externosAbiertosRestantes->get($index);
                $externoSiguiente = $externosAbiertosRestantes->get($index + 1);

                if ($externoActual === null || $externoSiguiente === null) {
                    continue;
                }

                $fechaIngreso = (string) ($externoActual->fecha_ingreso_st ?: $externoActual->fecha);
                $fechaCierre = (string) ($externoSiguiente->fecha_ingreso_st ?: $externoSiguiente->fecha ?: $fechaIngreso);

                if ($fechaCierre < $fechaIngreso) {
                    $fechaCierre = $fechaIngreso;
                }

                $dias = Carbon::parse($fechaIngreso)->diffInDays(Carbon::parse($fechaCierre));

                DB::table('mantenimientos')
                    ->where('id', $externoActual->id)
                    ->update([
                        'fecha_ingreso_st' => $fechaIngreso,
                        'fecha_egreso_st' => $fechaCierre,
                        'dias_en_servicio' => $dias,
                    ]);
            }
        }
    }

    private function sincronizarEstadosDeEquipos(): void
    {
        $statusIds = DB::table('equipo_statuses')
            ->whereIn('code', ['OPERATIVA', 'EN_SERVICIO_TECNICO', 'BAJA'])
            ->pluck('id', 'code');

        $equipos = DB::table('equipos')
            ->select(['id', 'estado', 'equipo_status_id'])
            ->get();

        foreach ($equipos as $equipo) {
            $tieneExternoAbierto = DB::table('mantenimientos')
                ->where('equipo_id', $equipo->id)
                ->where('tipo', 'externo')
                ->whereNull('fecha_egreso_st')
                ->exists();

            if ($tieneExternoAbierto) {
                DB::table('equipos')
                    ->where('id', $equipo->id)
                    ->update([
                        'estado' => 'mantenimiento',
                        'equipo_status_id' => $statusIds['EN_SERVICIO_TECNICO'] ?? $equipo->equipo_status_id,
                    ]);

                continue;
            }

            if ($equipo->estado !== 'mantenimiento') {
                continue;
            }

            $ultimoCierre = DB::table('mantenimientos')
                ->where('equipo_id', $equipo->id)
                ->whereIn('tipo', ['alta', 'baja'])
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->first();

            $nuevoEstado = $ultimoCierre?->tipo === 'baja' ? 'baja' : 'operativo';
            $nuevoStatusId = $nuevoEstado === 'baja'
                ? ($statusIds['BAJA'] ?? $equipo->equipo_status_id)
                : ($statusIds['OPERATIVA'] ?? $equipo->equipo_status_id);

            DB::table('equipos')
                ->where('id', $equipo->id)
                ->update([
                    'estado' => $nuevoEstado,
                    'equipo_status_id' => $nuevoStatusId,
                ]);
        }
    }

    private function crearIndices(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS mantenimientos_unico_externo_abierto_idx
            ON mantenimientos (equipo_id)
            WHERE tipo = 'externo' AND fecha_egreso_st IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS mantenimientos_unico_cierre_externo_idx
            ON mantenimientos (mantenimiento_externo_id)
            WHERE mantenimiento_externo_id IS NOT NULL
        ");
    }

    private function crearChecksPostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_externo_requiere_ingreso_chk');
        DB::statement("
            ALTER TABLE mantenimientos
            ADD CONSTRAINT mantenimientos_externo_requiere_ingreso_chk
            CHECK (tipo <> 'externo' OR fecha_ingreso_st IS NOT NULL)
        ");

        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_cierre_requiere_egreso_chk');
        DB::statement("
            ALTER TABLE mantenimientos
            ADD CONSTRAINT mantenimientos_cierre_requiere_egreso_chk
            CHECK (tipo NOT IN ('alta', 'baja') OR fecha_egreso_st IS NOT NULL)
        ");

        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_cierre_tipo_chk');
        DB::statement("
            ALTER TABLE mantenimientos
            ADD CONSTRAINT mantenimientos_cierre_tipo_chk
            CHECK (mantenimiento_externo_id IS NULL OR tipo IN ('alta', 'baja'))
        ");

        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_rango_fechas_chk');
        DB::statement("
            ALTER TABLE mantenimientos
            ADD CONSTRAINT mantenimientos_rango_fechas_chk
            CHECK (fecha_egreso_st IS NULL OR fecha_ingreso_st IS NULL OR fecha_egreso_st >= fecha_ingreso_st)
        ");
    }

    private function eliminarChecksPostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_externo_requiere_ingreso_chk');
        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_cierre_requiere_egreso_chk');
        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_cierre_tipo_chk');
        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS mantenimientos_rango_fechas_chk');
    }

    private function buscarExternoParaCierre(Collection $externos, array $externosUsados, object $cierre): ?object
    {
        $claveCierre = $this->claveOrden((string) $cierre->fecha, (int) $cierre->id);
        $fechaCierre = (string) ($cierre->fecha_egreso_st ?: $cierre->fecha);

        return $externos
            ->reverse()
            ->first(function (object $externo) use ($externosUsados, $claveCierre, $fechaCierre): bool {
                if (in_array((int) $externo->id, $externosUsados, true)) {
                    return false;
                }

                if ($this->claveOrden((string) $externo->fecha, (int) $externo->id) > $claveCierre) {
                    return false;
                }

                $fechaEgresoExterno = $externo->fecha_egreso_st;

                return $fechaEgresoExterno === null || (string) $fechaEgresoExterno <= $fechaCierre;
            });
    }

    private function vincularCierreConExterno(object $cierre, object $externo): void
    {
        $fechaIngreso = (string) ($externo->fecha_ingreso_st ?: $externo->fecha);
        $fechaEgreso = (string) ($cierre->fecha_egreso_st ?: $externo->fecha_egreso_st ?: $cierre->fecha);

        if ($fechaEgreso < $fechaIngreso) {
            $fechaEgreso = $fechaIngreso;
        }

        $dias = Carbon::parse($fechaIngreso)->diffInDays(Carbon::parse($fechaEgreso));

        DB::table('mantenimientos')
            ->where('id', $externo->id)
            ->update([
                'fecha_ingreso_st' => $fechaIngreso,
                'fecha_egreso_st' => $fechaEgreso,
                'dias_en_servicio' => $dias,
            ]);

        DB::table('mantenimientos')
            ->where('id', $cierre->id)
            ->update([
                'mantenimiento_externo_id' => $externo->id,
                'fecha_ingreso_st' => $cierre->fecha_ingreso_st ?: $fechaIngreso,
                'fecha_egreso_st' => $cierre->fecha_egreso_st ?: $fechaEgreso,
                'dias_en_servicio' => $dias,
            ]);
    }

    private function claveOrden(string $fecha, int $id): string
    {
        return sprintf('%s#%010d', $fecha, $id);
    }
};
