<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminAccountSettingsController extends Controller
{
    public function edit(): View
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
        $admin = auth()->user();

        return view('admin.account-settings', compact('tenant', 'admin'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Your password has been updated.');
    }
}
