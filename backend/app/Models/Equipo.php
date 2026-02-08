<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipo extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const ESTADO_OPERATIVO = 'operativo';
    public const ESTADO_EN_REPARACION = 'en_reparacion';
    public const ESTADO_BAJA = 'baja';

    public const ESTADOS = [
        self::ESTADO_OPERATIVO,
        self::ESTADO_EN_REPARACION,
        self::ESTADO_BAJA,
    ];

    protected $fillable = [
        'office_id',
        'tipo_equipo',
        'marca',
        'modelo',
        'numero_serie',
        'bien_patrimonial',
        'descripcion',
        'estado',
        'fecha_ingreso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function getServiceAttribute(): ?Service
    {
        return $this->office?->service;
    }

    public function getInstitutionAttribute(): ?Institution
    {
        return $this->office?->service?->institution;
    }
}
