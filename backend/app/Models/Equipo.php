<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Equipo extends Model
{
    use Auditable, HasFactory;

    public const ESTADO_OPERATIVO = 'operativo';
    public const ESTADO_MANTENIMIENTO = 'mantenimiento';
    public const ESTADO_BAJA = 'baja';
    public const ESTADOS = [self::ESTADO_OPERATIVO, self::ESTADO_MANTENIMIENTO, self::ESTADO_BAJA];

    protected $fillable = ['tipo', 'tipo_equipo_id', 'marca', 'modelo', 'numero_serie', 'bien_patrimonial', 'estado', 'fecha_ingreso', 'oficina_id'];

    protected function casts(): array
    {
        return ['fecha_ingreso' => 'date'];
    }

    public function oficina(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'oficina_id');
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderByDesc('fecha');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }
}
