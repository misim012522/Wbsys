<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tenancy (domain): resolve tenant from domain/subdomain and set as current tenant. */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenant = Tenant::active()->where('domain', $host)->first();
        if (! $tenant && count(explode('.', $host)) >= 2) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::active()->where('subdomain', $subdomain)->first();
        }
        if ($tenant) {
            app()->instance('current_tenant', $tenant);
            app()->instance('current_tenant_id', $tenant->id);
        }
        return $next($request);
    }
}
