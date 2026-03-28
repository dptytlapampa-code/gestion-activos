<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActaAuthorizationService
{
    public function __construct(private readonly ActiveInstitutionContext $activeInstitutionContext) {}

    public function viewAny(User $user): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)) {
            return Response::deny('No tiene permisos para consultar actas.');
        }

        return Response::allow();
    }

    public function view(User $user, Acta $acta): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER)) {
            return Response::deny('No tiene permisos para consultar actas.');
        }

        if (! $this->activeInstitutionContext->isActiveInstitution($user, (int) $acta->institution_id)) {
            return Response::deny('No tiene permisos para acceder a esta acta con la institucion activa seleccionada.');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO)) {
            return Response::deny('No tiene permisos para generar actas.');
        }

        if ($this->activeInstitutionContext->accessibleInstitutionIds($user)->isEmpty()) {
            return Response::deny('No tiene instituciones habilitadas para generar actas.');
        }

        $activeInstitutionId = $this->activeInstitutionContext->currentId($user);

        if ($activeInstitutionId === null) {
            return Response::deny('Debe seleccionar una institucion activa habilitada para generar actas.');
        }

        if (! $user->canAccessInstitution($activeInstitutionId)) {
            return Response::deny('No tiene permisos para generar actas con la institucion activa seleccionada.');
        }

        return Response::allow();
    }

    public function anular(User $user, Acta $acta): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN)) {
            return Response::deny('No tiene permisos para anular actas.');
        }

        if (! $this->activeInstitutionContext->isActiveInstitution($user, (int) $acta->institution_id)) {
            return Response::deny('No tiene permisos para anular esta acta con la institucion activa seleccionada.');
        }

        return Response::allow();
    }
}
