@extends('layouts.app')

@section('title', 'Office Staff Dashboard')

@section('content')
@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $viewer = auth()->user();
    $dashboardProfile = \App\Support\TenantDashboardProfile::for($tenant);
    $queueLabel = $tenantTheme['queue_label'] ?? 'Queue';
    $officeLabel = $tenantTheme['office_label'] ?? 'Office';
    $queueEnabled = (bool) ($tenantTheme['guest_queue_enabled'] ?? true);
    $canUseQr = $viewer?->hasPermission('office.qr');
    $canManageQueue = $viewer?->hasPermission('office.queue.manage');
    $canViewReports = $viewer?->hasPermission('reports.view');
    $canViewActivity = $viewer?->hasPermission('office.activity.view');
@endphp

@include('office._workspace-nav', [
    'title' => $office->name,
    'description' => 'Call the next number and update live queue status for your assigned office.',
])

@if($updateAvailable)
<div class="mb-6 rounded-xl border-2 border-amber-200 bg-amber-50 p-4">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div class="flex-1">
            <p class="font-semibold text-amber-900">System update available</p>
            <p class="mt-1 text-sm text-amber-800">
                New version {{ $latestVersion->version }} is available. You are currently on version {{ $currentVersion }}.
            </p>
            @if($latestVersion->download_url)
            <a href="{{ $latestVersion->download_url }}" target="_blank" class="mt-2 inline-flex items-center text-sm font-medium text-amber-700 hover:text-amber-900">
                View release notes
                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            @endif
        </div>
    </div>
</div>
@endif

<div class="panel mb-6 overflow-hidden shadow-xl shadow-slate-200/50">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fffc_45%,_#eef6ff_100%)] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-600">Live queue</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $office->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Call the next number and update live queue status.</p>
            </div>

        </div>
    </div>
</div>

<div class="panel mb-8 p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Counter controls</h2>
            <p class="mt-2 text-sm text-slate-500">Use this page to move the line.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($canManageQueue)
                <form method="POST" action="{{ route('office.call-next') }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Call next</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="mb-8 grid gap-4 md:grid-cols-2">
    <div class="stat-card">
        <p class="text-sm text-slate-500">Waiting now</p>
        <p class="mt-3 text-3xl font-bold text-slate-900">{{ $todayQueue->count() }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-slate-500">Serving now</p>
        <p class="mt-3 text-3xl font-bold text-emerald-600">{{ $currentServing ? '#'.$currentServing->queue_number : '-' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-8">
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Queue today</h2>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500">{{ $todayQueue->count() }} in line</span>
                @if($canManageQueue && $todayQueue->count() > 0)
                    <form method="POST" action="{{ route('office.queue.clear-all') }}" onsubmit="return confirm('Are you sure you want to clear all queues? This cannot be undone.');" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700">Clear All</button>
                    </form>
                @endif
            </div>
        </div>

        @if($currentServing)
            <div class="mb-4 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-sm font-medium text-emerald-700">Now serving</p>
                <p class="text-2xl font-bold text-emerald-800">#{{ $currentServing->queue_number }}</p>
                <p class="text-sm text-emerald-600">{{ $currentServing->display_name }}</p>
                @if($currentServing->service_type)
                    <p class="mt-0.5 text-xs text-emerald-700">{{ $currentServing->service_type }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-600">
                    Contact:
                    @if($currentServing->guest_email)
                        <a href="mailto:{{ $currentServing->guest_email }}" class="text-blue-600 hover:underline">{{ $currentServing->guest_email }}</a>
                    @endif
                    @if($currentServing->guest_email && $currentServing->guest_phone)
                        |
                    @endif
                    @if($currentServing->guest_phone)
                        <a href="tel:{{ $currentServing->guest_phone }}" class="text-blue-600 hover:underline">{{ $currentServing->guest_phone }}</a>
                    @endif
                    @if(!$currentServing->guest_email && !$currentServing->guest_phone)
                        -
                    @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('office.queue.update', $currentServing) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="serving">
                        <button type="submit" class="rounded-full bg-emerald-600 px-3 py-1.5 text-sm text-white">Mark serving</button>
                    </form>
                    <form method="POST" action="{{ route('office.queue.update', $currentServing) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="rounded-full bg-slate-700 px-3 py-1.5 text-sm text-white">Complete</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="panel overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($todayQueue as $q)
                    <li class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between {{ $q->status === 'called' ? 'bg-amber-50' : '' }}">
                        <div class="min-w-0">
                            <span class="font-semibold text-slate-800">#{{ $q->queue_number }}</span>
                            <span class="ml-2 text-slate-600">{{ $q->display_name }}</span>
                            @if($q->service_type)
                                <span class="ml-2 text-xs text-slate-500">({{ $q->service_type }})</span>
                            @endif
                            <span class="ml-2 text-xs text-slate-500">{{ $q->reference_code }}</span>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                Contact:
                                @if($q->guest_email)
                                    <a href="mailto:{{ $q->guest_email }}" class="text-blue-600 hover:underline">{{ $q->guest_email }}</a>
                                @endif
                                @if($q->guest_email && $q->guest_phone)
                                    |
                                @endif
                                @if($q->guest_phone)
                                    <a href="tel:{{ $q->guest_phone }}" class="text-blue-600 hover:underline">{{ $q->guest_phone }}</a>
                                @endif
                                @if(!$q->guest_email && !$q->guest_phone)
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if($canManageQueue && $q->status === 'waiting')
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="called">
                                    <button type="submit" class="rounded-full bg-amber-100 px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-200">Call</button>
                                </form>
                            @endif
                            @if($canManageQueue && in_array($q->status, ['called', 'serving']))
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="serving">
                                    <button type="submit" class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs text-emerald-800">Serving</button>
                                </form>
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-700">Complete</button>
                                </form>
                            @endif
                            @if($canManageQueue && in_array($q->status, ['waiting', 'called', 'serving']))
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="rounded-full bg-red-100 px-3 py-1.5 text-xs text-red-700">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500">No one in queue.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@include('office._workspace-nav-footer')
@endsection
