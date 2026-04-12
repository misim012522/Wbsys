<?php

namespace App\Http\Controllers;

use App\Services\TenantRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRbacController extends Controller
{
    public function __construct(
        private TenantRbacService $tenantRbacService
    ) {}

    public function edit(): View
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;

        return view('admin.rbac', array_merge(
            $this->tenantRbacService->viewData($tenant),
            [
                'pageMode' => 'tenant',
                'pageTitle' => 'Access Control',
                'pageDescription' => 'Manage access control for this tenant workspace.',
                'saveAction' => route('admin.rbac.update'),
                'saveMethod' => 'PUT',
                'saveButtonLabel' => 'Save access',
            ]
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;

        abort_unless($tenant, 404);

        $this->tenantRbacService->updateFromRequest($tenant, $request->all());

        return redirect()
            ->route('admin.rbac.edit')
            ->with('success', 'Tenant access control has been updated.');
    }
}
