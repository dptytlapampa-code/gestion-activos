<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\User;
use App\Services\ActaAuthorizationService;
use Illuminate\Auth\Access\Response;

class ActaPolicy
{
    public function __construct(private readonly ActaAuthorizationService $authorizationService) {}

    public function before(User $user): ?bool
    {
        return null;
    }

    public function viewAny(User $user): Response
    {
        return $this->authorizationService->viewAny($user);
    }

    public function view(User $user, Acta $acta): Response
    {
        return $this->authorizationService->view($user, $acta);
    }

    public function create(User $user): Response
    {
        return $this->authorizationService->create($user);
    }

    public function anular(User $user, Acta $acta): Response
    {
        return $this->authorizationService->anular($user, $acta);
    }
}
