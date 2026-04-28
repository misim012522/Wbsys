<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = 'login_web_' . sha1('Illuminate\Auth\SessionGuard');

        Log::info('[DEBUG-AUTH-MID] === REQUEST ===', [
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'session_id' => $request->session()->getId(),
            'session_auth_key' => $sessionKey,
            'session_auth_value' => $request->session()->get($sessionKey),
            'tenant_auth' => $request->session()->get('tenant_auth'),
            'auth_check' => Auth::check(),
            'auth_id' => Auth::id(),
            'current_tenant' => app()->bound('current_tenant') ? app('current_tenant')->id : null,
            'all_session_keys' => array_keys($request->session()->all()),
        ]);

        $response = $next($request);

        Log::info('[DEBUG-AUTH-MID] === RESPONSE ===', [
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'is_redirect' => $response->isRedirect(),
            'redirect_to' => $response->isRedirect() ? $response->headers->get('Location') : null,
        ]);

        return $response;
    }
}
