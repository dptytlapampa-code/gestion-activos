<?php

namespace App\Models;

use App\Services\ActiveInstitutionContext;
use App\Services\RecepcionTecnicaCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecepcionTecnica extends Model
{
    protected $table = 'recepciones_tecnicas';

    public const CODIGO_PREFIX = 'GA-RT-';
    public const CODIGO_PAD_LENGTH = 9;

    public const ESTADO_RECIBIDO = 'recibido';
    public const ESTADO_EN_REVISION = 'en_revision';
    public const ESTADO_PENDIENTE_REPUESTO = 'pendiente_repuesto';
    public const ESTADO_REPARADO = 'reparado';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_ANULADO = 'anulado';

    public const ESTADOS = [
        self::ESTADO_RECIBIDO,
        self::ESTADO_EN_REVISION,
        self::ESTADO_PENDIENTE_REPUESTO,
        self::ESTADO_REPARADO,
        self::ESTADO_ENTREGADO,
        self::ESTADO_ANULADO,
    ];

    public const LABELS = [
        self::ESTADO_RECIBIDO => 'Recibido',
        self::ESTADO_EN_REVISION => 'En revision',
        self::ESTADO_PENDIENTE_REPUESTO => 'Pendiente de repuesto',
        self::ESTADO_REPARADO => 'Reparado',
        self::ESTADO_ENTREGADO => 'Entregado',
        self::ESTADO_ANULADO => 'Anulado',
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'institution_id',
        'created_by',
        'anulada_por',
        'equipo_id',
        'equipo_creado_id',
        'procedencia_institution_id',
        'procedencia_service_id',
        'procedencia_office_id',
        'fecha_recepcion',
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

    public function resolvedEquipo(): ?Equipo
    {
        return $this->equipoCreado ?? $this->equipo;
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
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

    public function canBeAnnulled(): bool
    {
        return $this->estado !== self::ESTADO_ENTREGADO && $this->estado !== self::ESTADO_ANULADO;
    }

    public function canBeIncorporated(): bool
    {
        return $this->resolvedEquipo() === null && $this->estado !== self::ESTADO_ANULADO;
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
                ->orWhereHas('creator', fn (Builder $creatorQuery) => $creatorQuery->where('name', 'ilike', $like))
                ->orWhereHas('procedenciaInstitution', fn (Builder $institutionQuery) => $institutionQuery->where('nombre', 'ilike', $like));
        });
    }

    public static function formatCodigo(int $sequence): string
    {
        return sprintf('%s%0'.self::CODIGO_PAD_LENGTH.'d', self::CODIGO_PREFIX, $sequence);
    }
}
