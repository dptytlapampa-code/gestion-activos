<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoDocumento extends Model
{
    public const ORIGEN_DIRECTO = 'directo';
    public const ORIGEN_MOVIMIENTO = 'movimiento';
    public const ORIGEN_MANTENIMIENTO = 'mantenimiento';
    public const ORIGEN_ACTA = 'acta';

    public const ORIGENES = [
        self::ORIGEN_DIRECTO,
        self::ORIGEN_MOVIMIENTO,
        self::ORIGEN_MANTENIMIENTO,
        self::ORIGEN_ACTA,
    ];

    protected $table = 'equipo_documentos';

    protected $fillable = [
        'equipo_id',
        'document_id',
        'tipo_documento',
        'origen_tipo',
        'origen_id',
        'nombre_original',
        'file_path',
        'mime_type',
        'observacion',
        'fecha_documento',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getOrigenLabelAttribute(): string
    {
        return match ($this->origen_tipo) {
            self::ORIGEN_MOVIMIENTO => 'Movimiento',
            self::ORIGEN_MANTENIMIENTO => 'Mantenimiento',
            self::ORIGEN_ACTA => 'Acta',
            default => 'Directo',
        };
    }
}
