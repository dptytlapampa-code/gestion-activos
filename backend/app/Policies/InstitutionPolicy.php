<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class InstitutionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null;
    }

    public function viewAny(User $user): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO); }
    public function view(User $user, Institution $institution): bool { return app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, $institution->id); }
    public function create(User $user): bool { return false; }
    public function update(User $user, Institution $institution): bool { return false; }
    public function delete(User $user, Institution $institution): bool { return false; }
}
