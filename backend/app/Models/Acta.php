<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Acta extends Model
{
    use Auditable;

    public const TIPO_ENTREGA = 'entrega';
    public const TIPO_PRESTAMO = 'prestamo';
    public const TIPO_TRASLADO = 'traslado';
    public const TIPO_BAJA = 'baja';
    public const TIPO_DEVOLUCION = 'devolucion';
    public const TIPO_MANTENIMIENTO = 'mantenimiento';

    public const STATUS_ACTIVA = 'activa';
    public const STATUS_ANULADA = 'anulada';

    public const TIPOS = [
        self::TIPO_ENTREGA,
        self::TIPO_PRESTAMO,
        self::TIPO_TRASLADO,
        self::TIPO_BAJA,
        self::TIPO_DEVOLUCION,
        self::TIPO_MANTENIMIENTO,
    ];

    public const LABELS = [
        self::TIPO_ENTREGA => 'ENTREGA',
        self::TIPO_PRESTAMO => 'PRESTAMO',
        self::TIPO_TRASLADO => 'TRASLADO',
        self::TIPO_BAJA => 'BAJA',
        self::TIPO_DEVOLUCION => 'DEVOLUCION',
        self::TIPO_MANTENIMIENTO => 'MANTENIMIENTO',
    ];

    protected $fillable = [
        'institution_id',
        'institution_destino_id',
        'service_origen_id',
        'office_origen_id',
        'service_destino_id',
        'office_destino_id',
        'tipo',
        'fecha',
        'receptor_nombre',
        'receptor_dni',
        'receptor_cargo',
        'receptor_dependencia',
        'motivo_baja',
        'evento_payload',
        'observaciones',
        'status',
        'anulada_por',
        'anulada_at',
        'motivo_anulacion',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'anulada_at' => 'datetime',
            'evento_payload' => 'array',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function institucionDestino(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_destino_id');
    }

    public function servicioOrigen(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_origen_id');
    }

    public function oficinaOrigen(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_origen_id');
    }

    public function servicioDestino(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_destino_id');
    }

    public function oficinaDestino(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_destino_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function annulledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'acta_equipo')
            ->withPivot(['cantidad', 'accesorios', 'institucion_origen_id', 'institucion_origen_nombre', 'servicio_origen_id', 'servicio_origen_nombre', 'oficina_origen_id', 'oficina_origen_nombre']);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->latest('fecha');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(EquipoHistorial::class, 'acta_id')->latest('fecha');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function getCodigoAttribute(): string
    {
        return sprintf('ACTA-%s-%06d', $this->fecha?->format('Y') ?? now()->format('Y'), $this->id);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::LABELS[$this->tipo] ?? strtoupper((string) $this->tipo);
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        return $query->when(
            $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
            fn (Builder $builder) => $builder->whereIn('institution_id', $user->accessibleInstitutionIds()->all())
        );
    }

    /**
     * @param array{tipo?:mixed,fecha_desde?:mixed,fecha_hasta?:mixed} $filters
     */
    public function scopeApplyIndexFilters(Builder $query, array $filters): Builder
    {
        $tipo = (string) ($filters['tipo'] ?? '');
        $fechaDesde = (string) ($filters['fecha_desde'] ?? '');
        $fechaHasta = (string) ($filters['fecha_hasta'] ?? '');

        return $query
            ->when(in_array($tipo, self::TIPOS, true), fn (Builder $builder) => $builder->where('tipo', $tipo))
            ->when($fechaDesde !== '', fn (Builder $builder) => $builder->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta !== '', fn (Builder $builder) => $builder->whereDate('fecha', '<=', $fechaHasta));
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
                ->whereRaw(
                    "concat('ACTA-', to_char(fecha, 'YYYY'), '-', lpad(actas.id::text, 6, '0')) ilike ?",
                    [$like]
                )
                ->orWhere('tipo', 'ilike', $like)
                ->orWhere('status', 'ilike', $like)
                ->orWhere('receptor_nombre', 'ilike', $like)
                ->orWhere('receptor_dni', 'ilike', $like)
                ->orWhere('receptor_cargo', 'ilike', $like)
                ->orWhere('receptor_dependencia', 'ilike', $like)
                ->orWhere('motivo_baja', 'ilike', $like)
                ->orWhere('observaciones', 'ilike', $like)
                ->orWhereHas('creator', fn (Builder $creatorQuery) => $creatorQuery->where('name', 'ilike', $like))
                ->orWhereHas('institution', fn (Builder $institutionQuery) => $institutionQuery->where('nombre', 'ilike', $like))
                ->orWhereHas('institucionDestino', fn (Builder $institutionQuery) => $institutionQuery->where('nombre', 'ilike', $like));
        });
    }
}

