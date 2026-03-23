<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isCentralUser()) {
            return $next($request);
        }

        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant) {
                return redirect()->away(TenantUrl::dashboard($tenant, $user))
                    ->with('error', 'Tenant accounts can only access their own tenant workspace.');
            }
        }

        return redirect()->route('login')
            ->with('error', 'Sign in with a central account to access the central app dashboard.');
    }
}
