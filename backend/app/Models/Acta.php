<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Acta extends Model
{
    use Auditable;

    public const TIPO_ENTREGA = 'entrega';
    public const TIPO_PRESTAMO = 'prestamo';
    public const TIPO_TRASLADO = 'traslado';
    public const TIPO_BAJA = 'baja';

    public const TIPOS = [
        self::TIPO_ENTREGA,
        self::TIPO_PRESTAMO,
        self::TIPO_TRASLADO,
        self::TIPO_BAJA,
    ];

    protected $fillable = [
        'institution_id',
        'tipo',
        'fecha',
        'receptor_nombre',
        'receptor_dni',
        'receptor_cargo',
        'receptor_dependencia',
        'observaciones',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'acta_equipo')
            ->withPivot(['cantidad', 'accesorios']);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function getCodigoAttribute(): string
    {
        return sprintf('ACTA-%s-%06d', $this->fecha?->format('Y') ?? now()->format('Y'), $this->id);
    }
}
