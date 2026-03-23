<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\Request;
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
            return $next($request);
        }

        if (! $user->tenant_id) {
            if (app()->bound('current_tenant')) {
                return redirect()->away(TenantUrl::centralDashboard())
                    ->with('error', 'Central accounts can only access the central app.');
            }

            return $next($request);
        }

        $tenant = Tenant::find($user->tenant_id);

        if (! $tenant) {
            return redirect()->route('login')
                ->with('error', 'Your tenant workspace could not be found. Please contact support.');
        }

        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (! $currentTenant || (int) $currentTenant->id !== (int) $tenant->id) {
            return redirect()->away(TenantUrl::dashboard($tenant, $user))
                ->with('error', 'Please continue inside your assigned tenant workspace.');
        }

        app()->instance('current_tenant', $tenant);
        app()->instance('current_tenant_id', $tenant->id);
        $this->tenantDatabaseManager->activate($tenant);

        return $next($request);
    }
}
