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
            $host = preg_replace('/:\d+$/', '', (string) ($request->server('HTTP_HOST') ?: $request->getHost()));
            $tenantOnHost = null;

            if (is_string($host) && $host !== '') {
                $tenantOnHost = Tenant::active()->where('domain', $host)->first();

                if (! $tenantOnHost && count(explode('.', $host)) >= 2) {
                    $tenantOnHost = Tenant::active()->where('subdomain', explode('.', $host)[0])->first();
                }
            }

            if ($tenantOnHost && $user->isCentralUser()) {
                return redirect()->route('tenant.home');
            }

            if ($tenantOnHost) {
                if ($request->isMethod('GET') && trim($request->path(), '/') === 'login') {
                    return $next($request);
                }

                $dashboardUrl = TenantUrl::forUserDashboard($user);

                return redirect()->to($dashboardUrl, 303);
            }

            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        return $next($request);
    }
}
