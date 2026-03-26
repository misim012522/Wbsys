<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantAccountSettingsController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.settings.edit');
        }

        return view('tenant.settings', [
            'user' => $request->user(),
            'tenant' => app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()->route('admin.settings.edit');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_password' => ['nullable', 'required_with:password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($validated['password']) && ! Hash::check((string) $validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ])->withInput($request->except(['current_password', 'password', 'password_confirmation']));
        }

        $user->update([
            'name' => trim($validated['name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ? trim($validated['phone']) : null,
            'password' => ! empty($validated['password']) ? $validated['password'] : $user->password,
        ]);

        return redirect()
            ->route('tenant.settings.edit')
            ->with('success', 'Your tenant workspace settings have been updated.');
    }
}
