<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\RecepcionTecnica;
use App\Models\User;
use App\Services\Auditing\AuditLogService;
use App\Support\Listings\ListingState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class RecepcionTecnicaService
{
    public const MODO_EQUIPO_EXISTENTE = 'existente';
    public const MODO_EQUIPO_NUEVO = 'nuevo';

    public const MODO_INCORPORACION_EXISTENTE = 'existente';
    public const MODO_INCORPORACION_NUEVO = 'nuevo';

    public function __construct(
        private readonly ActiveInstitutionContext $activeInstitutionContext,
        private readonly AuditLogService $auditLogService,
        private readonly EquipoRegistrationService $equipoRegistrationService,
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

    /**
     * @param  array<string, mixed>  $filters
     */
    public function buildIndexQuery(?User $user, string $search, array $filters): Builder
    {
        return RecepcionTecnica::query()
            ->with([
                'creator:id,name',
                'institution:id,nombre',
                'procedenciaInstitution:id,nombre',
                'equipo:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
                'equipoCreado:id,tipo,marca,modelo,numero_serie,bien_patrimonial,codigo_interno',
            ])
            ->visibleToUser($user)
            ->searchIndex($search)
            ->applyIndexFilters($filters)
            ->latest('fecha_recepcion')
            ->latest('id');
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
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): RecepcionTecnica
    {
        $institutionId = $this->resolveActiveInstitutionId($user);
        $mode = (string) ($data['modo_equipo'] ?? self::MODO_EQUIPO_NUEVO);

        /** @var RecepcionTecnica $recepcionTecnica */
        $recepcionTecnica = DB::transaction(function () use ($user, $data, $institutionId, $mode): RecepcionTecnica {
            $recepcionTecnica = new RecepcionTecnica($this->basePayload($data, $institutionId, $user));
            $recepcionTecnica->save();

            if ($mode === self::MODO_EQUIPO_EXISTENTE) {
                $equipo = $this->resolveEquipoWithinScope($user, (int) $data['equipo_id']);
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

        return $this->loadFull($recepcionTecnica);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(User $user, RecepcionTecnica $recepcionTecnica, array $data): RecepcionTecnica
    {
        $targetStatus = (string) $data['estado'];

        if ($recepcionTecnica->estado === $targetStatus) {
            throw ValidationException::withMessages([
                'estado' => 'La recepcion tecnica ya se encuentra en el estado seleccionado.',
            ]);
        }

        $this->assertStatusTransitionAllowed($recepcionTecnica, $targetStatus);

        $before = [
            'estado' => $recepcionTecnica->statusLabel(),
        ];

        $recepcionTecnica->estado = $targetStatus;
        $recepcionTecnica->status_changed_at = now();

        if ($targetStatus === RecepcionTecnica::ESTADO_ENTREGADO) {
            $recepcionTecnica->entregada_at = now();
        }

        if ($targetStatus === RecepcionTecnica::ESTADO_ANULADO) {
            $recepcionTecnica->anulada_at = now();
            $recepcionTecnica->anulada_por = $user->id;
            $recepcionTecnica->motivo_anulacion = $this->nullableString($data['motivo_anulacion'] ?? null);
        }

        $recepcionTecnica->save();

        $after = [
            'estado' => $recepcionTecnica->statusLabel(),
        ];

        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => $targetStatus === RecepcionTecnica::ESTADO_ANULADO
                ? 'recepcion_tecnica_anulada'
                : 'recepcion_tecnica_estado_actualizado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => $targetStatus === RecepcionTecnica::ESTADO_ANULADO
                ? sprintf('Se anulo la recepcion tecnica %s.', $recepcionTecnica->codigo)
                : sprintf(
                    'La recepcion tecnica %s cambio a estado %s.',
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
                'changes' => $this->auditLogService->diff($before, $after, ['estado' => 'Estado']),
            ],
            'level' => $targetStatus === RecepcionTecnica::ESTADO_ANULADO ? AuditLog::LEVEL_WARNING : AuditLog::LEVEL_INFO,
            'is_critical' => $targetStatus === RecepcionTecnica::ESTADO_ANULADO,
        ]);

        return $this->loadFull($recepcionTecnica->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function incorporate(User $user, RecepcionTecnica $recepcionTecnica, array $data): RecepcionTecnica
    {
        if (! $recepcionTecnica->canBeIncorporated()) {
            throw ValidationException::withMessages([
                'equipo_id' => 'Esta recepcion tecnica ya tiene un equipo vinculado o incorporado.',
            ]);
        }

        $mode = (string) ($data['modo_incorporacion'] ?? self::MODO_INCORPORACION_NUEVO);

        DB::transaction(function () use ($user, $recepcionTecnica, $data, $mode): void {
            $recepcionTecnica->refresh();

            if (! $recepcionTecnica->canBeIncorporated()) {
                throw ValidationException::withMessages([
                    'equipo_id' => 'Esta recepcion tecnica ya tiene un equipo vinculado o incorporado.',
                ]);
            }

            if ($mode === self::MODO_INCORPORACION_EXISTENTE) {
                $equipo = $this->resolveEquipoWithinScope($user, (int) $data['equipo_id']);
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

        return $this->loadFull($recepcionTecnica->fresh());
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
            'action' => $wasPrinted ? 'recepcion_tecnica_reimpresa' : 'recepcion_tecnica_impresa',
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
            Log::warning('recepcion tecnica qr generation failed', [
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
                RecepcionTecnica::ESTADO_EN_REVISION => 'El equipo se encuentra en revision tecnica.',
                RecepcionTecnica::ESTADO_PENDIENTE_REPUESTO => 'El equipo se encuentra pendiente de repuesto o insumo.',
                RecepcionTecnica::ESTADO_REPARADO => 'La intervencion tecnica finalizo y el equipo figura como reparado.',
                RecepcionTecnica::ESTADO_ENTREGADO => 'El circuito tecnico fue cerrado y el equipo figura como entregado.',
                RecepcionTecnica::ESTADO_ANULADO => 'El comprobante fue anulado y ya no se encuentra activo.',
                default => 'El comprobante se encuentra registrado en Mesa Tecnica.',
            },
        ];
    }

    /**
     * @return array{
     *     recepcionTecnica: RecepcionTecnica,
     *     publicUrl: string,
     *     statusOptions: array<string, string>
     * }
     */
    public function detailData(RecepcionTecnica $recepcionTecnica): array
    {
        $recepcionTecnica = $this->loadFull($recepcionTecnica);

        return [
            'recepcionTecnica' => $recepcionTecnica,
            'publicUrl' => route('mesa-tecnica.recepciones-tecnicas.public.show', ['uuid' => $recepcionTecnica->uuid]),
            'statusOptions' => $this->statusOptions(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return RecepcionTecnica::LABELS;
    }

    public function defaultReceptionDate(): string
    {
        return now()->toDateString();
    }

    private function loadFull(RecepcionTecnica $recepcionTecnica): RecepcionTecnica
    {
        return $recepcionTecnica->loadMissing([
            'institution:id,nombre',
            'creator:id,name',
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
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function basePayload(array $data, int $institutionId, User $user): array
    {
        return [
            'institution_id' => $institutionId,
            'created_by' => $user->id,
            'fecha_recepcion' => $data['fecha_recepcion'],
            'estado' => RecepcionTecnica::ESTADO_RECIBIDO,
            'status_changed_at' => now(),
            'sector_receptor' => $this->nullableString($data['sector_receptor'] ?? null) ?? 'Mesa Tecnica',
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

    private function resolveEquipoWithinScope(User $user, int $equipoId): Equipo
    {
        $equipo = Equipo::query()
            ->visibleToUser($user)
            ->with(['tipoEquipo:id,nombre', 'oficina.service.institution'])
            ->find($equipoId);

        if (! $equipo instanceof Equipo) {
            throw ValidationException::withMessages([
                'equipo_id' => 'No se encontro un equipo valido dentro del alcance actual.',
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

        if ($created) {
            $recepcionTecnica->equipo_creado_id = $equipo->id;
        } else {
            $recepcionTecnica->equipo_id = $equipo->id;
        }
    }

    private function assertStatusTransitionAllowed(RecepcionTecnica $recepcionTecnica, string $targetStatus): void
    {
        if (! in_array($targetStatus, RecepcionTecnica::ESTADOS, true)) {
            throw ValidationException::withMessages([
                'estado' => 'El estado seleccionado no es valido para la recepcion tecnica.',
            ]);
        }

        if ($recepcionTecnica->estado === RecepcionTecnica::ESTADO_ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede modificar una recepcion tecnica que ya fue anulada.',
            ]);
        }

        if ($targetStatus === RecepcionTecnica::ESTADO_ANULADO && ! $recepcionTecnica->canBeAnnulled()) {
            throw ValidationException::withMessages([
                'estado' => 'No se pudo anular la recepcion tecnica porque ya fue marcada como entregada.',
            ]);
        }

        if ($recepcionTecnica->estado === RecepcionTecnica::ESTADO_ENTREGADO) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede modificar una recepcion tecnica que ya fue marcada como entregada.',
            ]);
        }
    }

    private function recordCreated(RecepcionTecnica $recepcionTecnica, User $user): void
    {
        $this->auditLogService->record([
            'user' => $user,
            'institution_id' => $recepcionTecnica->institution_id,
            'module' => 'mesa_tecnica',
            'action' => 'recepcion_tecnica_creada',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf('Se registro la recepcion tecnica %s.', $recepcionTecnica->codigo),
            'after' => [
                'codigo' => $recepcionTecnica->codigo,
                'estado' => $recepcionTecnica->statusLabel(),
                'persona_entrega' => $recepcionTecnica->persona_nombre,
                'equipo' => $recepcionTecnica->equipmentReference(),
            ],
            'metadata' => [
                'details' => array_filter([
                    'codigo' => $recepcionTecnica->codigo,
                    'fecha_recepcion' => $recepcionTecnica->fecha_recepcion?->format('d/m/Y'),
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
            'action' => 'recepcion_tecnica_equipo_vinculado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf(
                'La recepcion tecnica %s quedo vinculada al equipo %s.',
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
            'action' => 'recepcion_tecnica_equipo_incorporado',
            'entity_type' => 'recepcion_tecnica',
            'entity_id' => $recepcionTecnica->id,
            'summary' => sprintf(
                'El equipo %s fue incorporado al sistema desde la recepcion tecnica %s.',
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
}
