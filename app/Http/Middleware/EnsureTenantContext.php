<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Set current tenant from authenticated user (data isolation). */
class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->tenant_id) {
            if (! app()->bound('current_tenant_id')) {
                app()->instance('current_tenant_id', $user->tenant_id);
            }
            if (! app()->bound('current_tenant')) {
                app()->instance('current_tenant', $user->tenant);
            }
        }
        return $next($request);
    }
}
