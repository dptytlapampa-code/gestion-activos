<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Movimiento extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'equipo_id', 'user_id', 'tipo_movimiento', 'fecha',
        'institucion_origen_id', 'servicio_origen_id', 'oficina_origen_id',
        'institucion_destino_id', 'servicio_destino_id', 'oficina_destino_id',
        'receptor_nombre', 'receptor_dni', 'receptor_cargo', 'fecha_inicio_prestamo', 'fecha_estimada_devolucion',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'fecha_inicio_prestamo' => 'date',
            'fecha_estimada_devolucion' => 'date',
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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }
}
