<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
    ];

    protected static function booted(): void
    {
        static::updating(function (Institution $institution): void {
            if ($institution->getOriginal('codigo') !== null && $institution->isDirty('codigo')) {
                $institution->codigo = $institution->getOriginal('codigo');
            }
        });
    }
}
