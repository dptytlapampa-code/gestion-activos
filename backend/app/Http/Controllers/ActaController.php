<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActaRequest;
use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\User;
use App\Services\ActaTraceabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ActaController extends Controller
{
    public function __construct(private readonly ActaTraceabilityService $traceabilityService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Acta::class);

        $user = $request->user();
        $validated = $request->validate([
            'tipo' => ['nullable', Rule::in(Acta::TIPOS)],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $actas = Acta::query()
            ->withCount('equipos')
            ->with(['creator:id,name'])
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->where('institution_id', $user->institution_id)
            )
            ->when($validated['tipo'] ?? null, fn (Builder $query, string $tipo) => $query->where('tipo', $tipo))
            ->when($validated['fecha_desde'] ?? null, fn (Builder $query, string $fechaDesde) => $query->whereDate('fecha', '>=', $fechaDesde))
            ->when($validated['fecha_hasta'] ?? null, fn (Builder $query, string $fechaHasta) => $query->whereDate('fecha', '<=', $fechaHasta))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('actas.index', [
            'actas' => $actas,
            'tipos' => Acta::TIPOS,
            'tipoLabels' => Acta::LABELS,
            'filters' => $validated,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Acta::class);

        $user = $request->user();
        $institutions = Institution::query()->orderBy('nombre')->get(['id', 'nombre']);

        $oldEquipoIds = collect(old('equipos', []))
            ->pluck('equipo_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        $oldEquipoMeta = collect(old('equipos', []))->keyBy(fn (array $item): int => (int) ($item['equipo_id'] ?? 0));

        $oldSelectedEquipos = $oldEquipoIds->isEmpty()
            ? collect()
            : Equipo::query()
                ->with('oficina.service.institution')
                ->whereIn('id', $oldEquipoIds)
                ->get()
                ->map(function (Equipo $equipo) use ($oldEquipoMeta): array {
                    $meta = $oldEquipoMeta->get($equipo->id, []);

                    return [
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
                        'institucion' => $equipo->oficina?->service?->institution?->nombre,
                        'servicio' => $equipo->oficina?->service?->nombre,
                        'oficina' => $equipo->oficina?->nombre,
                        'institucion_id' => $equipo->oficina?->service?->institution?->id,
                        'servicio_id' => $equipo->oficina?->service?->id,
                        'oficina_id' => $equipo->oficina?->id,
                        'cantidad' => (int) ($meta['cantidad'] ?? 1),
                        'accesorios' => $meta['accesorios'] ?? '',
                    ];
                });

        return view('actas.create', [
            'tipos' => Acta::TIPOS,
            'tipoLabels' => Acta::LABELS,
            'institutions' => $institutions,
            'userInstitutionId' => $user->institution_id,
            'isSuperadmin' => $user->hasRole(User::ROLE_SUPERADMIN),
            'oldSelectedEquipos' => $oldSelectedEquipos,
        ]);
    }

    public function store(StoreActaRequest $request): RedirectResponse
    {
        $this->authorize('create', Acta::class);

        $acta = $this->traceabilityService->crear($request->user(), $request->validated());

        return redirect()->route('actas.show', $acta)->with('status', 'Acta de trazabilidad generada correctamente.');
    }

    public function show(Acta $acta)
    {
        $this->authorize('view', $acta);

        $acta->load([
            'institution',
            'institucionDestino',
            'servicioOrigen',
            'oficinaOrigen',
            'servicioDestino',
            'oficinaDestino',
            'creator:id,name',
            'equipos.tipoEquipo',
            'equipos.oficina.service',
            'documents.uploadedBy:id,name',
            'historial.usuario:id,name',
            'historial.equipo:id,tipo,numero_serie',
        ]);

        return view('actas.show', ['acta' => $acta, 'tipoLabels' => Acta::LABELS]);
    }

    public function descargar(Acta $acta): Response
    {
        $this->authorize('view', $acta);

        $acta->load([
            'institution',
            'institucionDestino',
            'servicioOrigen',
            'oficinaOrigen',
            'servicioDestino',
            'oficinaDestino',
            'creator',
            'equipos.tipoEquipo',
        ]);

        $pdf = Pdf::loadView('actas.pdf.'.$acta->tipo, ['acta' => $acta])->setPaper('a4');

        return $pdf->download($acta->codigo.'.pdf');
    }
}
