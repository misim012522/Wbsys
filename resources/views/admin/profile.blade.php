@extends('layouts.app')

@section('title', 'Workspace Info')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Workspace info',
    'description' => 'Review the tenant workspace details that were provided during central registration.',
    'actions' => [
        ['label' => 'Admin settings', 'href' => route('admin.settings.edit')],
    ],
])

<div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
    <div class="space-y-6">
        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-slate-800">Organization profile</h2>
                <p class="mt-2 text-sm text-slate-600">These are the workspace details currently assigned to this tenant.</p>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant name</dt>
                    <dd class="mt-2 text-lg font-semibold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Address</dt>
                    <dd class="mt-2 text-sm text-slate-700">{{ $tenant?->address ?: 'N/A' }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contact number</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->contact_number ?: 'N/A' }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registration email</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->email ?: 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-slate-800">Admin account</h2>
                <p class="mt-2 text-sm text-slate-600">This is the tenant administrator identity created from the registration flow.</p>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin name</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->name }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin username</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->username }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin email</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->email }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin contact</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->phone ?: 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-slate-800">Workspace access</h2>
                <p class="mt-2 text-sm text-slate-600">Use these links when opening this tenant workspace.</p>
            </div>

            <dl class="space-y-4">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace URL</dt>
                    <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $workspaceUrl ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Login URL</dt>
                    <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $loginUrl ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subdomain</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->subdomain ?: 'N/A' }}</dd>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Custom domain</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->domain ?: 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-slate-800">Subscription</h2>
                <p class="mt-2 text-sm text-slate-600">Current tenant plan details from the central app.</p>
            </div>

            <dl class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current plan</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->plan?->name ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</dt>
                    <dd class="mt-2 text-sm font-medium capitalize text-slate-800">{{ $subscription?->status ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Started</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $subscription?->starts_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ends</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $subscription?->ends_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
