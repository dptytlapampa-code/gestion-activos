<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null;
    }

    public function manageUsers(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN);
    }
}
