<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConfigureSessionCookie
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = preg_replace('/:\d+$/', '', (string) ($request->header('host') ?: $request->server('HTTP_HOST') ?: $request->getHost()));
        $host = is_string($host) ? trim($host) : '';
        $baseCookie = (string) config('session.cookie', Str::slug((string) config('app.name', 'laravel')).'-session');
        $tenantBaseDomain = trim((string) env('LOCAL_TENANT_BASE_DOMAIN', 'lvh.me'));

        $isLocalTenant = $host !== '' && $tenantBaseDomain !== '' && str_ends_with($host, '.'.$tenantBaseDomain);
        $isNgrok = $host !== '' && (str_contains($host, 'ngrok-free.app') || str_contains($host, 'ngrok-free.dev'));

        if ($isLocalTenant || $isNgrok) {
            $tenantCookie = $baseCookie.'-'.Str::slug($host);

            // StartSession runs before ResolveTenant in the web stack, so resolve+activate here.
            $tenant = Tenant::resolveFromHost($host, true);
            if ($tenant && $tenant->is_active) {
                app()->instance('current_tenant', $tenant);
                app()->instance('current_tenant_id', $tenant->id);
                $this->tenantDatabaseManager->activate($tenant);
            }

            config([
                'session.cookie' => $tenantCookie,
                // Keep sessions host-only so browsers accept them and each subdomain is isolated.
                'session.domain' => null,
                // Tenant workspace requests must persist sessions in the tenant database.
                'session.connection' => 'tenant',
            ]);
        } elseif (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            config([
                'session.cookie' => $baseCookie,
                'session.domain' => null,
                // Local central host keeps using configured session connection.
                'session.connection' => env('SESSION_CONNECTION') ?: null,
            ]);
        }

        return $next($request);
    }
}
