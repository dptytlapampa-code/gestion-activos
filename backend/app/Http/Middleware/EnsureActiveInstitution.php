<?php

namespace App\Http\Middleware;

use App\Services\ActiveInstitutionContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveInstitution
{
    public function __construct(private readonly ActiveInstitutionContext $activeInstitutionContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->activeInstitutionContext->initializeForRequest($request);

        return $next($request);
    }
}
