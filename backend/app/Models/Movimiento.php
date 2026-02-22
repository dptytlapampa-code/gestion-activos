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

    public const TIPO_MANTENIMIENTO = 'mantenimiento';
    public const TIPO_PRESTAMO = 'prestamo';
    public const TIPO_BAJA = 'baja';
    public const TIPO_TRASLADO = 'traslado';
    public const TIPO_TRANSFERENCIA_INTERNA = 'transferencia_interna';
    public const TIPO_TRANSFERENCIA_EXTERNA = 'transferencia_externa';
    public const TIPO_DEVOLUCION = 'devolucion';

    public const TIPOS = [
        self::TIPO_MANTENIMIENTO,
        self::TIPO_PRESTAMO,
        self::TIPO_BAJA,
        self::TIPO_TRASLADO,
        self::TIPO_TRANSFERENCIA_INTERNA,
        self::TIPO_TRANSFERENCIA_EXTERNA,
        self::TIPO_DEVOLUCION,
    ];

    protected $fillable = [
        'equipo_id', 'user_id', 'tipo_movimiento', 'fecha',
        'institucion_origen_id', 'servicio_origen_id', 'oficina_origen_id',
        'institucion_destino_id', 'servicio_destino_id', 'oficina_destino_id',
        'receptor_nombre', 'receptor_dni', 'receptor_cargo', 'fecha_inicio_prestamo', 'fecha_estimada_devolucion',
        'fecha_devolucion_real',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'fecha_inicio_prestamo' => 'date',
            'fecha_estimada_devolucion' => 'date',
            'fecha_devolucion_real' => 'datetime',
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

    public function esTransferencia(): bool
    {
        return in_array($this->tipo_movimiento, [
            self::TIPO_TRANSFERENCIA_INTERNA,
            self::TIPO_TRANSFERENCIA_EXTERNA,
            self::TIPO_TRASLADO,
        ], true);
    }

    public function esPrestamo(): bool
    {
        return $this->tipo_movimiento === self::TIPO_PRESTAMO;
    }

    public function esDevolucion(): bool
    {
        return $this->tipo_movimiento === self::TIPO_DEVOLUCION;
    }
}
