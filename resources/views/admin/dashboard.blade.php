@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $dashboardProfile = \App\Support\TenantDashboardProfile::for($tenant);
    $queueLabel = $tenantTheme['queue_label'] ?? 'Queue';
    $appointmentLabel = $tenantTheme['appointment_label'] ?? 'Appointment';
@endphp

@include('admin._workspace-nav', [
    'title' => $dashboardProfile['name'] ?? 'Admin dashboard',
    'description' => 'Manage approvals, workspace activity, and account controls for this tenant. '.$dashboardProfile['admin_focus'],
])

<p class="sr-only">Admin dashboard</p>

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
