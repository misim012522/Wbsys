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

    public static function centralRegister(): string
    {
        return self::forPath(null, '/central/register');
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

        if ($user?->isOfficeStaff()) {
            if ($user->hasPermission('office.serve')) {
                return self::forPath($tenant, '/office');
            }

            if ($user->hasPermission('reports.view')) {
                return self::forPath($tenant, '/office/reports');
            }

            return self::forPath($tenant, '/settings');
        }

        if ($user?->isStudent()) {
            return self::forPath($tenant, '/dashboard');
        }

        return self::forPath($tenant, '/admin');
    }

    public static function login(?Tenant $tenant, bool $forceLogin = false): string
    {
        $url = self::forPath($tenant, '/login');

        if (! $forceLogin) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'force_login=1';
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
            return rtrim(self::configuredBaseUrl(), '/').'/'.ltrim($path, '/');
        }

        $baseUrl = self::baseUrl();
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'http';
        $host = self::tenantHost($tenant, $parts['host'] ?? 'localhost');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$host.$port.'/'.ltrim($path, '/');
    }

    private static function baseUrl(): string
    {
        $configuredUrl = self::configuredBaseUrl();

        if (app()->runningInConsole()) {
            return $configuredUrl;
        }

        if (! app()->bound('request')) {
            return $configuredUrl;
        }

        $request = request();
        $root = $request->getSchemeAndHttpHost();
        $host = $request->getHost();
        $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);

        if (! is_string($root) || $root === '') {
            return $configuredUrl;
        }

        if (in_array($host, ['127.0.0.1', 'localhost'], true) && is_string($configuredHost) && $configuredHost !== '') {
            $configuredParts = parse_url($configuredUrl);
            $scheme = $configuredParts['scheme'] ?? $request->getScheme();
            $port = isset($configuredParts['port']) ? ':'.$configuredParts['port'] : '';

            return $scheme.'://'.$configuredHost.$port;
        }

        return $root;
    }

    private static function configuredBaseUrl(): string
    {
        return (string) config('app.url');
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

        if ($localTenantBaseDomain !== '' && $baseHost === $localTenantBaseDomain) {
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
