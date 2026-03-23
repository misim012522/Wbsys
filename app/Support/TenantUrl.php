<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;

class TenantUrl
{
    public static function centralHome(): string
    {
        return self::forPath(null, '/central');
    }

    public static function centralDashboard(): string
    {
        return self::forPath(null, '/central/dashboard');
    }

    public static function authContinue(?Tenant $tenant): string
    {
        return self::forPath($tenant, '/auth/continue');
    }

    public static function forUserDashboard(User $user): string
    {
        return self::dashboard($user->tenant, $user);
    }

    public static function dashboard(?Tenant $tenant, ?User $user = null): string
    {
        if ($tenant === null) {
            return self::centralDashboard();
        }

        return self::forPath($tenant, '/dashboard');
    }

    public static function login(?Tenant $tenant): string
    {
        return self::forPath($tenant, '/login');
    }

    public static function workspace(?Tenant $tenant): string
    {
        return self::forPath($tenant, '/');
    }

    public static function passwordReset(?Tenant $tenant): string
    {
        return self::forPath($tenant, '/forgot-password');
    }

    public static function forPath(?Tenant $tenant, string $path = '/'): string
    {
        if (! $tenant) {
            return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
        }

        $parts = parse_url((string) config('app.url'));
        $scheme = $parts['scheme'] ?? 'http';
        $host = self::tenantHost($tenant, $parts['host'] ?? 'localhost');
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme.'://'.$host.$port.'/'.ltrim($path, '/');
    }

    private static function tenantHost(Tenant $tenant, string $baseHost): string
    {
        $baseHost = trim($baseHost);
        $localTenantBaseDomain = trim((string) env('LOCAL_TENANT_BASE_DOMAIN', 'lvh.me'));

        if (is_string($tenant->domain) && trim($tenant->domain) !== '') {
            return trim($tenant->domain);
        }

        if ($tenant->subdomain === null || $tenant->subdomain === '') {
            return $baseHost;
        }

        if (filter_var($baseHost, FILTER_VALIDATE_IP) || in_array($baseHost, ['localhost', '127.0.0.1'], true)) {
            return $tenant->subdomain.'.'.$localTenantBaseDomain;
        }

        $segments = explode('.', $baseHost);

        if (count($segments) < 2) {
            return $tenant->subdomain.'.'.$baseHost;
        }

        $segments[0] = $tenant->subdomain;

        return implode('.', $segments);
    }
}
