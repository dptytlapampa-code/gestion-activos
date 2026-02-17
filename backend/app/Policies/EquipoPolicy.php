<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\User;

class EquipoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(User::ROLE_SUPERADMIN)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER);
    }

    public function view(User $user, Equipo $equipo): bool
    {
        if (! $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)) {
            return false;
        }

        return (int) $equipo->oficina?->service?->institution_id === (int) $user->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO);
    }

    public function update(User $user, Equipo $equipo): bool
    {
        return $user->hasRole(User::ROLE_ADMIN)
            && (int) $equipo->oficina?->service?->institution_id === (int) $user->institution_id;
    }

    public function delete(User $user, Equipo $equipo): bool
    {
        return $user->hasRole(User::ROLE_ADMIN)
            && (int) $equipo->oficina?->service?->institution_id === (int) $user->institution_id;
    }
}
