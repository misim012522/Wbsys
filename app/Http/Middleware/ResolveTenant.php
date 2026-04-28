<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use App\Support\TenantDisabledResponse;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $host = Tenant::normalizeHost((string) ($request->header('host') ?: $request->server('HTTP_HOST') ?: $request->getHost()));

        if ($host === '') {
            return $next($request);
        }

        if ($redirect = $this->normalizeLegacyLocalhostTenantHost($request, $host)) {
            return $redirect;
        }

        Log::info('[DEBUG-TENANT] Resolving host', ['host' => $host]);
        $tenant = Tenant::resolveFromHost($host, true);
        Log::info('[DEBUG-TENANT] Resolution result', ['found' => !!$tenant, 'tenant_id' => $tenant?->id]);

        if (
            ! $tenant
            && app()->environment('testing')
            && ! str_starts_with($request->path(), 'central')
            && ! in_array($request->path(), ['login', 'auth/continue'], true)
        ) {
            $activeTenants = Tenant::active()->get();

            if ($activeTenants->count() === 1) {
                $tenant = $activeTenants->first();
            }
        }

        if ($tenant) {
            if (! $tenant->is_active) {
                $tenantAuth = $request->session()->get('tenant_auth');
                $authenticatedTenantId = (int) (Auth::user()?->tenant_id ?? 0);
                $sessionTenantId = is_array($tenantAuth) ? (int) ($tenantAuth['tenant_id'] ?? 0) : 0;

                if ($authenticatedTenantId === (int) $tenant->id || $sessionTenantId === (int) $tenant->id) {
                    return $this->logoutDueToDeactivation($request);
                }

                return TenantDisabledResponse::make($tenant, $request);
            }

            app()->instance('current_tenant', $tenant);
            app()->instance('current_tenant_id', $tenant->id);
            $this->tenantDatabaseManager->activate($tenant);
        }

        return $next($request);
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

    private function normalizeLegacyLocalhostTenantHost(Request $request, string $host): ?RedirectResponse
    {
        if (! str_ends_with($host, '.localhost')) {
            return null;
        }

        $subdomain = explode('.', $host)[0] ?? '';

        if ($subdomain === '') {
            return null;
        }

        $tenant = Tenant::query()->where('subdomain', $subdomain)->first();

        if (! $tenant) {
            return null;
        }

        $targetBase = rtrim(TenantUrl::workspace($tenant), '/');
        $path = '/'.ltrim($request->getPathInfo(), '/');
        $query = $request->getQueryString();
        $target = $targetBase.($path === '/' ? '' : $path).($query ? '?'.$query : '');

        return redirect()->away($target, 302);
    }
}
