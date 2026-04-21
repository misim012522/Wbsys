<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantResolved
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[DEBUG-TENANT-REQ] EnsureTenantResolved', [
            'path' => $request->path(),
            'current_tenant_bound' => app()->bound('current_tenant'),
            'current_tenant_id' => app()->bound('current_tenant') ? app('current_tenant')->id : null,
        ]);

        if (app()->bound('current_tenant')) {
            return $next($request);
        }

        $user = $request->user();

        Log::warning('[DEBUG-TENANT-REQ] current_tenant NOT bound, checking user', [
            'path' => $request->path(),
            'user_id' => $user?->id,
            'user_tenant_id' => $user?->tenant_id,
        ]);

        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant) {
                if (! $tenant->is_active) {
                    return $this->logoutDueToDeactivation($request);
                }

                $loginUrl = TenantUrl::login($tenant, true);
                $currentUrl = $request->fullUrl();

                Log::warning('[DEBUG-TENANT-REQ] Redirecting unresolved tenant user to canonical tenant login', [
                    'path' => $request->path(),
                    'login_url' => $loginUrl,
                    'current_url' => $currentUrl,
                ]);

                if (rtrim($loginUrl, '/') === rtrim($currentUrl, '/')) {
                    Log::warning('[DEBUG-TENANT-REQ] Redirect loop detected, clearing session', [
                        'path' => $request->path(),
                        'login_url' => $loginUrl,
                    ]);

                    Auth::logout();
                    $request->session()->forget('tenant_auth');
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->away(TenantUrl::login(null, true))
                        ->with('error', 'Your tenant workspace could not be resolved. Please sign in again from your assigned workspace link.');
                }

                return redirect()->away($loginUrl)
                    ->with('error', 'Please continue inside your assigned tenant workspace.');
            }
        }

        Log::warning('[DEBUG-TENANT-REQ] No tenant resolved, redirecting to central home', [
            'path' => $request->path(),
        ]);

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
