<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchActaEquiposRequest;
use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\ActaEquipoSearchService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SearchController extends Controller
{
    public function searchInstitutions(Request $request): JsonResponse
    {
        $q = $this->validatedQuery($request);
        $listAll = $this->isListAllQuery($q);
        $user = $request->user();
        $activeInstitutionId = $this->activeInstitutionId($user);

        $query = Institution::query()
            ->where('id', $activeInstitutionId ?? 0)
            ->when(! $listAll, fn ($query) => $query->where('nombre', 'ilike', "%{$q}%"))
            ->orderBy('nombre');

        if (! $listAll) {
            $query->limit(20);
        }

        $items = $query
            ->get(['id', 'nombre'])
            ->map(fn (Institution $institution): array => [
                'id' => $institution->id,
                'label' => $institution->nombre,
            ])
            ->values();

        return response()->json($items);
    }

    public function searchServices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'acta_context' => ['nullable', 'boolean'],
        ]);

        $q = (string) $validated['q'];
        $listAll = $this->isListAllQuery($q);
        $institutionId = ($validated['institution_id'] ?? null) !== null
            ? (int) $validated['institution_id']
            : null;
        $actaContext = (bool) ($validated['acta_context'] ?? false);
        $user = $request->user();
        $activeInstitutionId = $this->activeInstitutionId($user);

        if ($institutionId === null) {
            return response()->json([]);
        }

        if (! $actaContext && $institutionId !== $activeInstitutionId) {
            return response()->json([]);
        }

        if ($user !== null && $actaContext && (! $user->can('create', Acta::class) || ! $user->canAccessInstitution($institutionId))) {
            return response()->json([]);
        }

        $query = Service::query()
            ->where('institution_id', $institutionId)
            ->when(! $listAll, fn ($query) => $query->where('nombre', 'ilike', "%{$q}%"))
            ->orderBy('nombre');

        if (! $listAll) {
            $query->limit(20);
        }

        $items = $query
            ->get(['id', 'nombre'])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'label' => $service->nombre,
            ])
            ->values();

        return response()->json($items);
    }

    public function searchOffices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'acta_context' => ['nullable', 'boolean'],
        ]);

        $q = (string) $validated['q'];
        $listAll = $this->isListAllQuery($q);
        $serviceId = ($validated['service_id'] ?? null) !== null
            ? (int) $validated['service_id']
            : null;
        $institutionId = ($validated['institution_id'] ?? null) !== null
            ? (int) $validated['institution_id']
            : null;
        $actaContext = (bool) ($validated['acta_context'] ?? false);
        $user = $request->user();
        $activeInstitutionId = $this->activeInstitutionId($user);

        if ($serviceId === null || $institutionId === null) {
            return response()->json([]);
        }

        $serviceBelongsToInstitution = Service::query()
            ->where('id', $serviceId)
            ->where('institution_id', $institutionId)
            ->exists();

        if (! $serviceBelongsToInstitution) {
            return response()->json([]);
        }

        if (! $actaContext && $institutionId !== $activeInstitutionId) {
            return response()->json([]);
        }

        if ($user !== null && $actaContext && (! $user->can('create', Acta::class) || ! $user->canAccessInstitution($institutionId))) {
            return response()->json([]);
        }

        $query = Office::query()
            ->where('service_id', $serviceId)
            ->whereHas(
                'service',
                fn ($query) => $query->where('institution_id', $institutionId)
            )
            ->when(! $listAll, fn ($query) => $query->where('nombre', 'ilike', "%{$q}%"))
            ->orderBy('nombre');

        if (! $listAll) {
            $query->limit(20);
        }

        $items = $query
            ->get(['id', 'nombre'])
            ->map(fn (Office $office): array => [
                'id' => $office->id,
                'label' => $office->nombre,
            ])
            ->values();

        return response()->json($items);
    }

    public function searchEquipos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'include_baja' => ['nullable', 'boolean'],
            'acta_context' => ['nullable', 'boolean'],
        ]);

        $q = trim((string) $validated['q']);
        $listAll = $this->isListAllQuery($q);
        $includeBaja = (bool) ($validated['include_baja'] ?? false);
        $actaContext = (bool) ($validated['acta_context'] ?? false);
        $user = $request->user();
        $activeInstitutionId = $this->activeInstitutionId($user);

        $requestedInstitutionId = ($validated['institution_id'] ?? null) !== null
            ? (int) $validated['institution_id']
            : null;

        if ($user !== null && $actaContext && ! $user->can('create', Acta::class)) {
            return response()->json([]);
        }

        if ($user !== null && $actaContext) {
            $allowedInstitutionIds = $user->hasRole(User::ROLE_SUPERADMIN)
                ? null
                : $user->accessibleInstitutionIds();

            if ($allowedInstitutionIds !== null && $allowedInstitutionIds->isEmpty()) {
                return response()->json([]);
            }

            if ($requestedInstitutionId !== null) {
                if ($allowedInstitutionIds !== null && ! $allowedInstitutionIds->contains($requestedInstitutionId)) {
                    return response()->json([]);
                }

                $institutionIds = collect([$requestedInstitutionId]);
            } elseif ($activeInstitutionId !== null) {
                $institutionIds = collect([$activeInstitutionId]);
            } else {
                $institutionIds = collect();
            }
        } else {
            $institutionIds = $activeInstitutionId !== null
                ? collect([$activeInstitutionId])
                : collect();
        }

        if ($institutionIds->isEmpty()) {
            return response()->json([]);
        }

        $hasMacAddress = Schema::hasColumn('equipos', 'mac_address');
        $hasCodigoInterno = Schema::hasColumn('equipos', 'codigo_interno');

        $select = [
            'equipos.id',
            'equipos.tipo',
            'equipos.marca',
            'equipos.modelo',
            'equipos.numero_serie',
            'equipos.bien_patrimonial',
            'equipos.estado',
            'offices.id as oficina_id',
            'offices.nombre as oficina_nombre',
            'services.id as servicio_id',
            'services.nombre as servicio_nombre',
            'institutions.id as institucion_id',
            'institutions.nombre as institucion_nombre',
        ];

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
            ->whereIn('institutions.id', $institutionIds->all())
            ->when(! $includeBaja, fn ($query) => $query->where('equipos.estado', '!=', Equipo::ESTADO_BAJA))
            ->when(! $listAll, function ($query) use ($q, $hasMacAddress, $hasCodigoInterno): void {
                $like = "%{$q}%";

                $query->where(function ($inner) use ($like, $hasMacAddress, $hasCodigoInterno): void {
                    $inner
                        ->where('equipos.numero_serie', 'ilike', $like)
                        ->orWhere('equipos.bien_patrimonial', 'ilike', $like)
                        ->orWhere('equipos.modelo', 'ilike', $like)
                        ->orWhere('equipos.tipo', 'ilike', $like)
                        ->orWhere('equipos.marca', 'ilike', $like);

                    if ($hasMacAddress) {
                        $inner->orWhere('equipos.mac_address', 'ilike', $like);
                    }

                    if ($hasCodigoInterno) {
                        $inner->orWhere('equipos.codigo_interno', 'ilike', $like);
                    }
                });
            })
            ->orderBy('equipos.tipo')
            ->orderBy('equipos.marca')
            ->orderBy('equipos.modelo')
            ->orderBy('equipos.numero_serie')
            ->limit($listAll ? 80 : 25);

        $items = $query
            ->get()
            ->map(fn (Equipo $equipo): array => [
                'id' => $equipo->id,
                'label' => trim(sprintf('%s %s %s', $equipo->tipo, $equipo->marca, $equipo->modelo)),
                'tipo' => $equipo->tipo,
                'marca' => $equipo->marca,
                'modelo' => $equipo->modelo,
                'numero_serie' => $equipo->numero_serie,
                'bien_patrimonial' => $equipo->bien_patrimonial,
                'mac' => $equipo->getAttribute('mac_address'),
                'codigo_interno' => $equipo->getAttribute('codigo_interno'),
                'estado' => $equipo->estado,
                'institucion' => $equipo->institucion_nombre,
                'institucion_id' => (int) $equipo->getAttribute('institucion_id'),
                'servicio' => $equipo->servicio_nombre,
                'servicio_id' => (int) $equipo->getAttribute('servicio_id'),
                'oficina' => $equipo->oficina_nombre,
                'oficina_id' => (int) $equipo->getAttribute('oficina_id'),
            ])
            ->values();

        if ($actaContext) {
            return response()->json([
                'items' => $items,
                'meta' => [
                    'searched' => true,
                    'message' => $items->isEmpty() ? 'No encontramos equipos con los criterios indicados.' : null,
                    'page' => 1,
                    'per_page' => $items->count(),
                    'has_more' => false,
                    'next_page' => null,
                ],
            ]);
        }

        return response()->json($items);
    }

    public function searchActaEquipos(SearchActaEquiposRequest $request, ActaEquipoSearchService $service): JsonResponse
    {
        try {
            return response()->json(
                $service->search($request->user(), $request->validated())
            );
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('acta equipos search failed', [
                'user_id' => $request->user()?->id,
                'filters' => $request->except(['_token']),
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Ocurrio un error al buscar equipos. Intente nuevamente en unos segundos.',
            ], 500);
        }
    }

    public function tiposEquipos(Request $request): JsonResponse
    {
        $q = (string) $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ])['q'];
        $listAll = $this->isListAllQuery($q);

        return response()->json(
            TipoEquipo::query()
                ->when(! $listAll, fn ($query) => $query->where('nombre', 'ilike', "%{$q}%"))
                ->orderBy('nombre')
                ->limit(50)
                ->get(['id', 'nombre as label'])
        );
    }

    private function validatedQuery(Request $request): string
    {
        return (string) $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ])['q'];
    }

    private function isListAllQuery(string $query): bool
    {
        return trim($query) === '...';
    }
}
