<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\RecepcionTecnica;
use App\Models\User;
use App\Services\Auditing\AuditLogService;
use App\Support\Listings\ListingState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class RecepcionTecnicaService
{
    public const TRAY_EN_MESA = 'en_mesa';
    public const TRAY_LISTOS = 'listos';
    public const TRAY_PENDIENTES = 'pendientes';
    public const TRAY_FINALIZADOS = 'finalizados';
    public const TRAY_TODOS = 'todos';

    public const TRAY_LABELS = [
        self::TRAY_EN_MESA => 'En mesa',
        self::TRAY_LISTOS => 'Listos para entregar',
        self::TRAY_PENDIENTES => 'Pendientes',
        self::TRAY_FINALIZADOS => 'Finalizados',
        self::TRAY_TODOS => 'Todos',
    ];

    public const MODO_EQUIPO_EXISTENTE = 'existente';
    public const MODO_EQUIPO_NUEVO = 'nuevo';

    public const MODO_INCORPORACION_EXISTENTE = 'existente';
    public const MODO_INCORPORACION_NUEVO = 'nuevo';

    public function __construct(
        private readonly ActiveInstitutionContext $activeInstitutionContext,
        private readonly AuditLogService $auditLogService,
        private readonly EquipoRegistrationService $equipoRegistrationService,
        private readonly MantenimientoService $mantenimientoService,
    ) {}

    public function listingState(Request $request): ListingState
    {
        return ListingState::fromRequest($request);
    }

    /**
     * @return array<string, string>
     */
    public function filtersFromRequest(Request $request): array
    {
        return [
            'fecha_desde' => trim((string) $request->query('fecha_desde', '')),
            'fecha_hasta' => trim((string) $request->query('fecha_hasta', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'numero_serie' => trim((string) $request->query('numero_serie', '')),
            'bien_patrimonial' => trim((string) $request->query('bien_patrimonial', '')),
            'marca_modelo' => trim((string) $request->query('marca_modelo', '')),
            'procedencia' => trim((string) $request->query('procedencia', '')),
            'persona_entrega' => trim((string) $request->query('persona_entrega', '')),
        ];
    }

    public function quickViewFromRequest(Request $request, string $default = RecepcionTecnica::VISTA_ACTIVOS): string
    {
        return RecepcionTecnica::normalizeQuickView($request->query('vista'), $default);
    }

    public function operationalTrayFromRequest(Request $request, string $default = self::TRAY_EN_MESA): string
    {
        $tray = trim((string) $request->query('bandeja', ''));

        if (array_key_exists($tray, self::TRAY_LABELS)) {
            return $tray;
        }

        return match ($this->quickViewFromRequest($request)) {
            RecepcionTecnica::VISTA_LISTOS => self::TRAY_LISTOS,
            RecepcionTecnica::VISTA_CERRADOS => self::TRAY_FINALIZADOS,
            RecepcionTecnica::VISTA_TODOS => self::TRAY_TODOS,
            default => $default,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function buildIndexQuery(?User $user, string $search, array $filters, string $quickView): Builder
    {
        return $this->baseIndexQuery($user, $search, $filters)
            ->applyQuickView($quickView)
            ->operationalOrder();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function buildOperationalTrayQuery(?User $user, string $search, array $filters, string $tray): Builder
    {
        return $this->applyOperationalTray(
            $this->baseIndexQuery($user, $search, $filters),
            $tray
        )->operationalOrder();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function quickViewCounts(?User $user, string $search, array $filters): array
    {
        $filtersWithoutExactStatus = array_merge($filters, ['estado' => '']);
        $query = $this->baseIndexQuery($user, $search, $filtersWithoutExactStatus);

        return collect(RecepcionTecnica::VISTA_LABELS)
            ->mapWithKeys(function (string $label, string $view) use ($query): array {
                return [$view => (clone $query)->applyQuickView($view)->count()];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function operationalTrayCounts(?User $user, string $search, array $filters): array
    {
        $filtersWithoutExactStatus = array_merge($filters, ['estado' => '']);
        $query = $this->baseIndexQuery($user, $search, $filtersWithoutExactStatus);

        return collect(self::TRAY_LABELS)
            ->mapWithKeys(function (string $label, string $tray) use ($query): array {
                $builder = clone $query;
                $this->applyOperationalTray($builder, $tray);

                return [$tray => $builder->count()];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseIndexQuery(?User $user, string $search, array $filters): Builder
    {
        return RecepcionTecnica::query()
            ->with([
                'creator:id,name',
                'recibidoPor:id,name',
                'cerradoPor:id,name',
                'institution:id,nombre',
                'procedenciaInstitution:id,nombre',
                'equipo:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
                'equipoCreado:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
                'maintenanceRecord:id,recepcion_tecnica_id,equipo_id,tipo,titulo,fecha',
            ])
            ->visibleToUser($user)
            ->searchIndex($search)
            ->applyIndexFilters($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hasActiveFilters(string $search, array $filters): bool
    {
        if (trim($search) !== '') {
            return true;
        }

        return collect($filters)->contains(fn (mixed $value): bool => trim((string) $value) !== '');
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return RecepcionTecnica::LABELS;
    }

    /**
     * @return array<string, string>
     */
    public function trackingStatusOptions(): array
    {
        return collect(RecepcionTecnica::ESTADOS_DE_SEGUIMIENTO)
            ->mapWithKeys(fn (string $status): array => [$status => RecepcionTecnica::LABELS[$status]])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function closureStatusOptions(): array
    {
        return collect(RecepcionTecnica::ESTADOS_DE_CIERRE)
            ->mapWithKeys(fn (string $status): array => [$status => RecepcionTecnica::LABELS[$status]])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function egressConditionOptions(): array
    {
        return RecepcionTecnica::CONDICION_LABELS;
    }

    private function applyOperationalTray(Builder $query, string $tray): Builder
    {
        return match ($tray) {
            self::TRAY_LISTOS => $query->where('estado', RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR),
            self::TRAY_PENDIENTES => $query->where('estado', RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO),
            self::TRAY_FINALIZADOS => $query->history(),
            self::TRAY_TODOS => $query,
            default => $query->whereIn('estado', [
                RecepcionTecnica::ESTADO_RECIBIDO,
                RecepcionTecnica::ESTADO_EN_DIAGNOSTICO,
                RecepcionTecnica::ESTADO_EN_REPARACION,
            ]),
        };
    }

    public function defaultReceptionTimestamp(): string
    {
        return now()->format('Y-m-d\TH:i');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): RecepcionTecnica
    {
        $institutionId = $this->resolveActiveInstitutionId($user);
        $mode = (string) ($data['modo_equipo'] ?? self::MODO_EQUIPO_NUEVO);

        try {
            /** @var RecepcionTecnica $recepcionTecnica */
            $recepcionTecnica = DB::transaction(function () use ($user, $data, $institutionId, $mode): RecepcionTecnica {
                $recepcionTecnica = new RecepcionTecnica($this->basePayload($data, $institutionId, $user));
                $recepcionTecnica->save();

                if ($mode === self::MODO_EQUIPO_EXISTENTE) {
                    $equipo = $this->resolveOperableEquipoWithinScope($user, (int) $data['equipo_id']);
                    $this->assertNoOpenReceptionForEquipment($equipo->id);
                    $this->syncSnapshotWithEquipo($recepcionTecnica, $equipo, false);
                    $recepcionTecnica->equipo_id = $equipo->id;
                    $recepcionTecnica->save();
                }

                if ($mode === self::MODO_EQUIPO_NUEVO && (bool) ($data['incorporar_equipo'] ?? false)) {
                    $equipo = $this->equipoRegistrationService->create(
                        $user,
                        $this->equipmentPayload($data),
                        [
                            'movement_observation' => sprintf('Ingreso tecnico %s incorporado al sistema.', $recepcionTecnica->codigo),
                            'audit_summary' => sprintf(
                                'Se dio de alta el equipo asociado al ingreso tecnico %s.',
                                $recepcionTecnica->codigo
                            ),
                        ]
                    );

                    $this->syncSnapshotWithEquipo($recepcionTecnica, $equipo, true);
                    $recepcionTecnica->equipo_creado_id = $equipo->id;
                    $recepcionTecnica->save();
                }

                $this->recordCreated($recepcionTecnica, $user);

                if ($recepcionTecnica->equipo_id !== null) {
                    $this->recordLinkedExistingEquipo($recepcionTecnica, $user, $recepcionTecnica->equipo);
                }

                if ($recepcionTecnica->equipo_creado_id !== null) {
                    $this->recordCreatedEquipo($recepcionTecnica, $user, $recepcionTecnica->equipoCreado);
                }

                return $recepcionTecnica;
            }, 3);
        } catch (QueryException $exception) {
            if ($this->isOpenReceptionUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'equipo_id' => 'Este equipo ya tiene un ingreso tecnico abierto.',
                ]);
            }

            throw $exception;
        }

        return $this->loadFull($recepcionTecnica);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function incorporate(User $user, RecepcionTecnica $recepcionTecnica, array $data): RecepcionTecnica
    {
        if (! $recepcionTecnica->canBeIncorporated()) {
            throw ValidationException::withMessages([
                'equipo_id' => 'Este ingreso tecnico ya tiene un equipo vinculado o incorporado.',
            ]);
        }

        $mode = (string) ($data['modo_incorporacion'] ?? self::MODO_INCORPORACION_NUEVO);

        try {
            DB::transaction(function () use ($user, $recepcionTecnica, $data, $mode): void {
                $recepcionTecnica->refresh();

                if (! $recepcionTecnica->canBeIncorporated()) {
                    throw ValidationException::withMessages([
                        'equipo_id' => 'Este ingreso tecnico ya tiene un equipo vinculado o incorporado.',
                    ]);
                }

                if ($mode === self::MODO_INCORPORACION_EXISTENTE) {
                    $equipo = $this->resolveOperableEquipoWithinScope($user, (int) $data['equipo_id']);
                    $this->assertNoOpenReceptionForEquipment($equipo->id, $recepcionTecnica->id);
                    $this->syncSnapshotWithEquipo($recepcionTecnica, $equipo, false);
                    $recepcionTecnica->equipo_id = $equipo->id;
                    $recepcionTecnica->save();
                    $this->recordLinkedExistingEquipo($recepcionTecnica, $user, $equipo);

                    return;
                }

                $equipo = $this->equipoRegistrationService->create(
                    $user,
                    $this->equipmentPayload($data),
                    [
                        'movement_observation' => sprintf('Ingreso tecnico %s incorporado posteriormente al sistema.', $recepcionTecnica->codigo),
                        'audit_summary' => sprintf(
                            'Se dio de alta el equipo desde la incorporacion posterior del ingreso tecnico %s.',
                            $recepcionTecnica->codigo
                        ),
                    ]
                );

                $this->syncSnapshotWithEquipo($recepcionTecnica, $equipo, true);
                $recepcionTecnica->equipo_creado_id = $equipo->id;
                $recepcionTecnica->save();
                $this->recordCreatedEquipo($recepcionTecnica, $user, $equipo);
            }, 3);
        } catch (QueryException $exception) {
            if ($this->isOpenReceptionUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'equipo_id' => 'Este equipo ya tiene un ingreso tecnico abierto.',
                ]);
            }

            throw $exception;
        }

        return $this->loadFull($recepcionTecnica->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(User $user, RecepcionTecnica $recepcionTecnica, array $data): RecepcionTecnica
    {
        $targetStatus = (string) $data['estado'];

        $before = $this->trackingSnapshot($recepcionTecnica);

        if (
            $recepcionTecnica->estado === $targetStatus
            && $this->nullableString($data['observaciones_internas'] ?? null) === $recepcionTecnica->observaciones_internas
            && $this->nullableString($data['diagnostico'] ?? null) === $recepcionTecnica->diagnostico
            && $this->nullableString($data['accion_realizada'] ?? null) === $recepcionTecnica->accion_realizada
            && $this->nullableString($data['solucion_aplicada'] ?? null) === $recepcionTecnica->solucion_aplicada
            && $this->nullableString($data['informe_tecnico'] ?? null) === $recepcionTecnica->informe_tecnico
            && $this->nullableString($data['motivo_anulacion'] ?? null) === $recepcionTecnica->motivo_anulacion
        ) {
            throw ValidationException::withMessages([
                'estado' => 'No hay cambios para guardar en el seguimiento del ingreso tecnico.',
            ]);
        }

        $this->assertTrackingTransitionAllowed($recepcionTecnica, $targetStatus);

        $recepcionTecnica->estado = $targetStatus;
        $recepcionTecnica->status_changed_at = now();
        $recepcionTecnica->observaciones_internas = $this->nullableString($data['observaciones_internas'] ?? $recepcionTecnica->observaciones_internas);
        $recepcionTecnica->diagnostico = $this->nullableString($data['diagnostico'] ?? $recepcionTecnica->diagnostico);
        $recepcionTecnica->accion_realizada = $this->nullableString($data['accion_realizada'] ?? $recepcionTecnica->accion_realizada);
        $recepcionTecnica->solucion_aplicada = $this->nullableString($data['solucion_aplicada'] ?? $recepcionTecnica->solucion_aplicada);
        $recepcionTecnica->informe_tecnico = $this->nullableString($data['informe_tecnico'] ?? $recepcionTecnica->informe_tecnico);

        if ($targetStatus === RecepcionTecnica::ESTADO_CANCELADO) {
            $recepcionTecnica->anulada_at = now();
            $recepcionTecnica->anulada_por = $user->id;
            $recepcionTecnica->motivo_anulacion = $this->nullableString($data['motivo_anulacion'] ?? null);
        }

        $recepcionTecnica->save();

        $after = $this->trackingSnapshot($recepcionTecnica);

        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => $targetStatus === RecepcionTecnica::ESTADO_CANCELADO
                ? 'ingreso_tecnico_cancelado'
                : 'ingreso_tecnico_seguimiento_actualizado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => $targetStatus === RecepcionTecnica::ESTADO_CANCELADO
                ? sprintf('Se cancelo el ingreso tecnico %s.', $recepcionTecnica->codigo)
                : sprintf(
                    'Se actualizo el seguimiento del ingreso tecnico %s a %s.',
                    $recepcionTecnica->codigo,
                    $recepcionTecnica->statusLabel()
                ),
            'before' => $before,
            'after' => $after,
            'metadata' => [
                'details' => array_filter([
                    'codigo' => $recepcionTecnica->codigo,
                    'motivo_anulacion' => $recepcionTecnica->motivo_anulacion,
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
                'changes' => $this->auditLogService->diff($before, $after, [
                    'estado' => 'Estado',
                    'diagnostico' => 'Diagnostico',
                    'accion_realizada' => 'Accion realizada',
                    'solucion_aplicada' => 'Solucion aplicada',
                    'observaciones_internas' => 'Observaciones internas',
                ]),
            ],
            'level' => $targetStatus === RecepcionTecnica::ESTADO_CANCELADO ? AuditLog::LEVEL_WARNING : AuditLog::LEVEL_INFO,
            'is_critical' => $targetStatus === RecepcionTecnica::ESTADO_CANCELADO,
        ]);

        return $this->loadFull($recepcionTecnica->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(User $user, RecepcionTecnica $recepcionTecnica, array $data): RecepcionTecnica
    {
        $closeStatus = (string) $data['estado_cierre'];

        /** @var RecepcionTecnica $closedReception */
        $closedReception = DB::transaction(function () use ($user, $recepcionTecnica, $data, $closeStatus): RecepcionTecnica {
            /** @var RecepcionTecnica $lockedReception */
            $lockedReception = RecepcionTecnica::query()
                ->with(['equipo.oficina.service.institution', 'equipoCreado.oficina.service.institution', 'maintenanceRecord'])
                ->lockForUpdate()
                ->findOrFail($recepcionTecnica->id);

            if ($lockedReception->isCancelled()) {
                throw ValidationException::withMessages([
                    'estado_cierre' => 'No se puede cerrar un ingreso tecnico cancelado.',
                ]);
            }

            if ($lockedReception->isClosed()) {
                throw ValidationException::withMessages([
                    'estado_cierre' => 'Este ingreso tecnico ya se encuentra cerrado.',
                ]);
            }

            $equipo = $lockedReception->resolvedEquipo();

            if (! $equipo instanceof Equipo) {
                throw ValidationException::withMessages([
                    'equipo_id' => 'No se puede cerrar este ingreso tecnico hasta vincular el equipo al inventario.',
                ]);
            }

            if ($lockedReception->maintenanceRecord instanceof Mantenimiento) {
                throw ValidationException::withMessages([
                    'estado_cierre' => 'Este ingreso tecnico ya genero su historial tecnico final.',
                ]);
            }

            $lockedReception->estado = $closeStatus;
            $lockedReception->cerrado_por = $user->id;
            $lockedReception->status_changed_at = now();
            $lockedReception->entregada_at = Carbon::parse((string) $data['fecha_entrega_real']);
            $lockedReception->diagnostico = $this->nullableString($data['diagnostico'] ?? null);
            $lockedReception->accion_realizada = $this->nullableString($data['accion_realizada'] ?? null);
            $lockedReception->solucion_aplicada = $this->nullableString($data['solucion_aplicada'] ?? null);
            $lockedReception->informe_tecnico = $this->nullableString($data['informe_tecnico'] ?? null);
            $lockedReception->observaciones_cierre = $this->nullableString($data['observaciones_finales'] ?? null);
            $lockedReception->persona_retiro_nombre = $this->nullableString($data['persona_retiro_nombre'] ?? null);
            $lockedReception->persona_retiro_documento = $this->nullableString($data['persona_retiro_documento'] ?? null);
            $lockedReception->persona_retiro_cargo = $this->nullableString($data['persona_retiro_cargo'] ?? null);
            $lockedReception->condicion_egreso = $this->nullableString($data['condicion_egreso'] ?? null);
            $lockedReception->save();

            $mantenimiento = $this->mantenimientoService->registrarDesdeIngresoTecnico(
                $equipo,
                $user,
                $lockedReception
            );

            $this->recordClosed($lockedReception, $user, $mantenimiento);

            return $lockedReception;
        }, 3);

        return $this->loadFull($closedReception->fresh());
    }

    /**
     * @return array{
     *     recepcionTecnica: RecepcionTecnica,
     *     publicUrl: string,
     *     qrSvg: string|null,
     *     generatedAt: string
     * }
     */
    public function registerPrint(User $user, RecepcionTecnica $recepcionTecnica): array
    {
        $recepcionTecnica->refresh();
        $wasPrinted = (int) $recepcionTecnica->print_count > 0;
        $now = now();

        $recepcionTecnica->print_count = (int) $recepcionTecnica->print_count + 1;
        $recepcionTecnica->printed_at ??= $now;
        $recepcionTecnica->last_printed_at = $now;
        $recepcionTecnica->save();

        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => $wasPrinted ? 'ingreso_tecnico_reimpreso' : 'ingreso_tecnico_impreso',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => $wasPrinted
                ? sprintf('Se reimprimio el comprobante del ingreso tecnico %s.', $recepcionTecnica->codigo)
                : sprintf('Se imprimio el comprobante del ingreso tecnico %s.', $recepcionTecnica->codigo),
            'after' => [
                'codigo' => $recepcionTecnica->codigo,
                'impresiones' => (string) $recepcionTecnica->print_count,
            ],
            'metadata' => [
                'details' => [
                    'codigo' => $recepcionTecnica->codigo,
                    'print_count' => $recepcionTecnica->print_count,
                    'last_printed_at' => $recepcionTecnica->last_printed_at?->toDateTimeString(),
                ],
            ],
            'level' => AuditLog::LEVEL_INFO,
        ]);

        return $this->printData($recepcionTecnica->fresh());
    }

    /**
     * @return array{
     *     recepcionTecnica: RecepcionTecnica,
     *     publicUrl: string,
     *     trackingStatusOptions: array<string, string>,
     *     closureStatusOptions: array<string, string>,
     *     egressConditionOptions: array<string, string>,
     *     returnTo: string|null,
     *     backToListUrl: string,
     *     backToListLabel: string
     * }
     */
    public function detailData(RecepcionTecnica $recepcionTecnica, ?string $returnTo = null): array
    {
        $recepcionTecnica = $this->loadFull($recepcionTecnica);
        $sanitizedReturnTo = $this->sanitizeReturnUrl($returnTo);
        $backQuickView = $this->quickViewFromReturnUrl($sanitizedReturnTo);

        return [
            'recepcionTecnica' => $recepcionTecnica,
            'publicUrl' => route('mesa-tecnica.recepciones-tecnicas.public.show', ['uuid' => $recepcionTecnica->uuid]),
            'trackingStatusOptions' => $this->trackingStatusOptions(),
            'closureStatusOptions' => $this->closureStatusOptions(),
            'egressConditionOptions' => $this->egressConditionOptions(),
            'returnTo' => $sanitizedReturnTo,
            'backToListUrl' => $sanitizedReturnTo ?? route('mesa-tecnica.recepciones-tecnicas.index'),
            'backToListLabel' => $backQuickView !== null
                ? 'Volver a '.RecepcionTecnica::VISTA_LABELS[$backQuickView]
                : 'Volver a tickets',
        ];
    }

    /**
     * @return array{
     *     recepcionTecnica: RecepcionTecnica,
     *     publicStatus: string,
     *     publicProgress: string
     * }
     */
    public function publicData(RecepcionTecnica $recepcionTecnica): array
    {
        $recepcionTecnica = $this->loadFull($recepcionTecnica);

        return [
            'recepcionTecnica' => $recepcionTecnica,
            'publicStatus' => $recepcionTecnica->statusLabel(),
            'publicProgress' => match ($recepcionTecnica->estado) {
                RecepcionTecnica::ESTADO_RECIBIDO => 'El equipo fue recibido y quedo registrado en Mesa Tecnica.',
                RecepcionTecnica::ESTADO_EN_DIAGNOSTICO => 'El equipo se encuentra en diagnostico.',
                RecepcionTecnica::ESTADO_EN_REPARACION => 'El equipo se encuentra en reparacion.',
                RecepcionTecnica::ESTADO_EN_ESPERA_REPUESTO => 'El equipo se encuentra en espera de repuesto o insumo.',
                RecepcionTecnica::ESTADO_LISTO_PARA_ENTREGAR => 'La reparacion finalizo y el equipo esta listo para entregar.',
                RecepcionTecnica::ESTADO_ENTREGADO => 'El circuito tecnico fue cerrado correctamente.',
                RecepcionTecnica::ESTADO_NO_REPARABLE => 'El circuito fue cerrado como no reparable.',
                RecepcionTecnica::ESTADO_CANCELADO => 'El ingreso tecnico fue cancelado.',
                default => 'El comprobante se encuentra registrado en Mesa Tecnica.',
            },
        ];
    }

    /**
     * @return array{
     *     recepcionTecnica: RecepcionTecnica,
     *     publicUrl: string,
     *     qrSvg: string|null,
     *     generatedAt: string
     * }
     */
    public function printData(RecepcionTecnica $recepcionTecnica): array
    {
        $recepcionTecnica = $this->loadFull($recepcionTecnica);
        $publicUrl = route('mesa-tecnica.recepciones-tecnicas.public.show', ['uuid' => $recepcionTecnica->uuid]);
        $qrSvg = null;

        try {
            $qrSvg = QrCode::size(132)
                ->margin(1)
                ->generate($publicUrl);
        } catch (Throwable $exception) {
            Log::warning('ingreso tecnico qr generation failed', [
                'recepcion_tecnica_id' => $recepcionTecnica->id,
                'codigo' => $recepcionTecnica->codigo,
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);
        }

        return [
            'recepcionTecnica' => $recepcionTecnica,
            'publicUrl' => $publicUrl,
            'qrSvg' => $qrSvg,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];
    }

    public function sanitizeReturnUrl(?string $returnTo): ?string
    {
        $normalized = trim((string) $returnTo);

        if ($normalized === '') {
            return null;
        }

        $relativeBase = route('mesa-tecnica.recepciones-tecnicas.index', [], false);
        $absoluteBase = route('mesa-tecnica.recepciones-tecnicas.index');

        if (str_starts_with($normalized, $relativeBase) || str_starts_with($normalized, $absoluteBase)) {
            return $normalized;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function basePayload(array $data, int $institutionId, User $user): array
    {
        $ingresadoAt = Carbon::parse((string) $data['fecha_hora_ingreso']);

        return [
            'institution_id' => $institutionId,
            'created_by' => $user->id,
            'recibido_por' => $user->id,
            'fecha_recepcion' => $ingresadoAt->toDateString(),
            'ingresado_at' => $ingresadoAt,
            'estado' => RecepcionTecnica::ESTADO_RECIBIDO,
            'status_changed_at' => now(),
            'sector_receptor' => $this->nullableString($data['sector_receptor'] ?? null) ?? 'Mesa Tecnica / Nivel Central',
            'referencia_equipo' => $this->nullableString($data['referencia_equipo'] ?? null),
            'tipo_equipo_texto' => $this->nullableString($data['tipo_equipo_texto'] ?? null),
            'marca' => $this->nullableString($data['marca'] ?? null),
            'modelo' => $this->nullableString($data['modelo'] ?? null),
            'numero_serie' => $this->nullableString($data['numero_serie'] ?? null),
            'bien_patrimonial' => $this->nullableString($data['bien_patrimonial'] ?? null),
            'procedencia_institution_id' => $this->nullableInt($data['procedencia_institution_id'] ?? null),
            'procedencia_service_id' => $this->nullableInt($data['procedencia_service_id'] ?? null),
            'procedencia_office_id' => $this->nullableInt($data['procedencia_office_id'] ?? null),
            'procedencia_hospital' => $this->nullableString($data['procedencia_hospital'] ?? null),
            'procedencia_libre' => $this->nullableString($data['procedencia_libre'] ?? null),
            'persona_nombre' => $this->nullableString($data['persona_nombre'] ?? null),
            'persona_documento' => $this->nullableString($data['persona_documento'] ?? null),
            'persona_telefono' => $this->nullableString($data['persona_telefono'] ?? null),
            'persona_area' => $this->nullableString($data['persona_area'] ?? null),
            'persona_institucion' => $this->nullableString($data['persona_institucion'] ?? null),
            'persona_relacion_equipo' => $this->nullableString($data['persona_relacion_equipo'] ?? null),
            'falla_motivo' => $this->nullableString($data['falla_motivo'] ?? null),
            'descripcion_falla' => $this->nullableString($data['descripcion_falla'] ?? null),
            'accesorios_entregados' => $this->nullableString($data['accesorios_entregados'] ?? null),
            'estado_fisico_inicial' => $this->nullableString($data['estado_fisico_inicial'] ?? null),
            'observaciones_recepcion' => $this->nullableString($data['observaciones_recepcion'] ?? null),
            'observaciones_internas' => $this->nullableString($data['observaciones_internas'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function equipmentPayload(array $data): array
    {
        return [
            'institution_id' => $data['institution_id'],
            'service_id' => $data['service_id'],
            'office_id' => $data['office_id'] ?? $data['oficina_id'] ?? null,
            'tipo_equipo_id' => $data['tipo_equipo_id'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'numero_serie' => $data['numero_serie'] ?? null,
            'bien_patrimonial' => $data['bien_patrimonial'] ?? null,
            'mac_address' => $data['mac_address'] ?? null,
            'estado' => $data['estado'],
            'fecha_ingreso' => $data['fecha_ingreso'],
        ];
    }

    private function resolveActiveInstitutionId(User $user): int
    {
        $institutionId = $this->activeInstitutionContext->currentId($user);

        if ($institutionId === null || ! $user->canAccessInstitution($institutionId)) {
            throw ValidationException::withMessages([
                'institution_id' => 'Debe seleccionar una institucion activa habilitada para registrar ingresos tecnicos.',
            ]);
        }

        return $institutionId;
    }

    private function resolveOperableEquipoWithinScope(User $user, int $equipoId): Equipo
    {
        $equipo = Equipo::query()
            ->visibleToUser($user)
            ->with(['tipoEquipo:id,nombre', 'oficina.service.institution', 'equipoStatus'])
            ->find($equipoId);

        if (! $equipo instanceof Equipo) {
            throw ValidationException::withMessages([
                'equipo_id' => 'No se encontro un equipo valido dentro del alcance actual.',
            ]);
        }

        if ($equipo->isBaja()) {
            throw ValidationException::withMessages([
                'equipo_id' => 'Este equipo esta en baja y no admite nuevos ingresos tecnicos.',
            ]);
        }

        return $equipo;
    }

    private function syncSnapshotWithEquipo(RecepcionTecnica $recepcionTecnica, Equipo $equipo, bool $created): void
    {
        $equipo->loadMissing(['tipoEquipo:id,nombre', 'oficina.service.institution']);

        $recepcionTecnica->referencia_equipo = $equipo->reference();
        $recepcionTecnica->tipo_equipo_texto = $equipo->tipo ?: $equipo->tipoEquipo?->nombre;
        $recepcionTecnica->marca = $equipo->marca;
        $recepcionTecnica->modelo = $equipo->modelo;
        $recepcionTecnica->numero_serie = $equipo->numero_serie;
        $recepcionTecnica->bien_patrimonial = $equipo->bien_patrimonial;
        $recepcionTecnica->codigo_interno_equipo = $equipo->codigo_interno;
        $recepcionTecnica->procedencia_institution_id ??= $equipo->oficina?->service?->institution?->id;
        $recepcionTecnica->procedencia_service_id ??= $equipo->oficina?->service?->id;
        $recepcionTecnica->procedencia_office_id ??= $equipo->oficina?->id;

        if ($created) {
            $recepcionTecnica->equipo_creado_id = $equipo->id;
        } else {
            $recepcionTecnica->equipo_id = $equipo->id;
        }
    }

    private function assertNoOpenReceptionForEquipment(int $equipoId, ?int $exceptReceptionId = null): void
    {
        $openReception = RecepcionTecnica::query()
            ->open()
            ->forResolvedEquipment($equipoId)
            ->when($exceptReceptionId !== null, fn (Builder $query) => $query->where('id', '!=', $exceptReceptionId))
            ->exists();

        if ($openReception) {
            throw ValidationException::withMessages([
                'equipo_id' => 'Este equipo ya tiene un ingreso tecnico abierto.',
            ]);
        }
    }

    private function assertTrackingTransitionAllowed(RecepcionTecnica $recepcionTecnica, string $targetStatus): void
    {
        if (! in_array($targetStatus, RecepcionTecnica::ESTADOS_DE_SEGUIMIENTO, true)) {
            throw ValidationException::withMessages([
                'estado' => 'El estado seleccionado no es valido para el seguimiento del ingreso tecnico.',
            ]);
        }

        if ($recepcionTecnica->isClosed()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede modificar un ingreso tecnico que ya fue cerrado.',
            ]);
        }

        if ($recepcionTecnica->isCancelled()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede modificar un ingreso tecnico que ya fue cancelado.',
            ]);
        }
    }

    private function recordCreated(RecepcionTecnica $recepcionTecnica, User $user): void
    {
        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => 'ingreso_tecnico_creado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf('Se registro el ingreso tecnico %s.', $recepcionTecnica->codigo),
            'after' => [
                'codigo' => $recepcionTecnica->codigo,
                'estado' => $recepcionTecnica->statusLabel(),
                'persona_entrega' => $recepcionTecnica->persona_nombre,
                'equipo' => $recepcionTecnica->equipmentReference(),
            ],
            'metadata' => [
                'details' => array_filter([
                    'codigo' => $recepcionTecnica->codigo,
                    'ingresado_at' => $recepcionTecnica->ingresado_at?->format('d/m/Y H:i'),
                    'persona_entrega' => $recepcionTecnica->persona_nombre,
                    'procedencia' => $recepcionTecnica->procedenciaResumen(),
                    'falla_motivo' => $recepcionTecnica->falla_motivo,
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
            ],
            'level' => AuditLog::LEVEL_INFO,
        ]);
    }

    private function recordLinkedExistingEquipo(RecepcionTecnica $recepcionTecnica, User $user, ?Equipo $equipo): void
    {
        if (! $equipo instanceof Equipo) {
            return;
        }

        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => 'ingreso_tecnico_equipo_vinculado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf(
                'El ingreso tecnico %s quedo vinculado al equipo %s.',
                $recepcionTecnica->codigo,
                $equipo->reference()
            ),
            'metadata' => [
                'details' => [
                    'codigo' => $recepcionTecnica->codigo,
                    'equipo_id' => $equipo->id,
                    'equipo_codigo_interno' => $equipo->codigo_interno,
                ],
            ],
            'level' => AuditLog::LEVEL_INFO,
        ]);
    }

    private function recordCreatedEquipo(RecepcionTecnica $recepcionTecnica, User $user, ?Equipo $equipo): void
    {
        if (! $equipo instanceof Equipo) {
            return;
        }

        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => 'ingreso_tecnico_equipo_incorporado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf(
                'El equipo %s fue incorporado al sistema desde el ingreso tecnico %s.',
                $equipo->reference(),
                $recepcionTecnica->codigo
            ),
            'metadata' => [
                'details' => [
                    'codigo' => $recepcionTecnica->codigo,
                    'equipo_id' => $equipo->id,
                    'equipo_codigo_interno' => $equipo->codigo_interno,
                ],
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);
    }

    private function recordClosed(RecepcionTecnica $recepcionTecnica, User $user, Mantenimiento $mantenimiento): void
    {
        $before = [
            'estado' => 'Abierto',
        ];

        $after = [
            'estado' => $recepcionTecnica->statusLabel(),
            'condicion_egreso' => $recepcionTecnica->egressConditionLabel(),
            'retiro' => $recepcionTecnica->retiroResumen(),
        ];

        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => 'ingreso_tecnico_cerrado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf(
                'Se cerro el ingreso tecnico %s y se genero el historial tecnico final.',
                $recepcionTecnica->codigo
            ),
            'before' => $before,
            'after' => $after,
            'metadata' => [
                'details' => array_filter([
                    'codigo' => $recepcionTecnica->codigo,
                    'fecha_entrega_real' => $recepcionTecnica->entregada_at?->format('d/m/Y H:i'),
                    'mantenimiento_id' => $mantenimiento->id,
                    'persona_retiro' => $recepcionTecnica->persona_retiro_nombre,
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
                'changes' => $this->auditLogService->diff($before, $after, [
                    'estado' => 'Estado',
                    'condicion_egreso' => 'Condicion de egreso',
                    'retiro' => 'Retiro',
                ]),
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function trackingSnapshot(RecepcionTecnica $recepcionTecnica): array
    {
        return [
            'estado' => $recepcionTecnica->statusLabel(),
            'diagnostico' => $recepcionTecnica->diagnostico ?: 'Sin diagnostico',
            'accion_realizada' => $recepcionTecnica->accion_realizada ?: 'Sin accion',
            'solucion_aplicada' => $recepcionTecnica->solucion_aplicada ?: 'Sin solucion',
            'observaciones_internas' => $recepcionTecnica->observaciones_internas ?: 'Sin observaciones',
        ];
    }

    private function loadFull(RecepcionTecnica $recepcionTecnica): RecepcionTecnica
    {
        return $recepcionTecnica->loadMissing([
            'institution:id,nombre',
            'creator:id,name',
            'recibidoPor:id,name',
            'cerradoPor:id,name',
            'anuladaPor:id,name',
            'procedenciaInstitution:id,nombre',
            'procedenciaService:id,nombre',
            'procedenciaOffice:id,nombre',
            'equipo:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno,oficina_id,tipo_equipo_id',
            'equipo.tipoEquipo:id,nombre',
            'equipo.oficina.service.institution',
            'equipoCreado:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno,oficina_id,tipo_equipo_id',
            'equipoCreado.tipoEquipo:id,nombre',
            'equipoCreado.oficina.service.institution',
            'maintenanceRecord:id,recepcion_tecnica_id,equipo_id,fecha,tipo,titulo,condicion_egreso',
        ]);
    }

    private function quickViewFromReturnUrl(?string $returnTo): ?string
    {
        $sanitized = $this->sanitizeReturnUrl($returnTo);

        if ($sanitized === null) {
            return null;
        }

        $query = parse_url($sanitized, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return RecepcionTecnica::VISTA_ACTIVOS;
        }

        parse_str($query, $parameters);

        return RecepcionTecnica::normalizeQuickView($parameters['vista'] ?? null, RecepcionTecnica::VISTA_ACTIVOS);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function isOpenReceptionUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        if (! in_array($sqlState, ['23000', '23505'], true)) {
            return false;
        }

        return str_contains(strtolower($exception->getMessage()), 'recepciones_tecnicas_equipo_abierto_idx');
    }
}
