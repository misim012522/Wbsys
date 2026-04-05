<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tamper-free & data isolation: ensure route model belongs to current user's tenant. */
class EnsureResourceBelongsToTenant
{
    public function handle(Request $request, Closure $next, string ...$paramNames): Response
    {
        $user = $request->user();
        if (! $user || ! $user->tenant_id) {
            return $next($request);
        }
        foreach ($paramNames as $param) {
            $resource = $request->route($param);
            if ($resource && is_object($resource) && isset($resource->tenant_id) && (int) $resource->tenant_id !== (int) $user->tenant_id) {
                abort(403, 'This resource does not belong to your organization.');
            }
        }

        return $next($request);
    }
}
