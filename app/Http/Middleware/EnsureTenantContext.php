<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Set current tenant from authenticated user (data isolation). */
class EnsureTenantContext
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            Log::info('[DEBUG-TENANT-CTX] No user, passing through', ['path' => $request->path()]);
            return $next($request);
        }

        if (! $user->tenant_id) {
            if (app()->bound('current_tenant')) {
                Log::info('[DEBUG-TENANT-CTX] Central user on tenant host, redirect to central', ['path' => $request->path()]);
                return redirect()->away(TenantUrl::centralDashboard())
                    ->with('error', 'Central accounts can only access the central app.');
            }

            return $next($request);
        }

        $tenant = Tenant::find($user->tenant_id);

        if (! $tenant) {
            Log::info('[DEBUG-TENANT-CTX] Tenant NOT FOUND, redirect to login', ['path' => $request->path(), 'tenant_id' => $user->tenant_id]);
            return redirect()->route('login')
                ->with('error', 'Your tenant workspace could not be found. Please contact support.');
        }

        if (! $tenant->is_active) {
            Log::info('[DEBUG-TENANT-CTX] Tenant not active, logout', ['path' => $request->path()]);
            return $this->logoutDueToDeactivation($request, $tenant);
        }

        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (! $currentTenant || (int) $currentTenant->id !== (int) $tenant->id) {
            $redirectUrl = TenantUrl::dashboard($tenant, $user);
            $currentUrl = $request->fullUrl();
            Log::info('[DEBUG-TENANT-CTX] Tenant mismatch, redirecting', [
                'path' => $request->path(),
                'currentTenant' => $currentTenant?->id,
                'userTenant' => $tenant->id,
                'redirectUrl' => $redirectUrl,
                'currentUrl' => $currentUrl,
            ]);
            // Prevent infinite redirect loop if target is the same as current URL
            if (rtrim($redirectUrl, '/') === rtrim($currentUrl, '/')) {
                Log::warning('[DEBUG-TENANT-CTX] Redirect loop detected, sending to tenant login instead', [
                    'url' => $currentUrl,
                ]);
                return redirect()->away(TenantUrl::login($tenant));
            }

            return redirect()->away($redirectUrl)
                ->with('error', 'Please continue inside your assigned tenant workspace.');
        }

        Log::info('[DEBUG-TENANT-CTX] OK, passing through', ['path' => $request->path()]);
        app()->instance('current_tenant', $tenant);
        app()->instance('current_tenant_id', $tenant->id);
        $this->tenantDatabaseManager->activate($tenant);

        return $next($request);
    }

    private function logoutDueToDeactivation(Request $request, Tenant $tenant): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget('tenant_auth');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(TenantUrl::login(null, true))
            ->with('info', 'Logging out due to deactivation.');
    }
}
