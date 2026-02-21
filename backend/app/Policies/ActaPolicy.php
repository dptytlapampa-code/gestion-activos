<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\User;

class ActaPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER);
    }

    public function view(User $user, Acta $acta): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && (int) $user->institution_id === (int) $acta->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}
