<?php

namespace App\Policies;

use App\Models\Movimiento;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class MovimientoPolicy
{
    public function before(User $user): ?bool { return null; }
    public function view(User $user, Movimiento $movimiento): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && app(ActiveInstitutionContext::class)->isActiveInstitution($user, $movimiento->equipo?->oficina?->service?->institution_id);
    }

    public function create(User $user, ?int $institutionId = null): bool
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO)) {
            return false;
        }

        return $institutionId === null
            || app(ActiveInstitutionContext::class)->isActiveInstitution($user, $institutionId);
    }
}
