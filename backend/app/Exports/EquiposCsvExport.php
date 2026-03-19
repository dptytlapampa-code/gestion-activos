<?php

namespace App\Exports;

use App\Contracts\Exports\CsvExport;
use App\Enums\ExportScope;
use App\Models\Equipo;
use App\Services\Exports\ExportFileNameService;
use App\Support\Exports\CsvColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EquiposCsvExport implements CsvExport
{
    public function __construct(
        private readonly Builder $query,
        private readonly ExportScope $scope,
        private readonly bool $hasActiveFilters,
        private readonly ExportFileNameService $fileNameService,
    ) {}

    public function fileName(): string
    {
        $prefix = match (true) {
            $this->scope->isAll() => 'equipos_completos',
            $this->hasActiveFilters => 'equipos_filtrados',
            default => 'equipos',
        };

        return $this->fileNameService->csv($prefix);
    }

    public function query(): Builder
    {
        return clone $this->query;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    /**
     * @return array<int, CsvColumn>
     */
    public function columns(): array
    {
        return [
            new CsvColumn('ID', static fn (Equipo $equipo): int => $equipo->id),
            new CsvColumn('UUID', static fn (Equipo $equipo): string => (string) $equipo->uuid),
            new CsvColumn('Tipo de equipo', static fn (Equipo $equipo): string => (string) $equipo->tipo),
            new CsvColumn('Marca', static fn (Equipo $equipo): string => (string) $equipo->marca),
            new CsvColumn('Modelo', static fn (Equipo $equipo): string => (string) $equipo->modelo),
            new CsvColumn('Numero de serie', static fn (Equipo $equipo): string => (string) $equipo->numero_serie),
            new CsvColumn('Bien patrimonial', static fn (Equipo $equipo): string => (string) $equipo->bien_patrimonial),
            new CsvColumn('Codigo interno', static fn (Equipo $equipo): ?string => $equipo->codigo_interno),
            new CsvColumn('Direccion MAC', static fn (Equipo $equipo): ?string => $equipo->mac_address),
            new CsvColumn('Estado', fn (Equipo $equipo): string => $this->estadoLegible($equipo)),
            new CsvColumn('Estado tecnico', static fn (Equipo $equipo): ?string => $equipo->equipoStatus?->name),
            new CsvColumn('Institucion', static fn (Equipo $equipo): ?string => $equipo->oficina?->service?->institution?->nombre),
            new CsvColumn('Servicio', static fn (Equipo $equipo): ?string => $equipo->oficina?->service?->nombre),
            new CsvColumn('Oficina', static fn (Equipo $equipo): ?string => $equipo->oficina?->nombre),
            new CsvColumn('Ubicacion completa', fn (Equipo $equipo): string => $this->ubicacionCompleta($equipo)),
            new CsvColumn('Fecha de ingreso', static fn (Equipo $equipo): ?string => $equipo->fecha_ingreso?->format('d/m/Y')),
            new CsvColumn('Fecha de creacion', static fn (Equipo $equipo): ?string => $equipo->created_at?->format('d/m/Y H:i')),
            new CsvColumn('Fecha de ultima actualizacion', static fn (Equipo $equipo): ?string => $equipo->updated_at?->format('d/m/Y H:i')),
        ];
    }

    private function estadoLegible(Equipo $equipo): string
    {
        if ($equipo->equipoStatus?->name !== null && $equipo->equipoStatus->name !== '') {
            return $equipo->equipoStatus->name;
        }

        return Str::of((string) $equipo->estado)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function ubicacionCompleta(Equipo $equipo): string
    {
        $parts = array_filter([
            $equipo->oficina?->service?->institution?->nombre,
            $equipo->oficina?->service?->nombre,
            $equipo->oficina?->nombre,
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return implode(' / ', $parts);
    }
}
