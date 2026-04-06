<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConfigureSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = preg_replace('/:\d+$/', '', (string) ($request->header('host') ?: $request->server('HTTP_HOST') ?: $request->getHost()));
        $host = is_string($host) ? trim($host) : '';
        $baseCookie = (string) config('session.cookie', Str::slug((string) config('app.name', 'laravel')).'-session');
        $tenantBaseDomain = trim((string) env('LOCAL_TENANT_BASE_DOMAIN', 'lvh.me'));

        if ($host !== '' && $tenantBaseDomain !== '' && str_ends_with($host, '.'.$tenantBaseDomain)) {
            config([
                'session.cookie' => $baseCookie,
                // Keep local tenant sessions host-only so each subdomain gets an isolated cookie jar.
                'session.domain' => null,
            ]);
        } elseif (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            config([
                'session.cookie' => $baseCookie,
                'session.domain' => null,
            ]);
        }

        return $next($request);
    }
}
