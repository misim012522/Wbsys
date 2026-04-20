@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $dashboardProfile = \App\Support\TenantDashboardProfile::for($tenant);
    $queueLabel = $tenantTheme['queue_label'] ?? 'Queue';
@endphp

@include('admin._workspace-nav', [
    'title' => $dashboardProfile['name'] ?? 'Admin dashboard',
    'description' => 'Manage approvals, workspace activity, and account controls for this tenant. '.$dashboardProfile['admin_focus'],
])

<p class="sr-only">Admin dashboard</p>

<div class="mb-8 grid gap-4 lg:grid-cols-2">
    @foreach(($dashboardProfile['admin_cards'] ?? []) as $card)
        <div class="stat-card">
            <p class="text-base font-semibold text-slate-900">{{ $card['title'] }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $card['body'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-sm text-slate-500 mb-1">{{ $queueLabel }} today</p>
        <p class="text-3xl font-bold text-emerald-600">{{ $todayQueues }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-slate-500 mb-1">Completed today</p>
        <p class="text-3xl font-bold text-slate-700">{{ $completedToday }}</p>
    </div>
</div>

<div class="panel p-5">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800">Office staff queue snapshot</h2>
        <p class="text-sm text-slate-500">Admin visibility only</p>
    </div>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse($staffQueueStats as $staff)
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="font-semibold text-slate-800">{{ $staff['name'] }}</p>
                <p class="text-xs text-slate-500">{{ $staff['office_name'] ?? 'No office assigned' }}</p>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-slate-500">Waiting</span>
                    <span class="font-semibold text-amber-600">{{ $staff['waiting_count'] }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-sm">
                    <span class="text-slate-500">Completed</span>
                    <span class="font-semibold text-emerald-600">{{ $staff['completed_count'] }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                No office staff accounts available yet.
            </div>
        @endforelse
    </div>
</div>

@include('admin._workspace-nav-footer')
@endsection
