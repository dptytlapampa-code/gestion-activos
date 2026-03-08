<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin_hospital';
    public const ROLE_TECNICO = 'tecnico';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_SUPERADMIN,
        self::ROLE_ADMIN,
        self::ROLE_TECNICO,
        self::ROLE_VIEWER,
    ];

    private const ROLE_ALIASES = [
        'superadmin' => self::ROLE_SUPERADMIN,
        'super_admin' => self::ROLE_SUPERADMIN,
        'super-admin' => self::ROLE_SUPERADMIN,
        'superadministrador' => self::ROLE_SUPERADMIN,
        'super_administrador' => self::ROLE_SUPERADMIN,
        'admin_hospital' => self::ROLE_ADMIN,
        'admin-hospital' => self::ROLE_ADMIN,
        'adminhospital' => self::ROLE_ADMIN,
        'administrador' => self::ROLE_ADMIN,
        'admin' => self::ROLE_ADMIN,
        'admin_hospitalario' => self::ROLE_ADMIN,
        'tecnico' => self::ROLE_TECNICO,
        'tecnico_hospital' => self::ROLE_TECNICO,
        'tecnico-hospital' => self::ROLE_TECNICO,
        'viewer' => self::ROLE_VIEWER,
        'readonly' => self::ROLE_VIEWER,
        'read_only' => self::ROLE_VIEWER,
        'lector' => self::ROLE_VIEWER,
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'institution_id', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function hasRole(string ...$roles): bool
    {
        $currentRole = $this->canonicalRole((string) $this->role);

        foreach ($roles as $role) {
            if ($currentRole === $this->canonicalRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function canonicalRole(string $role): string
    {
        $normalized = Str::of(Str::ascii($role))
            ->lower()
            ->trim()
            ->replace(' ', '_')
            ->toString();

        return self::ROLE_ALIASES[$normalized] ?? $normalized;
    }
}
