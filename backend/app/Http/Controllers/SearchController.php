<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchInstitutions(Request $request): JsonResponse
    {
        $q = $this->validatedQuery($request);
        $listAll = $this->isListAllQuery($q);
        $user = $request->user();

        $query = Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
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
        ]);

        $q = (string) $validated['q'];
        $listAll = $this->isListAllQuery($q);
        $institutionId = ($validated['institution_id'] ?? null) !== null
            ? (int) $validated['institution_id']
            : null;
        $user = $request->user();

        if ($institutionId === null) {
            return response()->json([]);
        }

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && $institutionId !== (int) $user->institution_id) {
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
        ]);

        $q = (string) $validated['q'];
        $listAll = $this->isListAllQuery($q);
        $serviceId = ($validated['service_id'] ?? null) !== null
            ? (int) $validated['service_id']
            : null;
        $institutionId = ($validated['institution_id'] ?? null) !== null
            ? (int) $validated['institution_id']
            : null;
        $user = $request->user();

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

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && $institutionId !== (int) $user->institution_id) {
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
        ]);

        $q = trim((string) $validated['q']);
        $listAll = $this->isListAllQuery($q);
        $user = $request->user();

        $institutionId = $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN)
            ? (int) $user->institution_id
            : (($validated['institution_id'] ?? null) !== null ? (int) $validated['institution_id'] : null);

        if ($institutionId === null) {
            return response()->json([]);
        }

        $query = Equipo::query()
            ->select([
                'equipos.id',
                'equipos.tipo',
                'equipos.marca',
                'equipos.modelo',
                'equipos.numero_serie',
                'equipos.bien_patrimonial',
                'equipos.mac_address',
                'equipos.codigo_interno',
                'equipos.estado',
                'offices.nombre as oficina_nombre',
                'services.nombre as servicio_nombre',
                'institutions.nombre as institucion_nombre',
                'institutions.id as institucion_id',
            ])
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->join('institutions', 'institutions.id', '=', 'services.institution_id')
            ->where('institutions.id', $institutionId)
            ->when(! $listAll, function ($query) use ($q): void {
                $like = "%{$q}%";

                $query->where(function ($inner) use ($like): void {
                    $inner
                        ->where('equipos.numero_serie', 'ilike', $like)
                        ->orWhere('equipos.bien_patrimonial', 'ilike', $like)
                        ->orWhere('equipos.modelo', 'ilike', $like)
                        ->orWhere('equipos.mac_address', 'ilike', $like)
                        ->orWhere('equipos.codigo_interno', 'ilike', $like)
                        ->orWhere('equipos.tipo', 'ilike', $like)
                        ->orWhere('equipos.marca', 'ilike', $like);
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
                'mac' => $equipo->mac_address,
                'codigo_interno' => $equipo->codigo_interno,
                'estado' => $equipo->estado,
                'institucion' => $equipo->institucion_nombre,
                'servicio' => $equipo->servicio_nombre,
                'oficina' => $equipo->oficina_nombre,
            ])
            ->values();

        return response()->json($items);
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
