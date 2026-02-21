<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipoStatus extends Model
{
    public const CODE_OPERATIVA = 'OPERATIVA';
    public const CODE_EN_SERVICIO_TECNICO = 'EN_SERVICIO_TECNICO';
    public const CODE_BAJA = 'BAJA';

    protected $fillable = ['code', 'name', 'color', 'is_terminal'];

    protected function casts(): array
    {
        return ['is_terminal' => 'boolean'];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
