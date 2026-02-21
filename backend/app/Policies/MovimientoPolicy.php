<?php

namespace App\Policies;

use App\Models\Movimiento;
use App\Models\User;

class MovimientoPolicy
{
    public function before(User $user): ?bool { return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null; }
    public function view(User $user, Movimiento $movimiento): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && (int) $movimiento->equipo?->oficina?->service?->institution_id === (int) $user->institution_id;
    }

    public function create(User $user, ?int $institutionId = null): bool
    {
        if (! $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO)) {
            return false;
        }

        return $institutionId === null || (int) $institutionId === (int) $user->institution_id;
    }
}
