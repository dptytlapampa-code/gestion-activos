<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    use Auditable;

    public const TIPO_INTERNO = 'interno';
    public const TIPO_EXTERNO = 'externo';
    public const TIPO_ALTA = 'alta';
    public const TIPO_BAJA = 'baja';
    public const TIPO_OTRO = 'otro';

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

    public function estadoResultante(): BelongsTo
    {
        return $this->belongsTo(EquipoStatus::class, 'estado_resultante_id');
    }
}
