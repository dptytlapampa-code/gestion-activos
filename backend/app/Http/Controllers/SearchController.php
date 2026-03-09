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
            'q' => ['required', 'string', 'min:2'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
        ]);

        $q = (string) $validated['q'];
        $user = $request->user();
        $institutionId = $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN)
            ? (int) $user->institution_id
            : (($validated['institution_id'] ?? null) !== null ? (int) $validated['institution_id'] : null);

        $nombreExpression = "trim(concat_ws(' ', tipo, marca, modelo, concat('(', numero_serie, ')'), bien_patrimonial))";

        $items = Equipo::query()
            ->select('equipos.id')
            ->selectRaw("{$nombreExpression} as nombre")
            ->join('offices', 'offices.id', '=', 'equipos.oficina_id')
            ->join('services', 'services.id', '=', 'offices.service_id')
            ->when($institutionId !== null, fn ($query) => $query->where('services.institution_id', $institutionId))
            ->whereRaw("{$nombreExpression} ilike ?", ["%{$q}%"])
            ->orderByRaw("{$nombreExpression} asc")
            ->limit(20)
            ->get()
            ->map(fn (Equipo $equipo): array => [
                'id' => $equipo->id,
                'label' => (string) $equipo->nombre,
            ])
            ->values();

        return response()->json($items);
    }

    public function tiposEquipos(Request $request): JsonResponse
    {
        $q = $request->get('q');

        return response()->json(
            TipoEquipo::query()
                ->where('nombre', 'ILIKE', "%{$q}%")
                ->orderBy('nombre')
                ->limit(20)
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
