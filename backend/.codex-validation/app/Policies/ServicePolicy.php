<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function before(User $user): ?bool { return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null; }
    public function viewAny(User $user): bool { return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO); }
    public function view(User $user, Service $service): bool { return (int) $service->institution_id === (int) $user->institution_id; }
    public function create(User $user): bool { return $user->hasRole(User::ROLE_ADMIN); }
    public function update(User $user, Service $service): bool { return $user->hasRole(User::ROLE_ADMIN) && (int) $service->institution_id === (int) $user->institution_id; }
    public function delete(User $user, Service $service): bool { return $this->update($user, $service); }
}
