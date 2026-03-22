<?php

namespace App\Http\Middleware;

use App\Services\Auditing\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignAuditCorrelationId
{
    public function __construct(private readonly AuditContext $auditContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $this->auditContext->initialize($request);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
