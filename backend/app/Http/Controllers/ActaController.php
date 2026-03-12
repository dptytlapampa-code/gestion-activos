<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActaRequest;
use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\User;
use App\Services\ActaPdfDataService;
use App\Services\ActaTraceabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActaController extends Controller
{
    public function __construct(
        private readonly ActaTraceabilityService $traceabilityService,
        private readonly ActaPdfDataService $actaPdfDataService,
    ) {}

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
                fn (Builder $query) => $query->whereIn('institution_id', $user->accessibleInstitutionIds())
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

    public function anular(Request $request, Acta $acta): RedirectResponse
    {
        $this->authorize('anular', $acta);

        $validated = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'max:1000'],
        ]);

        $motivo = trim((string) $validated['motivo_anulacion']);

        if (($acta->status ?? Acta::STATUS_ACTIVA) === Acta::STATUS_ANULADA) {
            return back()->withErrors(['acta' => 'El acta ya se encuentra anulada.']);
        }

        $user = $request->user();

        DB::transaction(function () use ($acta, $motivo, $user): void {
            $acta->refresh();

            if (($acta->status ?? Acta::STATUS_ACTIVA) === Acta::STATUS_ANULADA) {
                return;
            }

            $before = [
                'status' => $acta->status ?? Acta::STATUS_ACTIVA,
                'anulada_por' => $acta->anulada_por,
                'anulada_at' => $acta->anulada_at?->toDateTimeString(),
                'motivo_anulacion' => $acta->motivo_anulacion,
            ];

            $acta->status = Acta::STATUS_ANULADA;
            $acta->anulada_por = $user?->id;
            $acta->anulada_at = now();
            $acta->motivo_anulacion = $motivo;
            $acta->save();

            AuditLog::query()->create([
                'user_id' => $user?->id,
                'action' => 'acta anulada',
                'auditable_type' => Acta::class,
                'auditable_id' => $acta->id,
                'before' => $before,
                'after' => [
                    'status' => $acta->status,
                    'anulada_por' => $acta->anulada_por,
                    'anulada_at' => $acta->anulada_at?->toDateTimeString(),
                    'motivo_anulacion' => $acta->motivo_anulacion,
                ],
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });

        return redirect()->route('actas.show', $acta)->with('status', 'Acta anulada correctamente.');
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
            'annulledBy:id,name',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
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
            'annulledBy',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
        ]);

        $pdfData = array_merge(['acta' => $acta], $this->actaPdfDataService->build($acta));

        $pdf = Pdf::loadView('actas.pdf.'.$acta->tipo, $pdfData)->setPaper('a4');

        return $pdf->download($acta->codigo.'.pdf');
    }
}
