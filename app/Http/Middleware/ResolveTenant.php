<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tenancy (domain): resolve tenant from domain/subdomain and set as current tenant. */
class ResolveTenant
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = (string) ($request->server('HTTP_HOST') ?: $request->getHost());
        $host = preg_replace('/:\d+$/', '', $host) ?: $request->getHost();
        $tenant = Tenant::active()->where('domain', $host)->first();
        if (! $tenant && count(explode('.', $host)) >= 2) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::active()->where('subdomain', $subdomain)->first();
        }

        if (
            ! $tenant
            && app()->environment('testing')
            && ! str_starts_with($request->path(), 'central')
            && ! in_array($request->path(), ['login', 'auth/continue', '/'], true)
        ) {
            $activeTenants = Tenant::active()->get();

            if ($activeTenants->count() === 1) {
                $tenant = $activeTenants->first();
            }
        }

        if ($tenant) {
            app()->instance('current_tenant', $tenant);
            app()->instance('current_tenant_id', $tenant->id);
            $this->tenantDatabaseManager->activate($tenant);
        }
        return $next($request);
    }
}
