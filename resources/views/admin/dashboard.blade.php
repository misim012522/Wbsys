@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $workspaceHost = $tenant ? parse_url(\App\Support\TenantUrl::workspace($tenant), PHP_URL_HOST) : null;
    $dashboardProfile = \App\Support\TenantDashboardProfile::for($tenant);
    $queueLabel = $tenantTheme['queue_label'] ?? 'Queue';
    $appointmentLabel = $tenantTheme['appointment_label'] ?? 'Appointment';
    $officeLabel = $tenantTheme['office_label'] ?? 'Office';
    $queueEnabled = (bool) ($tenantTheme['guest_queue_enabled'] ?? true);
    $appointmentsEnabled = (bool) ($tenantTheme['appointments_enabled'] ?? true);
@endphp

@include('admin._workspace-nav', [
    'title' => $dashboardProfile['name'] ?? 'Admin dashboard',
    'description' => 'Manage approvals, workspace activity, and account controls for this tenant. '.$dashboardProfile['admin_focus'],
])

<div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-sm font-medium uppercase tracking-[0.22em] text-slate-400">Admin dashboard</p>
        <h2 class="text-2xl font-bold text-slate-800">Admin tools</h2>
        <p class="mt-2 text-sm text-slate-500">This admin area reflects the enabled features and labels configured for this tenant.</p>
    </div>

    <div class="w-full max-w-3xl space-y-3 lg:w-auto">
        <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-emerald-50/40 p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3 px-1 pb-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Quick actions</p>
                    <p class="mt-1 text-sm text-slate-500">Jump straight into the main workspace tasks.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @if($office && $queueEnabled)
                    <a href="{{ route('admin.qr') }}" class="group rounded-2xl border border-emerald-200 bg-white p-4 text-emerald-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50/70">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zm0 10h6v6H4zm10-10h6v6h-6zm3 10h3v3h-3zm-3 0h3m-3 3h3m3-3v6" /></svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">Office-led</span>
                        </div>
                        <div class="mt-5">
                            <p class="text-base font-semibold text-slate-800">Public access links</p>
                            <p class="mt-1 text-sm text-slate-500">Prepare the public QR and share it with your office staff for day-to-day queue handling.</p>
                        </div>
                    </a>
                @endif
                <a href="{{ route('admin.users.pending') }}" class="group rounded-2xl border border-slate-200 bg-slate-900 p-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2m8-10a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Review</span>
                    </div>
                    <div class="mt-5">
                        <p class="text-base font-semibold">Pending accounts</p>
                        <p class="mt-1 text-sm text-slate-300">Approve newly registered {{ strtolower($officeLabel) }} staff and unlock workspace access.</p>
                    </div>
                </a>
            </div>
        </div>

    </div>
</div>

<div class="mb-8 grid gap-4 lg:grid-cols-2">
    @foreach(($dashboardProfile['admin_cards'] ?? []) as $card)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-base font-semibold text-slate-900">{{ $card['title'] }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $card['body'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">{{ $queueLabel }} today</p>
        <p class="text-3xl font-bold text-emerald-600">{{ $todayQueues }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">{{ $appointmentLabel }} today</p>
        <p class="text-3xl font-bold text-blue-600">{{ $todayAppointments }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Completed today</p>
        <p class="text-3xl font-bold text-slate-700">{{ $completedToday }}</p>
    </div>
</div>

@endsection
