<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use Auditable, HasFactory;

    public const TIPOS = [
        'hospital',
        'clinica',
        'centro_salud',
        'otro',
    ];

    public const ESTADOS = [
        'activo',
        'inactivo',
    ];

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'localidad',
        'provincia',
        'direccion',
        'telefono',
        'email',
        'responsable',
        'tipo',
        'estado',
    ];

    protected static function booted(): void
    {
        static::updating(function (Institution $institution): void {
            if ($institution->getOriginal('codigo') !== null && $institution->isDirty('codigo')) {
                $institution->codigo = $institution->getOriginal('codigo');
            }
        });
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
