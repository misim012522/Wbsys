<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantResolved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('current_tenant')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant) {
                if (! $tenant->is_active) {
                    return $this->logoutDueToDeactivation($request);
                }

                return redirect()->away(TenantUrl::dashboard($tenant, $user))
                    ->with('error', 'Please open your assigned tenant workspace to continue.');
            }
        }

        return redirect()->away(TenantUrl::centralHome())
            ->with('info', 'Open your tenant workspace link or subdomain to sign in, register, or manage tenant data.');
    }

    private function logoutDueToDeactivation(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget('tenant_auth');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(TenantUrl::login(null, true))
            ->with('info', 'Logging out due to deactivation.');
    }
}
