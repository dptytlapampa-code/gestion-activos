<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\User;
use App\Support\Listings\ListingState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EquipoListingService
{
    public const FILTER_KEYS = [
        'tipo',
        'marca',
        'modelo',
        'estado',
    ];

    public function listingState(Request $request): ListingState
    {
        return ListingState::fromRequest($request);
    }

    /**
     * @return array{tipo:string,marca:string,modelo:string,estado:string}
     */
    public function filtersFromRequest(Request $request): array
    {
        $normalizeQueryValue = static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '';

        return [
            'tipo' => $normalizeQueryValue($request->query('tipo')),
            'marca' => $normalizeQueryValue($request->query('marca')),
            'modelo' => $normalizeQueryValue($request->query('modelo')),
            'estado' => $normalizeQueryValue($request->query('estado')),
        ];
    }

    /**
     * @return array{tipo:string,marca:string,modelo:string,estado:string}
     */
    public function emptyFilters(): array
    {
        return array_fill_keys(self::FILTER_KEYS, '');
    }

    /**
     * @param array{tipo:string,marca:string,modelo:string,estado:string} $filters
     */
    public function buildIndexQuery(?User $user, string $search, array $filters): Builder
    {
        return Equipo::query()
            ->with([
                'oficina.service.institution',
                'tipoEquipo',
                'equipoStatus',
                'recepcionTecnicaAbierta' => function ($query): void {
                    $query->select([
                        'recepciones_tecnicas.id',
                        'recepciones_tecnicas.equipo_id',
                        'recepciones_tecnicas.codigo',
                        'recepciones_tecnicas.estado',
                        'recepciones_tecnicas.ingresado_at',
                    ]);
                },
            ])
            ->visibleToUser($user)
            ->searchIndex($search)
            ->applyIndexFilters($filters)
            ->orderBy('tipo')
            ->orderBy('marca')
            ->orderBy('modelo')
            ->orderBy('id');
    }

    /**
     * @param array{tipo:string,marca:string,modelo:string,estado:string} $filters
     */
    public function hasActiveFilters(string $search, array $filters): bool
    {
        if ($search !== '') {
            return true;
        }

        foreach ($filters as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }
}
