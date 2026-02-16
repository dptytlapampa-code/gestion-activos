<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipo extends Model
{
    use HasFactory;

    public const ESTADO_OPERATIVO = 'operativo';
    public const ESTADO_MANTENIMIENTO = 'mantenimiento';
    public const ESTADO_BAJA = 'baja';

    public const ESTADOS = [
        self::ESTADO_OPERATIVO,
        self::ESTADO_MANTENIMIENTO,
        self::ESTADO_BAJA,
    ];

    protected $fillable = [
        'tipo',
        'marca',
        'modelo',
        'nro_serie',
        'bien_patrimonial',
        'estado',
        'fecha_ingreso',
        'oficina_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
        ];
    }

    public function oficina(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'oficina_id');
    }
}
