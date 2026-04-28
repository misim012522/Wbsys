<?php

namespace App\Http\Controllers;

use App\Support\TenantUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAppController extends Controller
{
    /** Show a tenant-facing landing page for end users. */
    public function home(): View|RedirectResponse
    {
        if (auth()->check()) {
            if (app()->bound('current_tenant') && auth()->user()->isCentralUser()) {
                return view('tenant.home', [
                    'tenant' => app('current_tenant'),
                ]);
            }

            return redirect()->route('login');
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        return view('tenant.home', [
            'tenant' => $tenant,
        ]);
    }

    /** Redirect a tenant-app tracking lookup to the public tracker page. */
    public function lookupTrack(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_code' => ['required', 'string', 'max:20'],
        ]);

        return redirect()->route('queue.track', [
            'referenceCode' => strtoupper(trim($validated['reference_code'])),
        ]);
    }
}
