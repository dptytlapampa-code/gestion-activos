<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class ServicePolicy
{
    public function before(User $user): ?bool { return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null; }
    public function viewAny(User $user): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO); }
    public function view(User $user, Service $service): bool { return app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, $service->institution_id); }
    public function create(User $user): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN); }
    public function update(User $user, Service $service): bool { return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN) && app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, $service->institution_id); }
    public function delete(User $user, Service $service): bool { return $this->update($user, $service); }
}
