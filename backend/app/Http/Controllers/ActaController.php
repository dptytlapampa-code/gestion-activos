<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActaRequest;
use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Services\ActaPdfDataService;
use App\Services\ActaTraceabilityService;
use App\Services\Auditing\AuditLogService;
use App\Support\Listings\ListingState;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActaController extends Controller
{
    public function __construct(
        private readonly ActaTraceabilityService $traceabilityService,
        private readonly ActaPdfDataService $actaPdfDataService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Acta::class);

        $listing = ListingState::fromRequest($request);
        $validated = $request->validate([
            'tipo' => ['nullable', Rule::in(Acta::TIPOS)],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $actas = Acta::query()
            ->withCount('equipos')
            ->with(['creator:id,name'])
            ->visibleToUser($request->user())
            ->searchIndex($listing->search)
            ->applyIndexFilters($validated)
            ->latest('fecha')
            ->latest('id')
            ->paginate($listing->perPage)
            ->withQueryString();

        return view('actas.index', [
            'actas' => $actas,
            'tipos' => Acta::TIPOS,
            'tipoLabels' => Acta::LABELS,
            'filters' => $validated,
            'listing' => $listing,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Acta::class);

        $user = $request->user();
        $activeInstitutionId = $this->activeInstitutionId($user);
        $destinationInstitutions = $this->scopedActaInstitutions($user);
        $originInstitutions = $this->operatesWithGlobalScope($user)
            ? $destinationInstitutions
            : $destinationInstitutions
                ->where('id', $activeInstitutionId)
                ->values();
        $tiposEquipo = TipoEquipo::query()->orderBy('nombre')->get(['id', 'nombre']);

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
                        'uuid' => $equipo->uuid,
                        'label' => trim(sprintf('%s %s %s', $equipo->tipo, $equipo->marca, $equipo->modelo)),
                        'tipo' => $equipo->tipo,
                        'tipo_equipo_id' => $equipo->tipo_equipo_id,
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
                        'estado_label' => match ($equipo->estado) {
                            Equipo::ESTADO_OPERATIVO => 'Operativo',
                            Equipo::ESTADO_PRESTADO => 'Prestado',
                            Equipo::ESTADO_EN_MANTENIMIENTO => 'Mantenimiento',
                            Equipo::ESTADO_FUERA_DE_SERVICIO => 'Fuera de servicio',
                            Equipo::ESTADO_BAJA => 'Baja',
                            default => ucfirst(str_replace('_', ' ', (string) $equipo->estado)),
                        },
                        'ubicacion_resumida' => collect([
                            $equipo->oficina?->service?->institution?->nombre,
                            $equipo->oficina?->service?->nombre,
                            $equipo->oficina?->nombre,
                        ])->filter()->implode(' / '),
                        'cantidad' => (int) ($meta['cantidad'] ?? 1),
                        'accesorios' => $meta['accesorios'] ?? '',
                    ];
                });

        return view('actas.create', [
            'tipos' => Acta::creatableTypes(),
            'tipoLabels' => Acta::LABELS,
            'destinationInstitutions' => $destinationInstitutions,
            'originInstitutions' => $originInstitutions,
            'activeInstitutionId' => $activeInstitutionId,
            'tipoEquipoOptions' => $tiposEquipo,
            'searchEndpoints' => [
                'actaEquipos' => route('api.search.acta-equipos', [], false),
                'services' => route('api.search.services', [], false),
                'offices' => route('api.search.offices', [], false),
            ],
            'estadoOptions' => [
                ['value' => Equipo::ESTADO_OPERATIVO, 'label' => 'Operativo'],
                ['value' => Equipo::ESTADO_PRESTADO, 'label' => 'Prestado'],
                ['value' => Equipo::ESTADO_EN_MANTENIMIENTO, 'label' => 'Mantenimiento'],
                ['value' => Equipo::ESTADO_FUERA_DE_SERVICIO, 'label' => 'Fuera de servicio'],
                ['value' => Equipo::ESTADO_BAJA, 'label' => 'Baja'],
            ],
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

            $after = [
                'status' => ucfirst((string) $acta->status),
                'anulada_por' => $user?->name ?? 'Usuario no identificado',
                'anulada_at' => $acta->anulada_at?->format('d/m/Y H:i'),
                'motivo_anulacion' => $acta->motivo_anulacion,
            ];

            $this->auditLogService->record([
                'user' => $user,
                'institution_id' => $acta->institution_id,
                'module' => 'actas',
                'action' => 'acta_anulada',
                'entity_type' => 'acta',
                'entity_id' => $acta->id,
                'summary' => sprintf('Se anulo el acta %s.', $acta->codigo),
                'before' => [
                    'status' => ucfirst((string) ($before['status'] ?? Acta::STATUS_ACTIVA)),
                    'motivo_anulacion' => $before['motivo_anulacion'] ?? 'Sin anular',
                ],
                'after' => $after,
                'metadata' => [
                    'details' => array_filter([
                        'codigo' => $acta->codigo,
                        'tipo' => ucfirst(strtolower(Acta::LABELS[$acta->tipo] ?? $acta->tipo)),
                        'motivo_anulacion' => $acta->motivo_anulacion,
                        'anulada_por' => $user?->name,
                    ], fn (mixed $value): bool => $value !== null && $value !== ''),
                    'changes' => [
                        [
                            'field' => 'status',
                            'label' => 'Estado',
                            'before' => ucfirst((string) ($before['status'] ?? Acta::STATUS_ACTIVA)),
                            'after' => ucfirst((string) $acta->status),
                        ],
                        [
                            'field' => 'motivo_anulacion',
                            'label' => 'Motivo de anulacion',
                            'before' => $before['motivo_anulacion'] ?? 'Sin motivo',
                            'after' => $acta->motivo_anulacion,
                        ],
                    ],
                ],
                'level' => AuditLog::LEVEL_CRITICAL,
                'is_critical' => true,
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

    private function scopedActaInstitutions(?\App\Models\User $user): \Illuminate\Support\Collection
    {
        return $this->applyGlobalAdministrationScope(
            \App\Models\Institution::query(),
            'id',
            $user
        )
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'scope_type']);
    }
}
