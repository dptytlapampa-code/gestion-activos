<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN) ? true : null;
    }

    public function view(User $user, Document $document): bool
    {
        $documentable = $document->documentable;

        $institutionId = $documentable instanceof Acta
            ? $documentable->institution_id
            : ($documentable?->oficina?->service?->institution_id
                ?? $documentable?->equipo?->oficina?->service?->institution_id);

        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)
            && (int) $institutionId === (int) $user->institution_id;
    }

    public function create(User $user, int $institutionId): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TECNICO)
            && (int) $institutionId === (int) $user->institution_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) && $this->view($user, $document);
    }
}
