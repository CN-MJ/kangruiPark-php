<?php

namespace App\Http\Middleware;

use App\Services\AdminAuditLogger;
use App\Services\AdminMutationAuditor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditSuperParkMutation
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
        private readonly AdminMutationAuditor $mutationAuditor,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('via_super')) {
            return $next($request);
        }

        $method = strtoupper($request->method());
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        if ($this->mutationAuditor->shouldSkip($request)) {
            return $next($request);
        }

        $captured = $this->mutationAuditor->captureBefore($request);
        $response = $next($request);

        $status = $response->getStatusCode();
        if ($status >= 400) {
            return $response;
        }

        $user = $request->attributes->get('super_user') ?: $request->attributes->get('park_user');
        $built = $captured
            ? $this->mutationAuditor->buildLog($request, $response, $captured)
            : $this->mutationAuditor->buildFallback($request, $response);

        if (! $built) {
            return $response;
        }

        $this->auditLogger->log(
            $user,
            $built['action'],
            $built['target_type'],
            $built['target_id'],
            $built['payload'],
            $request
        );

        return $response;
    }
}
