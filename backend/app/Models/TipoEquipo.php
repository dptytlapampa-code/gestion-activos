<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipo extends Model
{
    use HasFactory;

    protected $table = 'tipos_equipos';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
