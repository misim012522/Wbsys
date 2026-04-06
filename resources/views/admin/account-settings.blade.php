@extends('layouts.app')

@section('title', 'Admin Settings')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Admin settings',
    'description' => 'Administrative actions and profile security for this workspace.',
])

<div class="mb-4">
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Actionable settings</p>
</div>

<div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
    <div class="space-y-6">
        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Workspace administration</h2>
                <p class="mt-1 text-sm text-slate-500">A quick admin overview for this tenant.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Administrator</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $admin?->name ?? 'N/A' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $admin?->email ?? 'No email assigned' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Username: {{ $admin?->username ?? 'N/A' }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Subdomain: {{ $tenant?->subdomain ?? 'N/A' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Plan: {{ $tenant?->plan?->name ?? 'N/A' }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Admin focus</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Manage staff access, roles, customization, and tenant workflow settings.</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Security</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Keep administrator credentials updated and private.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Related sections</h2>
                <p class="mt-1 text-sm text-slate-500">Other admin pages for this workspace.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <a href="{{ route('admin.profile') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300 hover:bg-white">
                    <p class="text-sm font-semibold text-slate-900">Workspace info</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Registered tenant details, access links, and subscription info.</p>
                </a>

                <a href="{{ route('admin.customization.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300 hover:bg-white">
                    <p class="text-sm font-semibold text-slate-900">Customization</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Branding, labels, dashboard profile, and public workspace behavior.</p>
                </a>

                <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300 hover:bg-white">
                    <p class="text-sm font-semibold text-slate-900">Roles & permissions</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Role-based access for office staff and tenant permissions.</p>
                </a>

                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300 hover:bg-white">
                    <p class="text-sm font-semibold text-slate-900">Office staff accounts</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Approve, organize, archive, and review workspace staff accounts.</p>
                </a>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Profile settings</h2>
                <p class="mt-1 text-sm text-slate-500">Administrator account details.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Name</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-800">{{ $admin?->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-800">{{ $admin?->phone ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-800">{{ $admin?->email ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Role</dt>
                        <dd class="mt-1 text-sm font-medium capitalize text-slate-800">{{ str_replace('_', ' ', $admin?->role ?? 'tenant_admin') }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-slate-900">Change password</h2>
                <p class="mt-1 text-sm text-slate-500">Update profile security separately from workspace settings.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                    <input
                        type="password"
                        name="current_password"
                        id="current_password"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    >
                    @error('current_password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    >
                    @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    >
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Back to admin dashboard</a>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Update password</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
