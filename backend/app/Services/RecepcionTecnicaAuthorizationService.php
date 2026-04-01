<?php

namespace App\Services;

use App\Models\RecepcionTecnica;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecepcionTecnicaAuthorizationService
{
    public function __construct(private readonly ActiveInstitutionContext $activeInstitutionContext) {}

    public function viewAny(User $user): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO)) {
            return Response::deny('No tiene permisos para consultar ingresos tecnicos.');
        }

        if ($this->activeInstitutionContext->accessibleInstitutionIds($user)->isEmpty()) {
            return Response::deny('No tiene instituciones habilitadas para operar en Mesa Tecnica.');
        }

        return Response::allow();
    }

    public function view(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO)) {
            return Response::deny('No tiene permisos para consultar ingresos tecnicos.');
        }

        if (! $this->activeInstitutionContext->isWithinGlobalAdministrationScope($user, (int) $recepcionTecnica->institution_id)) {
            return Response::deny('No tiene permisos para acceder a este ingreso tecnico con la institucion activa seleccionada.');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        if (! $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO)) {
            return Response::deny('No tiene permisos para registrar ingresos tecnicos.');
        }

        $activeInstitutionId = $this->activeInstitutionContext->currentId($user);

        if ($activeInstitutionId === null || ! $user->canAccessInstitution($activeInstitutionId)) {
            return Response::deny('Debe seleccionar una institucion activa habilitada para registrar ingresos tecnicos.');
        }

        return Response::allow();
    }

    public function updateStatus(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        $response = $this->view($user, $recepcionTecnica);

        return $response->denied()
            ? Response::deny('No tiene permisos para actualizar el estado de este ingreso tecnico.')
            : Response::allow();
    }

    public function incorporate(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        $response = $this->view($user, $recepcionTecnica);

        return $response->denied()
            ? Response::deny('No tiene permisos para incorporar o vincular equipos en este ingreso tecnico.')
            : Response::allow();
    }

    public function print(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        $response = $this->view($user, $recepcionTecnica);

        return $response->denied()
            ? Response::deny('No tiene permisos para imprimir este ingreso tecnico.')
            : Response::allow();
    }
}
