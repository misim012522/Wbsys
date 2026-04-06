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

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Administrator</p>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ $admin?->name ?? 'N/A' }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ $admin?->email ?? 'No email assigned' }}</p>
        <div class="mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
            {{ str_replace('_', ' ', $admin?->role ?? 'tenant_admin') }}
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-emerald-200 bg-[linear-gradient(135deg,_#ffffff_0%,_#f1fcf6_55%,_#ecfdf5_100%)] p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Workspace</p>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $workspaceHost }}</p>
        <div class="mt-4 inline-flex rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
            {{ $workspaceStatus }}
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-sky-200 bg-[linear-gradient(135deg,_#ffffff_0%,_#f3faff_55%,_#eff6ff_100%)] p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Subscription</p>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ $tenant?->plan?->name ?? 'N/A' }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $subscriptionStatus }}</p>
        <div class="mt-4 inline-flex rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
            {{ $subscription?->ends_at?->format('M j, Y') ? 'Ends '.$subscription->ends_at->format('M j, Y') : 'No end date set' }}
        </div>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.14),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fffc_45%,_#eef6ff_100%)] p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Overview</p>
                <h2 class="mt-3 text-2xl font-bold text-slate-900">Workspace administration</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">A quick control surface for your tenant workspace, access configuration, and admin identity.</p>
            </div>

            <div class="grid gap-4 p-6 md:grid-cols-2">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 14a4 4 0 10-8 0m8 0H8m8 0v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2m8 0a4 4 0 00-8 0" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Administrator</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $admin?->name ?? 'N/A' }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $admin?->email ?? 'No email assigned' }}</p>
                            <p class="mt-2 inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">Username: {{ $admin?->username ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-sky-700 shadow-sm ring-1 ring-slate-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5l9-4 9 4m-18 0v9l9 4 9-4v-9m-18 0l9 4 9-4" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</p>
                            <p class="mt-1 text-sm text-slate-600">Subdomain: {{ $tenant?->subdomain ?? 'N/A' }}</p>
                            <p class="mt-1 text-sm text-slate-600">Plan: {{ $tenant?->plan?->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin focus</p>
                    <h3 class="mt-3 text-base font-semibold text-slate-900">Run the workspace from one place</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Manage staff access, roles, customization, and tenant workflow settings without jumping across too many pages.</p>
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Security</p>
                    <h3 class="mt-3 text-base font-semibold text-slate-900">Protect the admin account</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Keep administrator credentials updated and private so only authorized users can control tenant settings.</p>
                </div>
            </div>
        </section>

    </div>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/80 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Identity</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Profile settings</h2>
                <p class="mt-2 text-sm text-slate-500">Administrator account details at a glance.</p>
            </div>

            <div class="p-6">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Name</dt>
                        <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800">{{ $admin?->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</dt>
                        <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800">{{ $admin?->phone ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</dt>
                        <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800">{{ $admin?->email ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Role</dt>
                        <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium capitalize text-slate-800">{{ str_replace('_', ' ', $admin?->role ?? 'tenant_admin') }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.10),_transparent_28%),linear-gradient(180deg,_#ffffff_0%,_#f8fbff_100%)] p-6" id="workspace-info">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Reference</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">Workspace info</h2>
                        <p class="mt-2 text-sm text-slate-500">Registered tenant details, access links, and subscription info.</p>
                    </div>
                    <div class="hidden rounded-2xl bg-white/80 px-4 py-3 text-right shadow-sm ring-1 ring-slate-200 sm:block">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Host</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $workspaceHost }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6 p-6">
                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Organization</h3>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">Tenant record</span>
                    </div>
                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant name</dt>
                            <dd class="mt-2 text-lg font-semibold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Address</dt>
                            <dd class="mt-2 text-sm text-slate-700">{{ $tenant?->address ?: 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contact number</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->contact_number ?: 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registration email</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->email ?: 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Access</h3>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">Shareable links</span>
                    </div>
                    <dl class="space-y-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace URL</dt>
                            <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $workspaceUrl ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Login URL</dt>
                            <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $loginUrl ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subdomain</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->subdomain ?: 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Custom domain</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->domain ?: 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Subscription</h3>
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">{{ $tenant?->plan?->name ?? 'N/A' }}</span>
                    </div>
                    <dl class="space-y-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current plan</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->plan?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</dt>
                            <dd class="mt-2 text-sm font-medium capitalize text-slate-800">{{ $subscription?->status ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Started</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $subscription?->starts_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ends</dt>
                            <dd class="mt-2 text-sm font-medium text-slate-800">{{ $subscription?->ends_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/80 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Security</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Change password</h2>
                <p class="mt-2 text-sm text-slate-500">Update profile security separately from workspace settings.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-5">
                    <div class="grid gap-5">
                        <div>
                            <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
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
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
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
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                >
                            </div>
                        </div>
                    </div>
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
