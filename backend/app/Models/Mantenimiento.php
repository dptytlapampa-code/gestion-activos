<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Mantenimiento extends Model
{
    use Auditable;

    public const TIPO_INTERNO = 'interno';
    public const TIPO_EXTERNO = 'externo';
    public const TIPO_ALTA = 'alta';
    public const TIPO_BAJA = 'baja';
    public const TIPO_OTRO = 'otro';
    public const TIPOS_CIERRE_EXTERNO = [
        self::TIPO_ALTA,
        self::TIPO_BAJA,
    ];

    public const TIPOS = [
        self::TIPO_INTERNO,
        self::TIPO_EXTERNO,
        self::TIPO_ALTA,
        self::TIPO_BAJA,
        self::TIPO_OTRO,
    ];

    protected $fillable = [
        'equipo_id',
        'institution_id',
        'created_by',
        'fecha',
        'tipo',
        'titulo',
        'detalle',
        'proveedor',
        'fecha_ingreso_st',
        'fecha_egreso_st',
        'dias_en_servicio',
        'mantenimiento_externo_id',
        'estado_resultante_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_ingreso_st' => 'date',
            'fecha_egreso_st' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mantenimientoExterno(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mantenimiento_externo_id');
    }

    public function cierreExterno(): HasOne
    {
        return $this->hasOne(self::class, 'mantenimiento_externo_id');
    }

    public function estadoResultante(): BelongsTo
    {
        return $this->belongsTo(EquipoStatus::class, 'estado_resultante_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function scopeExternos(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_EXTERNO);
    }

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereNull('fecha_egreso_st');
    }

    public function scopeCierresExternos(Builder $query): Builder
    {
        return $query->whereIn('tipo', self::TIPOS_CIERRE_EXTERNO);
    }

    public function isExterno(): bool
    {
        return $this->tipo === self::TIPO_EXTERNO;
    }

    public function isCierreExterno(): bool
    {
        return in_array($this->tipo, self::TIPOS_CIERRE_EXTERNO, true);
    }

    public function isExternoAbierto(): bool
    {
        return $this->isExterno() && $this->fecha_egreso_st === null;
    }

    public function canBeManuallyChanged(): bool
    {
        return in_array($this->tipo, [self::TIPO_INTERNO, self::TIPO_OTRO], true)
            && $this->mantenimiento_externo_id === null;
    }
}
