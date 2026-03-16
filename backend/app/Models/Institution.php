<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        return $query->when(
            $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
            fn (Builder $builder) => $builder->where('id', $user->institution_id)
        );
    }

    public function scopeSearchIndex(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = "%{$search}%";

        return $query->where(function (Builder $builder) use ($like): void {
            $builder
                ->where('codigo', 'ilike', $like)
                ->orWhere('nombre', 'ilike', $like)
                ->orWhere('descripcion', 'ilike', $like)
                ->orWhere('provincia', 'ilike', $like)
                ->orWhere('localidad', 'ilike', $like)
                ->orWhere('direccion', 'ilike', $like)
                ->orWhere('telefono', 'ilike', $like)
                ->orWhere('email', 'ilike', $like)
                ->orWhere('responsable', 'ilike', $like)
                ->orWhere('tipo', 'ilike', $like)
                ->orWhere('estado', 'ilike', $like);
        });
    }
}
