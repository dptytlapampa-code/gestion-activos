<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\Auditing\AuditLogService;
use App\Services\EquipoListingService;
use App\Services\EquipoStatusResolver;
use App\Services\MantenimientoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EquipoStatusResolver $equipoStatusResolver,
        private readonly EquipoListingService $equipoListingService,
        private readonly MantenimientoService $mantenimientoService,
        private readonly AuditLogService $auditLogService,
    ) {
        $this->authorizeResource(Equipo::class, 'equipo');
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipo::class);

        $listing = $this->equipoListingService->listingState($request);
        $filters = $this->equipoListingService->filtersFromRequest($request);

        $equipos = $this->equipoListingService
            ->buildIndexQuery($request->user(), $listing->search, $filters)
            ->paginate($listing->perPage)
            ->withQueryString();

        return view('equipos.index', [
            'equipos' => $equipos,
            'estados' => Equipo::ESTADOS,
            'filters' => $filters,
            'listing' => $listing,
            'hasActiveFilters' => $this->equipoListingService->hasActiveFilters($listing->search, $filters),
        ]);
    }

    public function create(): View
    {
        $user = request()->user();

        $instituciones = $this->scopedInstituciones($user);
        $selectedInstitutionId = $this->resolveSelectedInstitutionId($user);
        $selectedServiceId = (int) old('service_id');

        $servicios = $this->scopedServicios($user, $selectedInstitutionId > 0 ? $selectedInstitutionId : null);
        $oficinas = $this->scopedOficinas($user, $selectedServiceId > 0 ? $selectedServiceId : null);

        return view('equipos.create', [
            'estados' => Equipo::ESTADOS,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
        ]);
    }

    public function store(StoreEquipoRequest $request): RedirectResponse
    {
        $tipoEquipo = TipoEquipo::query()->findOrFail($request->integer('tipo_equipo_id'));

        $validated = $request->validated();

        $data = [
            'tipo_equipo_id' => $validated['tipo_equipo_id'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'bien_patrimonial' => $validated['bien_patrimonial'],
            'mac_address' => $validated['mac_address'] ?? null,
            'codigo_interno' => $validated['codigo_interno'] ?? null,
            'estado' => $validated['estado'],
            'equipo_status_id' => $this->equipoStatusResolver->resolveIdByEstado($validated['estado'], 'estado'),
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'oficina_id' => $validated['office_id'],
            'tipo' => $tipoEquipo->nombre,
            'numero_serie' => $validated['numero_serie'],
        ];

        $equipo = Equipo::query()->create($data);

        $oficinaDestino = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);

        $ubicacionDestino = $this->mapOfficeLocation($oficinaDestino);

        Movimiento::query()->create([
            'equipo_id' => $equipo->id,
            'user_id' => auth()->id(),
            'tipo_movimiento' => 'ingreso',
            'fecha' => now(),
            'institucion_destino_id' => $ubicacionDestino['institucion_id'],
            'servicio_destino_id' => $ubicacionDestino['servicio_id'],
            'oficina_destino_id' => $ubicacionDestino['oficina_id'],
            'observacion' => 'Ingreso de equipo',
        ]);

        $snapshot = $this->equipmentAuditSnapshot($equipo, $oficinaDestino);

        $this->auditLogService->record([
            'user' => $request->user(),
            'institution_id' => $ubicacionDestino['institucion_id'],
            'module' => 'equipos',
            'action' => 'equipo_creado',
            'entity_type' => 'equipo',
            'entity_id' => $equipo->id,
            'summary' => sprintf('Se dio de alta el equipo %s.', $this->equipmentReference($equipo)),
            'after' => $snapshot,
            'metadata' => [
                'details' => $snapshot,
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);

        return redirect()->route('equipos.index')->with('status', 'Equipo creado correctamente.');
    }

    public function show(Equipo $equipo): View
    {
        $user = request()->user();

        $equipo->load([
            'oficina.service.institution',
            'tipoEquipo',
            'equipoStatus',
            'mantenimientoExternoAbierto.creador',
            'movimientos.user',
            'movimientos.documents',
            'documents.uploadedBy',
        ]);

        $mantenimientos = $equipo->mantenimientos()
            ->with([
                'creador:id,name',
                'estadoResultante:id,name,color',
                'mantenimientoExterno:id,fecha,fecha_ingreso_st,fecha_egreso_st,proveedor,titulo',
            ])
            ->limit(30)
            ->get();

        $mantenimientoExternoAbierto = $equipo->mantenimientoExternoAbierto;
        $tiposMantenimientoDisponibles = $this->mantenimientoService->tiposDisponiblesParaEquipo($equipo);
        $hayInconsistenciaMantenimiento = ($mantenimientoExternoAbierto !== null && $equipo->estado !== Equipo::ESTADO_MANTENIMIENTO)
            || ($mantenimientoExternoAbierto === null && $equipo->estado === Equipo::ESTADO_MANTENIMIENTO);

        $officeIds = $equipo->movimientos
            ->flatMap(fn (Movimiento $movimiento) => [
                $movimiento->oficina_origen_id,
                $movimiento->oficina_destino_id,
            ])
            ->filter()
            ->unique()
            ->values();

        $offices = Office::query()
            ->with('service.institution')
            ->whereIn('id', $officeIds)
            ->get()
            ->keyBy('id');

        $selectedInstitutionId = (int) old('institucion_destino_id');
        $selectedServiceId = (int) old('servicio_destino_id');

        $instituciones = $this->scopedInstituciones($user);
        $servicios = $this->scopedServicios($user, $selectedInstitutionId > 0 ? $selectedInstitutionId : null);
        $oficinas = $this->scopedOficinas($user, $selectedServiceId > 0 ? $selectedServiceId : null);

        return view('equipos.show', [
            'equipo' => $equipo,
            'mantenimientos' => $mantenimientos,
            'mantenimientoExternoAbierto' => $mantenimientoExternoAbierto,
            'tiposMantenimientoDisponibles' => $tiposMantenimientoDisponibles,
            'hayInconsistenciaMantenimiento' => $hayInconsistenciaMantenimiento,
            'offices' => $offices,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
        ]);
    }

    public function edit(Equipo $equipo): View
    {
        $user = request()->user();

        $instituciones = $this->scopedInstituciones($user);
        $selectedInstitutionId = (int) old('institution_id', $equipo->oficina?->service?->institution_id);
        $selectedServiceId = (int) old('service_id', $equipo->oficina?->service_id);
        $servicios = $this->scopedServicios($user, $selectedInstitutionId > 0 ? $selectedInstitutionId : null);
        $oficinas = $this->scopedOficinas($user, $selectedServiceId > 0 ? $selectedServiceId : null);

        return view('equipos.edit', [
            'equipo' => $equipo->load(['oficina.service.institution', 'tipoEquipo']),
            'estados' => Equipo::ESTADOS,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
        ]);
    }

    public function update(UpdateEquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        $tipoEquipo = TipoEquipo::query()->findOrFail($request->integer('tipo_equipo_id'));
        $oficinaOriginal = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);

        $validated = $request->validated();
        $before = $this->equipmentAuditSnapshot($equipo, $oficinaOriginal);

        $data = [
            'tipo_equipo_id' => $validated['tipo_equipo_id'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'bien_patrimonial' => $validated['bien_patrimonial'],
            'mac_address' => $validated['mac_address'] ?? null,
            'codigo_interno' => $validated['codigo_interno'] ?? null,
            'estado' => $validated['estado'],
            'equipo_status_id' => $this->equipoStatusResolver->resolveIdByEstado($validated['estado'], 'estado'),
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'oficina_id' => $validated['office_id'],
            'tipo' => $tipoEquipo->nombre,
            'numero_serie' => $validated['numero_serie'],
        ];

        if ($equipo->offsetExists('_audit_before')) {
            $equipo->offsetUnset('_audit_before');
        }

        $equipo->update($data);

        if ($equipo->wasChanged('oficina_id')) {
            $ubicacionOrigen = $this->mapOfficeLocation($oficinaOriginal);
            $oficinaDestino = Office::query()
                ->with('service.institution')
                ->find($equipo->oficina_id);
            $ubicacionDestino = $this->mapOfficeLocation($oficinaDestino);

            Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'traslado',
                'fecha' => now(),
                'institucion_origen_id' => $ubicacionOrigen['institucion_id'],
                'servicio_origen_id' => $ubicacionOrigen['servicio_id'],
                'oficina_origen_id' => $ubicacionOrigen['oficina_id'],
                'institucion_destino_id' => $ubicacionDestino['institucion_id'],
                'servicio_destino_id' => $ubicacionDestino['servicio_id'],
                'oficina_destino_id' => $ubicacionDestino['oficina_id'],
                'observacion' => 'Traslado de ubicacion',
            ]);
        }

        $oficinaDestino = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);

        $after = $this->equipmentAuditSnapshot($equipo->fresh(), $oficinaDestino);
        $changes = $this->auditLogService->diff($before, $after, $this->equipmentAuditLabels());

        if ($changes !== []) {
            $this->auditLogService->record([
                'user' => $request->user(),
                'institution_id' => $oficinaDestino?->service?->institution?->id ?? $oficinaOriginal?->service?->institution?->id,
                'module' => 'equipos',
                'action' => 'equipo_actualizado',
                'entity_type' => 'equipo',
                'entity_id' => $equipo->id,
                'summary' => $this->equipmentUpdateSummary($equipo, $changes),
                'before' => $before,
                'after' => $after,
                'metadata' => [
                    'details' => $after,
                    'changes' => $changes,
                ],
                'level' => $this->equipmentUpdateLevel($changes),
                'is_critical' => $this->equipmentUpdateIsCritical($changes),
            ]);
        }

        return redirect()->route('equipos.index')->with('status', 'Equipo actualizado correctamente.');
    }

    public function destroy(Request $request, Equipo $equipo): RedirectResponse
    {
        $equipo->loadMissing('oficina.service.institution');
        $before = $this->equipmentAuditSnapshot($equipo, $equipo->oficina);
        $institutionId = $equipo->oficina?->service?->institution?->id;
        $reference = $this->equipmentReference($equipo);
        $entityId = $equipo->id;

        $equipo->delete();

        $this->auditLogService->record([
            'user' => $request->user(),
            'institution_id' => $institutionId,
            'module' => 'equipos',
            'action' => 'equipo_eliminado',
            'entity_type' => 'equipo',
            'entity_id' => $entityId,
            'summary' => sprintf('Se elimino del inventario el equipo %s.', $reference),
            'before' => $before,
            'metadata' => [
                'details' => $before,
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);

        return redirect()->route('equipos.index')->with('status', 'Equipo eliminado correctamente.');
    }

    /**
     * @return array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}
     */
    private function mapOfficeLocation(?Office $office): array
    {
        return [
            'institucion_id' => $office?->service?->institution?->id,
            'servicio_id' => $office?->service?->id,
            'oficina_id' => $office?->id,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function equipmentAuditSnapshot(Equipo $equipo, ?Office $office): array
    {
        return [
            'tipo_equipo' => $equipo->tipo,
            'marca' => $equipo->marca ?: 'Sin marca',
            'modelo' => $equipo->modelo ?: 'Sin modelo',
            'numero_serie' => $equipo->numero_serie ?: 'Sin numero de serie',
            'bien_patrimonial' => $equipo->bien_patrimonial ?: 'Sin bien patrimonial',
            'codigo_interno' => $equipo->codigo_interno ?: 'Sin codigo interno',
            'estado' => $this->estadoLabel($equipo->estado),
            'institucion' => $office?->service?->institution?->nombre ?? 'Sin institucion',
            'servicio' => $office?->service?->nombre ?? 'Sin servicio',
            'oficina' => $office?->nombre ?? 'Sin oficina',
            'fecha_ingreso' => $equipo->fecha_ingreso?->format('d/m/Y') ?? 'Sin fecha de ingreso',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function equipmentAuditLabels(): array
    {
        return [
            'tipo_equipo' => 'Tipo',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'numero_serie' => 'Numero de serie',
            'bien_patrimonial' => 'Bien patrimonial',
            'codigo_interno' => 'Codigo interno',
            'estado' => 'Estado',
            'institucion' => 'Institucion',
            'servicio' => 'Servicio',
            'oficina' => 'Oficina',
            'fecha_ingreso' => 'Fecha de ingreso',
        ];
    }

    /**
     * @param  array<int, array{field:string,label:string,before:mixed,after:mixed}>  $changes
     */
    private function equipmentUpdateSummary(Equipo $equipo, array $changes): string
    {
        $fields = collect($changes)->pluck('field');
        $reference = $this->equipmentReference($equipo);

        if ($fields->contains('estado') && $fields->intersect(['institucion', 'servicio', 'oficina'])->isNotEmpty()) {
            return sprintf('Se actualizo la ficha del equipo %s, incluyendo estado y ubicacion.', $reference);
        }

        if ($fields->contains('estado')) {
            return sprintf('Se cambio el estado del equipo %s.', $reference);
        }

        if ($fields->intersect(['institucion', 'servicio', 'oficina'])->isNotEmpty()) {
            return sprintf('Se actualizo la ubicacion del equipo %s.', $reference);
        }

        return sprintf('Se actualizo la ficha del equipo %s.', $reference);
    }

    /**
     * @param  array<int, array{field:string,label:string,before:mixed,after:mixed}>  $changes
     */
    private function equipmentUpdateLevel(array $changes): string
    {
        return collect($changes)->pluck('field')->contains('estado')
            ? AuditLog::LEVEL_WARNING
            : AuditLog::LEVEL_INFO;
    }

    /**
     * @param  array<int, array{field:string,label:string,before:mixed,after:mixed}>  $changes
     */
    private function equipmentUpdateIsCritical(array $changes): bool
    {
        return collect($changes)
            ->contains(function (array $change): bool {
                return $change['field'] === 'estado'
                    && in_array((string) $change['after'], ['Mantenimiento', 'Baja'], true);
            });
    }

    private function equipmentReference(Equipo $equipo): string
    {
        $parts = collect([
            $equipo->tipo ?: 'Equipo',
            $equipo->numero_serie ? 'NS '.$equipo->numero_serie : null,
            $equipo->bien_patrimonial ? 'BP '.$equipo->bien_patrimonial : null,
        ])->filter()->values();

        return $parts->implode(' / ');
    }

    private function estadoLabel(?string $estado): string
    {
        return match ((string) $estado) {
            Equipo::ESTADO_OPERATIVO => 'Operativo',
            Equipo::ESTADO_PRESTADO => 'Prestado',
            Equipo::ESTADO_EN_MANTENIMIENTO, Equipo::ESTADO_MANTENIMIENTO => 'Mantenimiento',
            Equipo::ESTADO_FUERA_DE_SERVICIO => 'Fuera de servicio',
            Equipo::ESTADO_BAJA => 'Baja',
            default => ucfirst(str_replace('_', ' ', (string) $estado)),
        };
    }

    private function scopedInstituciones(?User $user)
    {
        return Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    private function scopedServicios(?User $user, ?int $institutionId)
    {
        return Service::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('institution_id', $user->institution_id)
            )
            ->when($institutionId !== null, fn ($query) => $query->where('institution_id', $institutionId))
            ->when($institutionId === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institution_id']);
    }

    private function scopedOficinas(?User $user, ?int $serviceId)
    {
        return Office::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), function ($query) use ($user): void {
                $query->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $user->institution_id));
            })
            ->when($serviceId !== null, fn ($query) => $query->where('service_id', $serviceId))
            ->when($serviceId === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'service_id']);
    }

    private function resolveSelectedInstitutionId(?User $user): ?int
    {
        $oldInstitutionId = (int) old('institution_id');

        if ($oldInstitutionId > 0) {
            return $oldInstitutionId;
        }

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN)) {
            return (int) $user->institution_id;
        }

        return null;
    }
}
