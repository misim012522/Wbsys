<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use App\Support\TenantDisabledResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/** Tenancy (domain): resolve tenant from domain/subdomain and set as current tenant. */
class ResolveTenant
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantConnection = (new Tenant)->getConnectionName() ?? config('database.default');

        if (! Schema::connection($tenantConnection)->hasTable('tenants')) {
            return $next($request);
        }

        $host = (string) ($request->header('host') ?: $request->server('HTTP_HOST') ?: $request->getHost());
        $host = preg_replace('/:\d+$/', '', $host) ?: $request->getHost();
        $tenant = Tenant::query()->where('domain', $host)->first();
        if (! $tenant && count(explode('.', $host)) >= 2) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::query()->where('subdomain', $subdomain)->first();
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
            if (! $tenant->is_active) {
                return TenantDisabledResponse::make($tenant, $request);
            }

            app()->instance('current_tenant', $tenant);
            app()->instance('current_tenant_id', $tenant->id);
            $this->tenantDatabaseManager->activate($tenant);
        }

        return $next($request);
    }
}
