<?php

namespace App\Policies;

use App\Models\EquipoStatus;
use App\Models\User;

class EquipoStatusPolicy
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

    public function view(User $user, EquipoStatus $equipoStatus): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EquipoStatus $equipoStatus): bool
    {
        return false;
    }

    public function delete(User $user, EquipoStatus $equipoStatus): bool
    {
        return false;
    }
}
