<?php

namespace App\Policies;

use App\Models\Mantenimiento;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class MantenimientoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER);
    }

    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && app(ActiveInstitutionContext::class)->isActiveInstitution($user, $mantenimiento->institution_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO);
    }

    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN)
            && app(ActiveInstitutionContext::class)->isActiveInstitution($user, $mantenimiento->institution_id);
    }

    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return $this->update($user, $mantenimiento);
    }
}
