<?php

namespace App\Policies;

use App\Models\RecepcionTecnica;
use App\Models\User;
use App\Services\RecepcionTecnicaAuthorizationService;
use Illuminate\Auth\Access\Response;

class RecepcionTecnicaPolicy
{
    public function __construct(private readonly RecepcionTecnicaAuthorizationService $authorizationService) {}

    public function viewAny(User $user): Response
    {
        return $this->authorizationService->viewAny($user);
    }

    public function view(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        return $this->authorizationService->view($user, $recepcionTecnica);
    }

    public function create(User $user): Response
    {
        return $this->authorizationService->create($user);
    }

    public function updateStatus(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        return $this->authorizationService->updateStatus($user, $recepcionTecnica);
    }

    public function incorporate(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        return $this->authorizationService->incorporate($user, $recepcionTecnica);
    }

    public function print(User $user, RecepcionTecnica $recepcionTecnica): Response
    {
        return $this->authorizationService->print($user, $recepcionTecnica);
    }
}
