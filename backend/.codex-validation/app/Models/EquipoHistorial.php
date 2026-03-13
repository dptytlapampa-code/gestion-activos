<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoHistorial extends Model
{
    public $timestamps = false;

    protected $table = 'equipo_historial';

    protected $fillable = [
        'equipo_id',
        'usuario_id',
        'tipo_evento',
        'acta_id',
        'estado_anterior',
        'estado_nuevo',
        'institucion_anterior',
        'institucion_nueva',
        'servicio_anterior',
        'servicio_nuevo',
        'oficina_anterior',
        'oficina_nueva',
        'fecha',
        'observaciones',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function acta(): BelongsTo
    {
        return $this->belongsTo(Acta::class);
    }

    public function institucionAnterior(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institucion_anterior');
    }

    public function institucionNueva(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institucion_nueva');
    }

    public function servicioAnterior(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'servicio_anterior');
    }

    public function servicioNuevo(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'servicio_nuevo');
    }

    public function oficinaAnterior(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'oficina_anterior');
    }

    public function oficinaNueva(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'oficina_nueva');
    }
}
