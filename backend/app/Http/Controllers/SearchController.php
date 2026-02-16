<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchInstitutions(Request $request): JsonResponse
    {
        $q = $this->validatedQuery($request);

        $items = Institution::query()
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
        $q = $this->validatedQuery($request);

        $items = Service::query()
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
        $q = $this->validatedQuery($request);

        $items = Office::query()
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

    private function validatedQuery(Request $request): string
    {
        return (string) $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ])['q'];
    }
}
