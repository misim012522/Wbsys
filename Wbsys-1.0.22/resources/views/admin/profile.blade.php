@extends('layouts.app')

@section('title', 'Workspace Info')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Workspace info',
    'description' => 'Reference details for this tenant workspace.',
    'actions' => [
        ['label' => 'Admin settings', 'href' => route('admin.settings.edit')],
    ],
])

<div class="mb-4">
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Read only</p>
</div>

<div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
    <div class="space-y-6">
        <div class="panel p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Organization</h2>
                <p class="mt-1 text-sm text-slate-500">Registered tenant details.</p>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2">
                <div class="panel border border-slate-200 bg-white/50/30 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant name</dt>
                    <dd class="mt-2 text-lg font-semibold text-slate-900">{{ $tenant?->name ?? 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Address</dt>
                    <dd class="mt-2 text-sm text-slate-700">{{ $tenant?->address ?: 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contact number</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->contact_number ?: 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registration email</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->email ?: 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="panel p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Administrator</h2>
                <p class="mt-1 text-sm text-slate-500">Primary account assigned to this workspace.</p>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2">
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin name</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->name }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin username</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->username }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin email</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->email }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin contact</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $admin->phone ?: 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="space-y-6">
        <div class="panel p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Access</h2>
                <p class="mt-1 text-sm text-slate-500">Links and domain details.</p>
            </div>

            <dl class="space-y-3">
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace URL</dt>
                    <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $workspaceUrl ?? 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Login URL</dt>
                    <dd class="mt-2 break-all text-sm font-medium text-slate-800">{{ $loginUrl ?? 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subdomain</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->subdomain ?: 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Custom domain</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->domain ?: 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="panel p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-slate-900">Subscription</h2>
                <p class="mt-1 text-sm text-slate-500">Current central plan details.</p>
            </div>

            <dl class="space-y-3">
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current plan</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $tenant?->plan?->name ?? 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</dt>
                    <dd class="mt-2 text-sm font-medium capitalize text-slate-800">{{ $subscription?->status ?? 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Started</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $subscription?->starts_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                </div>
                <div class="panel border border-slate-200 bg-white/50/30 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ends</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-800">{{ $subscription?->ends_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@include('admin._workspace-nav-footer')
@endsection
