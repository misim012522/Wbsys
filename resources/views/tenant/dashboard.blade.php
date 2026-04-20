@extends('layouts.app')

@section('title', 'Tenant Dashboard')

@section('content')
@php
    $workspaceName = $tenant?->name ?? 'Tenant workspace';
    $workspaceHost = $tenant ? parse_url(\App\Support\TenantUrl::workspace($tenant), PHP_URL_HOST) : null;
    $roleLabel = $user->isAdmin() ? 'Tenant administrator' : ($user->isOfficeStaff() ? 'Office staff' : 'Workspace account');
    $dashboardProfile = \App\Support\TenantDashboardProfile::for($tenant);
    $queueLabel = $tenantTheme['queue_label'] ?? 'Queue';
    $officeLabel = $tenantTheme['office_label'] ?? 'Office';
    $queueEnabled = (bool) ($tenantTheme['guest_queue_enabled'] ?? true);
    $canOpenOfficeDashboard = $user->hasPermission('office.dashboard');
    $canUseOfficeQr = $user->hasPermission('office.qr');
    $canViewOfficeActivity = $user->hasPermission('office.activity.view');
    $canViewOfficeReports = $user->hasPermission('reports.view');
    $supportUnreadCount = \App\Models\SupportThread::unreadCountForTenant($tenant?->id);
@endphp

<div class="panel mb-8 overflow-hidden">
    <div class="panel-section">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Queueing workspace</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $workspaceName }}</h1>
                <p class="mt-3 max-w-2xl text-sm text-slate-600">Workspace dashboard for today's line, bookings, and queue tools.</p>
            </div>

            <div class="flex flex-wrap gap-2">
        @if($user->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Open admin dashboard</a>
            <a href="{{ route('tenant.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Workspace settings</a>
            <a href="{{ route('support.tenant.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Support{{ $supportUnreadCount ? ' ('.$supportUnreadCount.')' : '' }}</a>
            <a href="{{ route('admin.users.pending') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Pending office staff</a>
            @if($queueEnabled)
                <a href="{{ route('admin.reports') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reports</a>
            @endif
        @elseif($user->isOfficeStaff())
            @if($canOpenOfficeDashboard)
                <a href="{{ route('office.dashboard') }}" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Open office dashboard</a>
            @endif
            <a href="{{ route('tenant.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Workspace settings</a>
            <a href="{{ route('support.tenant.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Support{{ $supportUnreadCount ? ' ('.$supportUnreadCount.')' : '' }}</a>
            @if($queueEnabled && $canUseOfficeQr)
                <a href="{{ route('office.qr') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Office QR access</a>
            @endif
            @if($canViewOfficeActivity)
                <a href="{{ route('office.activity') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Activity log</a>
            @endif
        @else
            <a href="{{ route('tenant.home') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Workspace home</a>
            <a href="{{ route('tenant.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Workspace settings</a>
            <a href="{{ route('support.tenant.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Support{{ $supportUnreadCount ? ' ('.$supportUnreadCount.')' : '' }}</a>
        @endif
            </div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <p class="text-sm text-slate-500">{{ $queueLabel }} today</p>
        <p class="mt-3 text-3xl font-bold text-emerald-600">{{ $summary['active_queue'] }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-slate-500">Completed today</p>
        <p class="mt-3 text-3xl font-bold text-slate-800">{{ $summary['completed_today'] }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-slate-500">{{ $user->isAdmin() ? 'Pending office staff' : 'Now serving' }}</p>
        <p class="mt-3 text-2xl font-bold text-slate-800">
            @if($user->isAdmin())
                {{ $summary['pending_staff'] }}
            @elseif($summary['current_serving'])
                #{{ $summary['current_serving']->queue_number }}
            @else
                None
            @endif
        </p>
        @if(! $user->isAdmin() && $summary['current_serving'])
            <p class="mt-1 text-sm text-slate-500">{{ $summary['current_serving']->display_name }}</p>
        @endif
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-[1.3fr_0.9fr]">
    <section class="panel panel-section">
        <h2 class="text-lg font-semibold text-slate-900">Quick access</h2>
        <p class="mt-2 text-sm text-slate-500">Open the page you need.</p>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @if($user->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 hover:bg-emerald-100">
                    <p class="font-semibold">Admin controls</p>
                    <p class="mt-1 text-emerald-800">Manage queue settings, reports, and staff.</p>
                </a>
                <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800 hover:bg-slate-100">
                    <p class="font-semibold">Approved office staff accounts</p>
                    <p class="mt-1 text-slate-600">Approve or manage staff.</p>
                </a>
            @elseif($user->isOfficeStaff())
                @if($canOpenOfficeDashboard)
                    <a href="{{ route('office.dashboard') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 hover:bg-emerald-100">
                        <p class="font-semibold">Live queue</p>
                        <p class="mt-1 text-emerald-800">Call, serve, complete, and update the line.</p>
                    </a>
                @endif
                @if($canViewOfficeReports && $queueEnabled)
                    <a href="{{ route('office.reports') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800 hover:bg-slate-100">
                        <p class="font-semibold">Reports</p>
                        <p class="mt-1 text-slate-600">Check queue totals.</p>
                    </a>
                @endif
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 sm:col-span-2">
                    Return to the workspace home page.
                </div>
            @endif
        </div>
    </section>

    <aside class="rounded-[1.75rem] border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-6 text-white shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Workspace</p>
        <h2 class="mt-2 text-xl font-semibold">{{ $workspaceName }}</h2>
        <dl class="mt-5 space-y-3 text-sm text-slate-200">
            <div>
                <dt class="text-slate-400">Tenant domain</dt>
                <dd class="font-medium text-white">{{ $workspaceHost ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-slate-400">Logged-in account</dt>
                <dd class="font-medium text-white">{{ $user->name }}</dd>
            </div>
            @if($office)
                <div>
                    <dt class="text-slate-400">Assigned workspace</dt>
                    <dd class="font-medium text-white">{{ $office->name }}</dd>
                </div>
            @endif
        </dl>
    </aside>
</div>
@endsection
