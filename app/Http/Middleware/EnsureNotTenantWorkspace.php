<?php

namespace App\Http\Middleware;

use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotTenantWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (app()->bound('current_tenant')) {
            if ($user && ! $user->isCentralUser()) {
                return redirect()->away(TenantUrl::forUserDashboard($user))
                    ->with('error', 'Tenant accounts cannot access the central app.');
            }

            return redirect()->away(TenantUrl::forPath(app('current_tenant')))
                ->with('error', 'The central app is not available inside a tenant workspace.');
        }

        return $next($request);
    }
}
