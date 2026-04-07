<?php

namespace App\Models;

use App\Services\ActiveInstitutionContext;
use App\Services\RecepcionTecnicaCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecepcionTecnica extends Model
{
    protected $table = 'recepciones_tecnicas';

    public const CODIGO_PREFIX = 'GA-RT-';
    public const CODIGO_PAD_LENGTH = 9;

    public const ESTADO_RECIBIDO = 'recibido';
    public const ESTADO_EN_DIAGNOSTICO = 'en_diagnostico';
    public const ESTADO_EN_REPARACION = 'en_reparacion';
    public const ESTADO_EN_ESPERA_REPUESTO = 'en_espera_repuesto';
    public const ESTADO_LISTO_PARA_ENTREGAR = 'listo_para_entregar';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_NO_REPARABLE = 'no_reparable';
    public const ESTADO_CANCELADO = 'cancelado';

    public const ESTADOS = [
        self::ESTADO_RECIBIDO,
        self::ESTADO_EN_DIAGNOSTICO,
        self::ESTADO_EN_REPARACION,
        self::ESTADO_EN_ESPERA_REPUESTO,
        self::ESTADO_LISTO_PARA_ENTREGAR,
        self::ESTADO_ENTREGADO,
        self::ESTADO_NO_REPARABLE,
        self::ESTADO_CANCELADO,
    ];

    public const ESTADOS_ABIERTOS = [
        self::ESTADO_RECIBIDO,
        self::ESTADO_EN_DIAGNOSTICO,
        self::ESTADO_EN_REPARACION,
        self::ESTADO_EN_ESPERA_REPUESTO,
        self::ESTADO_LISTO_PARA_ENTREGAR,
    ];

    public const ESTADOS_DE_CIERRE = [
        self::ESTADO_ENTREGADO,
        self::ESTADO_NO_REPARABLE,
    ];

    public const ESTADOS_DE_HISTORIAL = [
        self::ESTADO_ENTREGADO,
        self::ESTADO_NO_REPARABLE,
        self::ESTADO_CANCELADO,
    ];

    public const ESTADOS_DE_SEGUIMIENTO = [
        self::ESTADO_RECIBIDO,
        self::ESTADO_EN_DIAGNOSTICO,
        self::ESTADO_EN_REPARACION,
        self::ESTADO_EN_ESPERA_REPUESTO,
        self::ESTADO_LISTO_PARA_ENTREGAR,
        self::ESTADO_CANCELADO,
    ];

    public const LABELS = [
        self::ESTADO_RECIBIDO => 'Recibido',
        self::ESTADO_EN_DIAGNOSTICO => 'En diagnostico',
        self::ESTADO_EN_REPARACION => 'En reparacion',
        self::ESTADO_EN_ESPERA_REPUESTO => 'En espera de repuesto',
        self::ESTADO_LISTO_PARA_ENTREGAR => 'Listo para entregar',
        self::ESTADO_ENTREGADO => 'Entregado',
        self::ESTADO_NO_REPARABLE => 'No reparable',
        self::ESTADO_CANCELADO => 'Cancelado',
    ];

    public const CONDICION_REPARADO = 'reparado';
    public const CONDICION_DEVUELTO_SIN_REPARAR = 'devuelto_sin_reparar';
    public const CONDICION_NO_REPARABLE = 'no_reparable';

    public const CONDICIONES_EGRESO = [
        self::CONDICION_REPARADO,
        self::CONDICION_DEVUELTO_SIN_REPARAR,
        self::CONDICION_NO_REPARABLE,
    ];

    public const CONDICION_LABELS = [
        self::CONDICION_REPARADO => 'Reparado',
        self::CONDICION_DEVUELTO_SIN_REPARAR => 'Devuelto sin reparar',
        self::CONDICION_NO_REPARABLE => 'No reparable',
    ];

    public const VISTA_ACTIVOS = 'activos';
    public const VISTA_LISTOS = 'listos';
    public const VISTA_CERRADOS = 'cerrados';
    public const VISTA_TODOS = 'todos';

    public const VISTA_LABELS = [
        self::VISTA_ACTIVOS => 'Activos',
        self::VISTA_LISTOS => 'Listos para entregar',
        self::VISTA_CERRADOS => 'Cerrados',
        self::VISTA_TODOS => 'Todos',
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'institution_id',
        'created_by',
        'recibido_por',
        'cerrado_por',
        'anulada_por',
        'equipo_id',
        'equipo_creado_id',
        'procedencia_institution_id',
        'procedencia_service_id',
        'procedencia_office_id',
        'fecha_recepcion',
        'ingresado_at',
        'estado',
        'sector_receptor',
        'referencia_equipo',
        'tipo_equipo_texto',
        'marca',
        'modelo',
        'numero_serie',
        'bien_patrimonial',
        'codigo_interno_equipo',
        'procedencia_hospital',
        'procedencia_libre',
        'persona_nombre',
        'persona_documento',
        'persona_telefono',
        'persona_area',
        'persona_institucion',
        'persona_relacion_equipo',
        'falla_motivo',
        'descripcion_falla',
        'accesorios_entregados',
        'estado_fisico_inicial',
        'observaciones_recepcion',
        'observaciones_internas',
        'diagnostico',
        'accion_realizada',
        'solucion_aplicada',
        'informe_tecnico',
        'observaciones_cierre',
        'persona_retiro_nombre',
        'persona_retiro_documento',
        'persona_retiro_cargo',
        'condicion_egreso',
        'motivo_anulacion',
        'print_count',
        'printed_at',
        'last_printed_at',
        'status_changed_at',
        'entregada_at',
        'anulada_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (RecepcionTecnica $recepcionTecnica): void {
            if (Schema::hasColumn($recepcionTecnica->getTable(), 'uuid') && empty($recepcionTecnica->uuid)) {
                $recepcionTecnica->uuid = (string) Str::uuid();
            }

            if (
                Schema::hasColumn($recepcionTecnica->getTable(), 'codigo')
                && Schema::hasTable('internal_code_sequences')
                && empty($recepcionTecnica->codigo)
            ) {
                $recepcionTecnica->codigo = app(RecepcionTecnicaCodeService::class)->next();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'date',
            'ingresado_at' => 'datetime',
            'printed_at' => 'datetime',
            'last_printed_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'entregada_at' => 'datetime',
            'anulada_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function equipoCreado(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_creado_id');
    }

    public function procedenciaInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'procedencia_institution_id');
    }

    public function procedenciaService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'procedencia_service_id');
    }

    public function procedenciaOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'procedencia_office_id');
    }

    public function maintenanceRecord(): HasOne
    {
        return $this->hasOne(Mantenimiento::class, 'recepcion_tecnica_id');
    }

    public function resolvedEquipo(): ?Equipo
    {
        return $this->equipoCreado ?? $this->equipo;
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public function egressConditionLabel(): string
    {
        return self::CONDICION_LABELS[$this->condicion_egreso] ?? ucfirst(str_replace('_', ' ', (string) $this->condicion_egreso));
    }

    public function equipmentReference(): string
    {
        if ($this->resolvedEquipo() instanceof Equipo) {
            return $this->resolvedEquipo()->reference();
        }

        $summary = trim(collect([
            $this->tipo_equipo_texto ?: $this->referencia_equipo,
            $this->marca,
            $this->modelo,
        ])->filter()->implode(' '));

        if ($summary !== '') {
            return $summary;
        }

        return $this->codigo_interno_equipo
            ?: $this->numero_serie
            ?: $this->bien_patrimonial
            ?: 'Equipo sin referencia visible';
    }

    public function procedenciaResumen(): string
    {
        $structured = collect([
            $this->procedenciaInstitution?->nombre,
            $this->procedenciaService?->nombre,
            $this->procedenciaOffice?->nombre,
        ])->filter()->implode(' / ');

        return $structured !== ''
            ? $structured
            : ($this->procedencia_hospital ?: ($this->procedencia_libre ?: 'Sin procedencia informada'));
    }

    public function receptorResumen(): string
    {
        $parts = collect([
            $this->persona_nombre,
            $this->persona_relacion_equipo ? '('.$this->persona_relacion_equipo.')' : null,
        ])->filter()->implode(' ');

        return $parts !== '' ? $parts : 'Sin persona informada';
    }

    public function retiroResumen(): string
    {
        $parts = collect([
            $this->persona_retiro_nombre,
            $this->persona_retiro_cargo ? '('.$this->persona_retiro_cargo.')' : null,
        ])->filter()->implode(' ');

        return $parts !== '' ? $parts : 'Sin retiro registrado';
    }

    public function isOpen(): bool
    {
        return in_array($this->estado, self::ESTADOS_ABIERTOS, true);
    }

    public function isClosed(): bool
    {
        return in_array($this->estado, self::ESTADOS_DE_CIERRE, true);
    }

    public function isCancelled(): bool
    {
        return $this->estado === self::ESTADO_CANCELADO;
    }

    public function isReadyForDelivery(): bool
    {
        return $this->estado === self::ESTADO_LISTO_PARA_ENTREGAR;
    }

    public function canBeAnnulled(): bool
    {
        return ! $this->isClosed() && ! $this->isCancelled();
    }

    public function canBeClosed(): bool
    {
        return $this->isOpen() && ! $this->isCancelled();
    }

    public function canBeIncorporated(): bool
    {
        return $this->resolvedEquipo() === null && ! $this->isCancelled();
    }

    public function nextActionLabel(): string
    {
        if ($this->canBeIncorporated()) {
            return 'Vincular equipo';
        }

        return match ($this->estado) {
            self::ESTADO_RECIBIDO => 'Registrar diagnostico',
            self::ESTADO_EN_DIAGNOSTICO => 'Actualizar diagnostico',
            self::ESTADO_EN_REPARACION => 'Registrar reparacion',
            self::ESTADO_EN_ESPERA_REPUESTO => 'Actualizar seguimiento',
            self::ESTADO_LISTO_PARA_ENTREGAR => 'Entregar y cerrar',
            self::ESTADO_ENTREGADO, self::ESTADO_NO_REPARABLE => 'Ver historial tecnico',
            self::ESTADO_CANCELADO => 'Revisar ticket cancelado',
            default => 'Ver ticket',
        };
    }

    public function nextActionDescription(): string
    {
        if ($this->canBeIncorporated()) {
            return 'Necesita vincular o incorporar el equipo antes de poder cerrar el ticket.';
        }

        return match ($this->estado) {
            self::ESTADO_RECIBIDO => 'Todavia falta dejar el diagnostico inicial y orientar el trabajo tecnico.',
            self::ESTADO_EN_DIAGNOSTICO => 'El tecnico debe dejar avance claro para que el resto del equipo pueda continuar.',
            self::ESTADO_EN_REPARACION => 'Conviene registrar la reparacion realizada y el resultado tecnico actual.',
            self::ESTADO_EN_ESPERA_REPUESTO => 'Deje el motivo de espera y cualquier novedad operativa para evitar recovecos.',
            self::ESTADO_LISTO_PARA_ENTREGAR => 'El trabajo ya termino: conviene completar el egreso y retirarlo de la cola activa.',
            self::ESTADO_ENTREGADO => 'El ticket ya fue cerrado y consolido su historial tecnico final.',
            self::ESTADO_NO_REPARABLE => 'El ticket ya fue cerrado como no reparable y quedo trazado en mantenimiento.',
            self::ESTADO_CANCELADO => 'El ticket fue cancelado. El motivo queda registrado en la trazabilidad.',
            default => 'Revise el ticket para continuar con la operacion.',
        };
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        $scopeIds = app(ActiveInstitutionContext::class)->globalAdministrationScopeIds($user);

        if ($scopeIds === null) {
            return $query;
        }

        if ($scopeIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('institution_id', $scopeIds);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('estado', self::ESTADOS_ABIERTOS);
    }

    public function scopeHistory(Builder $query): Builder
    {
        return $query->whereIn('estado', self::ESTADOS_DE_HISTORIAL);
    }

    public function scopeForResolvedEquipment(Builder $query, int $equipoId): Builder
    {
        return $query->where(function (Builder $builder) use ($equipoId): void {
            $builder
                ->where('equipo_id', $equipoId)
                ->orWhere('equipo_creado_id', $equipoId);
        });
    }

    public function scopeApplyQuickView(Builder $query, string $view): Builder
    {
        return match (self::normalizeQuickView($view)) {
            self::VISTA_LISTOS => $query->where('estado', self::ESTADO_LISTO_PARA_ENTREGAR),
            self::VISTA_CERRADOS => $query->history(),
            self::VISTA_TODOS => $query,
            default => $query->open(),
        };
    }

    public function scopeOperationalOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw(
                'case estado
                    when ? then 0
                    when ? then 1
                    when ? then 2
                    when ? then 3
                    when ? then 4
                    when ? then 5
                    when ? then 6
                    when ? then 7
                    else 99
                end',
                [
                    self::ESTADO_LISTO_PARA_ENTREGAR,
                    self::ESTADO_RECIBIDO,
                    self::ESTADO_EN_DIAGNOSTICO,
                    self::ESTADO_EN_REPARACION,
                    self::ESTADO_EN_ESPERA_REPUESTO,
                    self::ESTADO_ENTREGADO,
                    self::ESTADO_NO_REPARABLE,
                    self::ESTADO_CANCELADO,
                ]
            )
            ->orderByDesc('status_changed_at')
            ->orderByDesc('entregada_at')
            ->orderByDesc('ingresado_at')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeApplyIndexFilters(Builder $query, array $filters): Builder
    {
        $fechaDesde = trim((string) ($filters['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($filters['fecha_hasta'] ?? ''));
        $estado = trim((string) ($filters['estado'] ?? ''));
        $numeroSerie = trim((string) ($filters['numero_serie'] ?? ''));
        $bienPatrimonial = trim((string) ($filters['bien_patrimonial'] ?? ''));
        $marcaModelo = trim((string) ($filters['marca_modelo'] ?? ''));
        $procedencia = trim((string) ($filters['procedencia'] ?? ''));
        $personaEntrega = trim((string) ($filters['persona_entrega'] ?? ''));

        return $query
            ->when($fechaDesde !== '', fn (Builder $builder) => $builder->whereDate('fecha_recepcion', '>=', $fechaDesde))
            ->when($fechaHasta !== '', fn (Builder $builder) => $builder->whereDate('fecha_recepcion', '<=', $fechaHasta))
            ->when(in_array($estado, self::ESTADOS, true), fn (Builder $builder) => $builder->where('estado', $estado))
            ->when($numeroSerie !== '', fn (Builder $builder) => $builder->where('numero_serie', 'ilike', "%{$numeroSerie}%"))
            ->when($bienPatrimonial !== '', fn (Builder $builder) => $builder->where('bien_patrimonial', 'ilike', "%{$bienPatrimonial}%"))
            ->when($marcaModelo !== '', function (Builder $builder) use ($marcaModelo): void {
                $builder->where(function (Builder $inner) use ($marcaModelo): void {
                    $inner
                        ->where('marca', 'ilike', "%{$marcaModelo}%")
                        ->orWhere('modelo', 'ilike', "%{$marcaModelo}%")
                        ->orWhereRaw("concat(coalesce(marca, ''), ' ', coalesce(modelo, '')) ilike ?", ["%{$marcaModelo}%"]);
                });
            })
            ->when($procedencia !== '', function (Builder $builder) use ($procedencia): void {
                $builder->where(function (Builder $inner) use ($procedencia): void {
                    $inner
                        ->where('procedencia_hospital', 'ilike', "%{$procedencia}%")
                        ->orWhere('procedencia_libre', 'ilike', "%{$procedencia}%")
                        ->orWhereHas('procedenciaInstitution', fn (Builder $institutionQuery) => $institutionQuery->where('nombre', 'ilike', "%{$procedencia}%"));
                });
            })
            ->when($personaEntrega !== '', fn (Builder $builder) => $builder->where('persona_nombre', 'ilike', "%{$personaEntrega}%"));
    }

    public function scopeSearchIndex(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = "%{$search}%";

        return $query->where(function (Builder $builder) use ($like): void {
            $builder
                ->where('codigo', 'ilike', $like)
                ->orWhere('estado', 'ilike', $like)
                ->orWhere('referencia_equipo', 'ilike', $like)
                ->orWhere('tipo_equipo_texto', 'ilike', $like)
                ->orWhere('marca', 'ilike', $like)
                ->orWhere('modelo', 'ilike', $like)
                ->orWhere('numero_serie', 'ilike', $like)
                ->orWhere('bien_patrimonial', 'ilike', $like)
                ->orWhere('codigo_interno_equipo', 'ilike', $like)
                ->orWhere('procedencia_hospital', 'ilike', $like)
                ->orWhere('procedencia_libre', 'ilike', $like)
                ->orWhere('persona_nombre', 'ilike', $like)
                ->orWhere('persona_documento', 'ilike', $like)
                ->orWhere('persona_area', 'ilike', $like)
                ->orWhere('falla_motivo', 'ilike', $like)
                ->orWhere('diagnostico', 'ilike', $like)
                ->orWhere('persona_retiro_nombre', 'ilike', $like)
                ->orWhereHas('creator', fn (Builder $creatorQuery) => $creatorQuery->where('name', 'ilike', $like))
                ->orWhereHas('procedenciaInstitution', fn (Builder $institutionQuery) => $institutionQuery->where('nombre', 'ilike', $like));
        });
    }

    public static function formatCodigo(int $sequence): string
    {
        return sprintf('%s%0'.self::CODIGO_PAD_LENGTH.'d', self::CODIGO_PREFIX, $sequence);
    }

    public static function normalizeQuickView(mixed $view, string $default = self::VISTA_ACTIVOS): string
    {
        $normalized = trim((string) $view);

        return array_key_exists($normalized, self::VISTA_LABELS) ? $normalized : $default;
    }
}
