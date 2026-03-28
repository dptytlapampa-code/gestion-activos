<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use App\Services\ActiveInstitutionContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Institution extends Model
{
    use Auditable, HasFactory;

    public const TIPO_HOSPITAL = 'hospital';
    public const TIPO_CLINICA = 'clinica';
    public const TIPO_CENTRO_SALUD = 'centro_salud';
    public const TIPO_OTRO = 'otro';

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    public const SCOPE_INSTITUTIONAL = 'institutional';
    public const SCOPE_GLOBAL = 'global';

    public const TIPOS = [
        self::TIPO_HOSPITAL,
        self::TIPO_CLINICA,
        self::TIPO_CENTRO_SALUD,
        self::TIPO_OTRO,
    ];

    public const ESTADOS = [
        self::ESTADO_ACTIVO,
        self::ESTADO_INACTIVO,
    ];

    public const SCOPE_TYPES = [
        self::SCOPE_INSTITUTIONAL,
        self::SCOPE_GLOBAL,
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
        'scope_type',
    ];

    protected static function booted(): void
    {
        static::saving(function (Institution $institution): void {
            if ($institution->getOriginal('codigo') !== null && $institution->isDirty('codigo')) {
                $institution->codigo = $institution->getOriginal('codigo');
            }

            if ($institution->exists && $institution->getOriginal('scope_type') === self::SCOPE_GLOBAL) {
                if ($institution->isDirty('nombre')) {
                    $institution->nombre = $institution->getOriginal('nombre');
                }

                if ($institution->isDirty('tipo')) {
                    $institution->tipo = $institution->getOriginal('tipo');
                }

                if ($institution->isDirty('estado')) {
                    $institution->estado = $institution->getOriginal('estado');
                }

                $institution->scope_type = self::SCOPE_GLOBAL;
            }
        });

        static::deleting(function (Institution $institution): void {
            if ($institution->isGlobalScope()) {
                throw new LogicException('La institucion madre Nivel Central no puede eliminarse.');
            }
        });
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function isGlobalScope(): bool
    {
        return $this->scope_type === self::SCOPE_GLOBAL;
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        $scopeIds = app(ActiveInstitutionContext::class)->globalAdministrationScopeIds($user);

        if ($scopeIds === null) {
            return $query;
        }

        if ($scopeIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $scopeIds);
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
