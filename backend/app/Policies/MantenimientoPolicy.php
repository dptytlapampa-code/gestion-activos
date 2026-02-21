<?php

namespace App\Policies;

use App\Models\Mantenimiento;
use App\Models\User;

class MantenimientoPolicy
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

    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && (int) $mantenimiento->institution_id === (int) $user->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO);
    }

    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasRole(User::ROLE_ADMIN)
            && (int) $mantenimiento->institution_id === (int) $user->institution_id;
    }

    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return $this->update($user, $mantenimiento);
    }
}
