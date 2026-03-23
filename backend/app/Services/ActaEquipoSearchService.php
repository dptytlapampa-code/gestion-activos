<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ActaEquipoSearchService
{
    private const PER_PAGE = 25;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     meta: array{
     *         searched: bool,
     *         message: string|null,
     *         page: int,
     *         per_page: int,
     *         has_more: bool,
     *         next_page: int|null
     *     }
     * }
     */
    public function search(User $user, array $filters): array
    {
        $queryText = trim((string) ($filters['q'] ?? ''));
        $institutionId = $this->nullableInt($filters['institution_id'] ?? null);
        $serviceId = $this->nullableInt($filters['service_id'] ?? null);
        $officeId = $this->nullableInt($filters['office_id'] ?? null);
        $tipoEquipoId = $this->nullableInt($filters['tipo_equipo_id'] ?? null);
        $estado = $this->nullableString($filters['estado'] ?? null);
        $page = max(1, (int) ($filters['page'] ?? 1));

        if (! $this->hasSearchCriteria($queryText, $institutionId, $serviceId, $officeId, $tipoEquipoId, $estado)) {
            return [
                'items' => [],
                'meta' => [
                    'searched' => false,
                    'message' => 'Escriba al menos 3 caracteres o use filtros avanzados para comenzar la busqueda.',
                    'page' => 1,
                    'per_page' => self::PER_PAGE,
                    'has_more' => false,
                    'next_page' => null,
                ],
            ];
        }

        $allowedInstitutionIds = $user->hasRole(User::ROLE_SUPERADMIN)
            ? null
            : $user->accessibleInstitutionIds();
        $activeInstitutionId = app(ActiveInstitutionContext::class)->currentId($user);

        if ($allowedInstitutionIds !== null && $allowedInstitutionIds->isEmpty()) {
            return $this->emptyResult($page, 'No tiene instituciones habilitadas para generar actas.');
        }

        if ($allowedInstitutionIds !== null && $institutionId !== null && ! $allowedInstitutionIds->contains($institutionId)) {
            return $this->emptyResult($page, 'No puede consultar equipos de la institucion seleccionada.');
        }

        if ($institutionId === null && $activeInstitutionId === null) {
            return $this->emptyResult($page, 'Seleccione una institucion activa para comenzar la busqueda.');
        }

        $hasUuid = Schema::hasColumn('equipos', 'uuid');
        $hasMacAddress = Schema::hasColumn('equipos', 'mac_address');
        $hasCodigoInterno = Schema::hasColumn('equipos', 'codigo_interno');

        $offset = ($page - 1) * self::PER_PAGE;

        $select = [
            'equipos.id',
            'equipos.tipo',
            'equipos.tipo_equipo_id',
            'equipos.marca',
            'equipos.modelo',
            'equipos.numero_serie',
            'equipos.bien_patrimonial',
            'equipos.estado',
            'tipos_equipos.nombre as tipo_equipo_nombre',
            'offices.id as oficina_id',
            'offices.nombre as oficina_nombre',
            'services.id as servicio_id',
            'services.nombre as servicio_nombre',
            'institutions.id as institucion_id',
            'institutions.nombre as institucion_nombre',
        ];

        if ($hasUuid) {
            $select[] = 'equipos.uuid';
        }

        if ($hasMacAddress) {
            $select[] = 'equipos.mac_address';
        }

        if ($hasCodigoInterno) {
            $select[] = 'equipos.codigo_interno';
        }

        $query = Equipo::query()
            ->select($select)
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->join('institutions', 'institutions.id', '=', 'services.institution_id')
            ->leftJoin('tipos_equipos', 'tipos_equipos.id', '=', 'equipos.tipo_equipo_id')
            ->when($allowedInstitutionIds !== null, fn (Builder $builder) => $builder->whereIn('institutions.id', $allowedInstitutionIds->all()))
            ->where('institutions.id', $institutionId ?? $activeInstitutionId)
            ->when($serviceId !== null, fn (Builder $builder) => $builder->where('services.id', $serviceId))
            ->when($officeId !== null, fn (Builder $builder) => $builder->where('offices.id', $officeId))
            ->when($tipoEquipoId !== null, fn (Builder $builder) => $builder->where('equipos.tipo_equipo_id', $tipoEquipoId))
            ->when(
                $estado !== null,
                fn (Builder $builder) => $builder->where('equipos.estado', $estado),
                fn (Builder $builder) => $builder->where('equipos.estado', '!=', Equipo::ESTADO_BAJA)
            );

        if ($queryText !== '') {
            $this->applySearchConditions($query, $queryText, $hasUuid, $hasMacAddress, $hasCodigoInterno);
            $this->applyRelevanceSorting($query, $queryText, $hasUuid, $hasMacAddress, $hasCodigoInterno);
        }

        $rows = $query
            ->orderBy('institutions.nombre')
            ->orderBy('services.nombre')
            ->orderBy('offices.nombre')
            ->orderBy('equipos.tipo')
            ->orderBy('equipos.marca')
            ->orderBy('equipos.modelo')
            ->orderBy('equipos.numero_serie')
            ->offset($offset)
            ->limit(self::PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::PER_PAGE;
        $visibleRows = $hasMore ? $rows->take(self::PER_PAGE) : $rows;

        $items = $visibleRows
            ->map(fn (Equipo $equipo): array => $this->transformEquipo($equipo))
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => [
                'searched' => true,
                'message' => empty($items) ? 'No encontramos equipos con los criterios indicados.' : null,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'has_more' => $hasMore,
                'next_page' => $hasMore ? $page + 1 : null,
            ],
        ];
    }

    private function applySearchConditions(Builder $query, string $queryText, bool $hasUuid, bool $hasMacAddress, bool $hasCodigoInterno): void
    {
        $like = '%'.$queryText.'%';

        $query->where(function (Builder $builder) use ($like, $hasUuid, $hasMacAddress, $hasCodigoInterno): void {
            $builder
                ->where('equipos.numero_serie', 'ilike', $like)
                ->orWhere('equipos.bien_patrimonial', 'ilike', $like)
                ->orWhere('equipos.marca', 'ilike', $like)
                ->orWhere('equipos.modelo', 'ilike', $like)
                ->orWhere('equipos.tipo', 'ilike', $like)
                ->orWhere('tipos_equipos.nombre', 'ilike', $like)
                ->orWhere('institutions.nombre', 'ilike', $like)
                ->orWhere('services.nombre', 'ilike', $like)
                ->orWhere('offices.nombre', 'ilike', $like);

            if ($hasUuid) {
                $builder->orWhereRaw('(equipos.uuid)::text ilike ?', [$like]);
            }

            if ($hasMacAddress) {
                $builder->orWhere('equipos.mac_address', 'ilike', $like);
            }

            if ($hasCodigoInterno) {
                $builder->orWhere('equipos.codigo_interno', 'ilike', $like);
            }
        });
    }

    private function applyRelevanceSorting(Builder $query, string $queryText, bool $hasUuid, bool $hasMacAddress, bool $hasCodigoInterno): void
    {
        $needle = $this->normalizeSearchText($queryText);
        $startsWith = $needle.'%';

        $cases = [
            [$this->lowerTextExpression('equipos.numero_serie').' = ?', $needle],
            [$this->lowerTextExpression('equipos.bien_patrimonial').' = ?', $needle],
            [$this->lowerTextExpression('equipos.marca').' = ?', $needle],
            [$this->lowerTextExpression('equipos.modelo').' = ?', $needle],
            [$this->lowerTextExpression('equipos.tipo').' = ?', $needle],
        ];

        if ($hasUuid) {
            $cases[] = [$this->lowerTextExpression('equipos.uuid').' = ?', $needle];
        }

        if ($hasMacAddress) {
            $cases[] = [$this->lowerTextExpression('equipos.mac_address').' = ?', $needle];
        }

        if ($hasCodigoInterno) {
            $cases[] = [$this->lowerTextExpression('equipos.codigo_interno').' = ?', $needle];
        }

        $cases[] = [$this->lowerTextExpression('equipos.numero_serie').' like ?', $startsWith];
        $cases[] = [$this->lowerTextExpression('equipos.bien_patrimonial').' like ?', $startsWith];
        $cases[] = [$this->lowerTextExpression('equipos.marca').' like ?', $startsWith];
        $cases[] = [$this->lowerTextExpression('equipos.modelo').' like ?', $startsWith];

        $sql = 'CASE ';
        $bindings = [];

        foreach ($cases as $index => [$expression, $binding]) {
            $sql .= 'WHEN '.$expression.' THEN '.$index.' ';
            $bindings[] = $binding;
        }

        $sql .= 'ELSE 999 END';

        $query->orderByRaw($sql, $bindings);
    }

    private function lowerTextExpression(string $column): string
    {
        return sprintf('lower((%s)::text)', $column);
    }


    /**
     * @return array<string, mixed>
     */
    private function transformEquipo(Equipo $equipo): array
    {
        $tipo = $this->nullableString($equipo->tipo) ?? $this->nullableString($equipo->getAttribute('tipo_equipo_nombre')) ?? 'Equipo';
        $marca = $this->nullableString($equipo->marca);
        $modelo = $this->nullableString($equipo->modelo);

        return [
            'id' => $equipo->id,
            'uuid' => $equipo->getAttribute('uuid'),
            'label' => trim(collect([$tipo, $marca, $modelo])->filter()->implode(' ')),
            'tipo' => $tipo,
            'tipo_equipo_id' => $equipo->tipo_equipo_id ? (int) $equipo->tipo_equipo_id : null,
            'marca' => $equipo->marca,
            'modelo' => $equipo->modelo,
            'numero_serie' => $equipo->numero_serie,
            'bien_patrimonial' => $equipo->bien_patrimonial,
            'mac' => $equipo->getAttribute('mac_address'),
            'codigo_interno' => $equipo->getAttribute('codigo_interno'),
            'estado' => $equipo->estado,
            'estado_label' => $this->estadoLabel((string) $equipo->estado),
            'institucion' => $equipo->getAttribute('institucion_nombre'),
            'institucion_id' => (int) $equipo->getAttribute('institucion_id'),
            'servicio' => $equipo->getAttribute('servicio_nombre'),
            'servicio_id' => (int) $equipo->getAttribute('servicio_id'),
            'oficina' => $equipo->getAttribute('oficina_nombre'),
            'oficina_id' => (int) $equipo->getAttribute('oficina_id'),
            'ubicacion_resumida' => collect([
                $equipo->getAttribute('institucion_nombre'),
                $equipo->getAttribute('servicio_nombre'),
                $equipo->getAttribute('oficina_nombre'),
            ])->filter()->implode(' / '),
        ];
    }

    private function hasSearchCriteria(string $queryText, ?int $institutionId, ?int $serviceId, ?int $officeId, ?int $tipoEquipoId, ?string $estado): bool
    {
        return $this->searchTextLength($queryText) >= 3
            || $institutionId !== null
            || $serviceId !== null
            || $officeId !== null
            || $tipoEquipoId !== null
            || $estado !== null;
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     meta: array{
     *         searched: bool,
     *         message: string,
     *         page: int,
     *         per_page: int,
     *         has_more: bool,
     *         next_page: int|null
     *     }
     * }
     */
    private function emptyResult(int $page, string $message): array
    {
        return [
            'items' => [],
            'meta' => [
                'searched' => true,
                'message' => $message,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'has_more' => false,
                'next_page' => null,
            ],
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cast = (int) $value;

        return $cast > 0 ? $cast : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function estadoLabel(string $estado): string
    {
        return match ($estado) {
            Equipo::ESTADO_OPERATIVO => 'Operativo',
            Equipo::ESTADO_PRESTADO => 'Prestado',
            Equipo::ESTADO_EN_MANTENIMIENTO => 'Mantenimiento',
            Equipo::ESTADO_FUERA_DE_SERVICIO => 'Fuera de servicio',
            Equipo::ESTADO_BAJA => 'Baja',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
    }

    private function normalizeSearchText(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value)
            : Str::lower($value);
    }

    private function searchTextLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value)
            : strlen($value);
    }
}
