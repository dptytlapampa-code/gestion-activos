<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class OfficePolicy
{
    public function before(User $user): ?bool { return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null; }
    public function viewAny(User $user): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO); }
    public function view(User $user, Office $office): bool { return app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, $office->service?->institution_id); }
    public function create(User $user): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN); }
    public function update(User $user, Office $office): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN) && app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, $office->service?->institution_id); }
    public function delete(User $user, Office $office): bool { return $this->update($user, $office); }
}
