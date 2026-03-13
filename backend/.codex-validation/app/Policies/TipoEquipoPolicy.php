<?php

namespace App\Policies;

use App\Models\TipoEquipo;
use App\Models\User;

class TipoEquipoPolicy
{
    public function before(User $user): ?bool { return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null; }
    public function viewAny(User $user): bool { return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER); }
    public function view(User $user, TipoEquipo $tipoEquipo): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasRole(User::ROLE_ADMIN); }
    public function update(User $user, TipoEquipo $tipoEquipo): bool { return $user->hasRole(User::ROLE_ADMIN); }
    public function delete(User $user, TipoEquipo $tipoEquipo): bool { return $user->hasRole(User::ROLE_ADMIN); }
}
