<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRbacController extends Controller
{
    public function edit(): View
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;

        $officeStaffPermissions = [
            'office.serve' => (bool) ($tenant?->getSetting('rbac.office_staff.office.serve', true) ?? true),
            'reports.view' => (bool) ($tenant?->getSetting('rbac.office_staff.reports.view', true) ?? true),
        ];

        return view('admin.rbac', compact('tenant', 'officeStaffPermissions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;

        abort_unless($tenant, 404);

        $tenant->setSetting('rbac.office_staff.office.serve', $request->boolean('office_staff_office_serve'));
        $tenant->setSetting('rbac.office_staff.reports.view', $request->boolean('office_staff_reports_view'));

        return redirect()
            ->route('admin.rbac.edit')
            ->with('success', 'Office staff access has been updated.');
    }
}
