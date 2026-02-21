<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

class OfficePolicy
{
    public function before(User $user): ?bool { return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null; }
    public function viewAny(User $user): bool { return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO); }
    public function view(User $user, Office $office): bool { return (int) $office->service?->institution_id === (int) $user->institution_id; }
    public function create(User $user): bool { return $user->hasRole(User::ROLE_ADMIN); }
    public function update(User $user, Office $office): bool { return $user->hasRole(User::ROLE_ADMIN) && (int) $office->service?->institution_id === (int) $user->institution_id; }
    public function delete(User $user, Office $office): bool { return $this->update($user, $office); }
}
