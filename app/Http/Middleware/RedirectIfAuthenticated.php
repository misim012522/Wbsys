<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (! Auth::guard($guard)->check()) {
                continue;
            }

            $user = Auth::guard($guard)->user();
            $host = Tenant::normalizeHost((string) ($request->server('HTTP_HOST') ?: $request->getHost()));
            $tenantOnHost = $host === '' ? null : Tenant::resolveFromHost($host);

            if ($tenantOnHost && $user->isCentralUser()) {
                return redirect()->route('tenant.home');
            }

            if ($tenantOnHost) {
                $dashboardUrl = TenantUrl::forUserDashboard($user);

                return redirect()->to($dashboardUrl, 302);
            }

            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        return $next($request);
    }
}
