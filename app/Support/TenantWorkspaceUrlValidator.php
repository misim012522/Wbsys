<?php

namespace App\Support;

class TenantWorkspaceUrlValidator
{
    /**
     * @return array<int, string>
     */
    public static function validate(?string $domain = null, ?string $subdomain = null): array
    {
        $domain = self::normalize($domain);
        $subdomain = self::normalize($subdomain);
        $errors = [];

        if ($domain !== null) {
            if (! self::isValidHost($domain)) {
                $errors[] = 'The tenant domain must be a host name only, without http://, https://, paths, or ports.';
            }

            return $errors;
        }

        if ($subdomain === null) {
            $errors[] = 'A tenant subdomain is required when no custom domain is provided.';

            return $errors;
        }

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $subdomain)) {
            $errors[] = 'The tenant subdomain may only contain lowercase letters, numbers, and single hyphens between words.';
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($appHost) || trim($appHost) === '') {
            $errors[] = 'Set APP_URL to the actual application URL before creating tenant workspaces.';

            return $errors;
        }

        $appHost = trim($appHost);

        if (filter_var($appHost, FILTER_VALIDATE_IP) || in_array($appHost, ['localhost', '127.0.0.1'], true)) {
            $localTenantBaseDomain = trim((string) env('LOCAL_TENANT_BASE_DOMAIN', ''));

            if ($localTenantBaseDomain === '') {
                $errors[] = 'Set LOCAL_TENANT_BASE_DOMAIN in .env so tenant workspace links can be generated from local subdomains.';
            } elseif (! self::isValidHost($localTenantBaseDomain) || ! str_contains($localTenantBaseDomain, '.')) {
                $errors[] = 'LOCAL_TENANT_BASE_DOMAIN must be a valid host such as lvh.me so emailed tenant links resolve correctly.';
            }
        }

        return $errors;
    }

    private static function normalize(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private static function isValidHost(string $host): bool
    {
        if (str_contains($host, '://') || str_contains($host, '/') || str_contains($host, '?') || str_contains($host, '#') || str_contains($host, ':')) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || $host === 'localhost';
    }
}
