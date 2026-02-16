<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipo_id',
        'user_id',
        'tipo_movimiento',
        'fecha',
        'institucion_origen_id',
        'servicio_origen_id',
        'oficina_origen_id',
        'institucion_destino_id',
        'servicio_destino_id',
        'oficina_destino_id',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
