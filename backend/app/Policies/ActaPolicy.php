<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class ActaPolicy
{
    public function before(User $user): ?bool
    {
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER);
    }

    public function view(User $user, Acta $acta): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && app(ActiveInstitutionContext::class)->isActiveInstitution($user, (int) $acta->institution_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN);
    }

    public function anular(User $user, Acta $acta): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN)
            && app(ActiveInstitutionContext::class)->isActiveInstitution($user, (int) $acta->institution_id);
    }
}
