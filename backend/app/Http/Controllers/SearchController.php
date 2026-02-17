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
        $user = $request->user();

        $items = Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
            ->where('nombre', 'ilike', "%{$q}%")
            ->orderBy('nombre')
            ->limit(20)
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
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
        ]);

        $q = (string) $validated['q'];
        $institutionId = (int) $validated['institution_id'];
        $user = $request->user();

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && $institutionId !== (int) $user->institution_id) {
            return response()->json([]);
        }

        $items = Service::query()
            ->where('institution_id', $institutionId)
            ->where('nombre', 'ilike', "%{$q}%")
            ->orderBy('nombre')
            ->limit(20)
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $q = (string) $validated['q'];
        $serviceId = (int) $validated['service_id'];
        $user = $request->user();

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN)) {
            $isUserService = Service::query()
                ->where('id', $serviceId)
                ->where('institution_id', $user->institution_id)
                ->exists();

            if (! $isUserService) {
                return response()->json([]);
            }
        }

        $items = Office::query()
            ->where('service_id', $serviceId)
            ->where('nombre', 'ilike', "%{$q}%")
            ->orderBy('nombre')
            ->limit(20)
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
        $q = $this->validatedQuery($request);

        $nombreExpression = "trim(concat_ws(' ', tipo, marca, modelo, concat('(', numero_serie, ')')))";

        $items = Equipo::query()
            ->select('id')
            ->selectRaw("{$nombreExpression} as nombre")
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
}
