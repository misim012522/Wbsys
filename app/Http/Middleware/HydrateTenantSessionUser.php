<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HydrateTenantSessionUser
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantAuth = $request->session()->get('tenant_auth');

        if (
            $tenant
            && is_array($tenantAuth)
            && (int) ($tenantAuth['tenant_id'] ?? 0) === (int) $tenant->id
            && (int) ($tenantAuth['user_id'] ?? 0) > 0
        ) {
            $currentUser = Auth::user();

            if (! $currentUser || $currentUser->tenant_id === null || (int) $currentUser->tenant_id !== (int) $tenant->id) {
                $this->tenantDatabaseManager->activate($tenant);

                $user = User::on('tenant')->find($tenantAuth['user_id']);

                if ($user && (int) $user->tenant_id === (int) $tenant->id) {
                    $user->setConnection('tenant');
                    Auth::setUser($user);
                }
            }
        }

        return $next($request);
    }
}
