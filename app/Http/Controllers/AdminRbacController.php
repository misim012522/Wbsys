<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRbacController extends Controller
{
    public function edit(): View
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
        $tenantAdminPermissionDefinitions = User::tenantAdminPermissionDefinitions();
        $tenantAdminPermissions = User::tenantAdminPermissionStates($tenant);
        $permissionDefinitions = User::officeStaffPermissionDefinitions();
        $officeStaffPermissions = User::officeStaffPermissionStates($tenant);

        return view('admin.rbac', compact('tenant', 'tenantAdminPermissions', 'tenantAdminPermissionDefinitions', 'officeStaffPermissions', 'permissionDefinitions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;

        abort_unless($tenant, 404);

        foreach (User::tenantAdminPermissionDefinitions() as $definition) {
            if (($definition['setting'] ?? null) === null || ($definition['input'] ?? null) === null) {
                continue;
            }

            $tenant->setSetting($definition['setting'], $request->boolean($definition['input']));
        }

        foreach (User::officeStaffPermissionDefinitions() as $definition) {
            $tenant->setSetting($definition['setting'], $request->boolean($definition['input']));
        }

        return redirect()
            ->route('admin.rbac.edit')
            ->with('success', 'Office staff access has been updated.');
    }
}
