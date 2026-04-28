<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantDisabledResponse
{
    public static function make(Tenant $tenant, Request $request, int $status = 423): Response
    {
        return response()->view('tenant.disabled', [
            'tenant' => $tenant,
            'workspaceUrl' => TenantUrl::workspace($tenant),
            'centralHomeUrl' => TenantUrl::centralHome(),
            'contactEmail' => $tenant->email,
        ], $status);
    }
}
