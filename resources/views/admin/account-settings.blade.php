@extends('layouts.app')

@section('title', 'Admin Settings')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Admin settings',
    'description' => 'Administrative actions and profile security for this workspace.',
])

@php
    $workspaceStatus = $tenant?->is_active ? 'Active workspace' : 'Inactive workspace';
    $subscriptionStatus = $subscription?->status ? str($subscription->status)->replace('_', ' ')->title()->toString() : 'No subscription record';
    $workspaceHost = $workspaceUrl ? parse_url($workspaceUrl, PHP_URL_HOST) : ($tenant?->subdomain ? $tenant->subdomain.'.lvh.me' : 'N/A');
@endphp

<div class="mb-4">
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Actionable settings</p>
</div>

<div class="mb-6 grid gap-6 md:grid-cols-3">
    <div class="min-w-0 panel shadow-sm p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Administrator</p>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ $admin?->name ?? 'N/A' }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ $admin?->email ?? 'No email assigned' }}</p>
        <div class="mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
            {{ str_replace('_', ' ', $admin?->role ?? 'tenant_admin') }}
        </div>
    </div>

    <div class="min-w-0 panel shadow-sm p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Workspace</p>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $workspaceHost }}</p>
        <div class="mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
            {{ $workspaceStatus }}
        </div>
    </div>

    <div class="min-w-0 panel shadow-sm p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Subscription</p>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ $tenant?->plan?->name ?? 'N/A' }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $subscriptionStatus }}</p>
        <div class="mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
            {{ $subscription?->ends_at?->format('M j, Y') ? 'Ends '.$subscription->ends_at->format('M j, Y') : 'No end date available' }}
        </div>
    </div>
</div>

<div class="split-page">
    <div class="min-w-0 space-y-6 xl:pr-2">
        <section class="min-w-0 panel shadow-sm p-6">
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Identity</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Profile settings</h2>
                <p class="mt-2 text-sm text-slate-500">Administrator account details at a glance.</p>
            </div>

            <div>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Name</dt>
                        <dd class="mt-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800">{{ $admin?->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</dt>
                        <dd class="mt-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800">{{ $admin?->phone ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</dt>
                        <dd class="mt-1 min-w-0 break-all rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800">{{ $admin?->email ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Role</dt>
                        <dd class="mt-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium capitalize text-slate-800">{{ str_replace('_', ' ', $admin?->role ?? 'tenant_admin') }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="min-w-0 panel shadow-sm p-6">
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Security</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Change password</h2>
                <p class="mt-2 text-sm text-slate-500">Update profile security separately from workspace settings.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <div class="grid gap-5">
                        <div>
                            <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            >
                            @error('current_password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    required
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
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
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-white/50">Back to admin dashboard</a>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Update password</button>
                </div>
            </form>
        </section>

    </div>

    <div class="min-w-0 space-y-6 xl:pr-2">
        <section class="min-w-0 panel shadow-sm p-6">
            <div class="mb-4" id="workspace-info">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Reference</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">Workspace info</h2>
                        <p class="mt-2 text-sm text-slate-500">Registered tenant details and access links.</p>
                    </div>
                    <div class="hidden rounded-lg border border-slate-300 px-3 py-2 text-right sm:block">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Host</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $workspaceHost }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Organization</h3>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">Tenant record</span>
                    </div>
                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-300 px-3 py-2 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant name</dt>
                            <dd class="mt-2 text-lg font-semibold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-300 px-3 py-2 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Address</dt>
                            <dd class="mt-2 text-sm text-slate-700">{{ $tenant?->address ?: 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-300 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contact number</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->contact_number ?: 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-300 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registration email</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->email ?: 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Access</h3>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">Shareable links</span>
                    </div>
                    <dl class="space-y-3">
                        <div class="rounded-lg border border-slate-300 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace URL</dt>
                            <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $workspaceUrl ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-300 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Login URL</dt>
                            <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $loginUrl ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-300 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subdomain</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->subdomain ?: 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-300 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Custom domain</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->domain ?: 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

            </div>
        </section>
    </div>
</div>
@include('admin._workspace-nav-footer')
@endsection
