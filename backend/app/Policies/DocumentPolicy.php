<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\Document;
use App\Models\User;
use App\Services\ActiveInstitutionContext;

class DocumentPolicy
{
    public function before(User $user): ?bool
    {
        return null;
    }

    public function view(User $user, Document $document): bool
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)) {
            return false;
        }

        $scopeIds = app(ActiveInstitutionContext::class)->globalAdministrationScopeIds($user);

        if ($scopeIds === null) {
            return true;
        }

        if ($scopeIds === [] || $document->equipoDocumentos()
            ->whereHas('equipo.oficina.service', fn ($query) => $query->whereIn('institution_id', $scopeIds))
            ->doesntExist()) {
            $documentable = $document->documentable;

            $institutionId = $documentable instanceof Acta
                ? $documentable->institution_id
                : ($documentable?->oficina?->service?->institution_id
                    ?? $documentable?->equipo?->oficina?->service?->institution_id);

            return $institutionId !== null
                && in_array((int) $institutionId, $scopeIds, true);
        }

        return true;
    }

    public function create(User $user, int $institutionId): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO)
            && app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope($user, $institutionId);
    }

    public function delete(User $user, Document $document): bool
    {
        if ($document->documentable instanceof Acta) {
            return false;
        }

        return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN) && $this->view($user, $document);
    }
}
