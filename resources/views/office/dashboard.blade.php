@extends('layouts.app')

@section('title', 'Office Staff Dashboard')

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

<div class="mb-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Workspace dashboard</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $office->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">
                {{ $officeLabel }} operations stay inside this tenant workspace.
                @if($workspaceHost)
                    Active domain: <span class="font-semibold text-slate-800">{{ $workspaceHost }}</span>.
                @endif
            </p>
            <p class="mt-2 text-sm text-slate-500">{{ $dashboardProfile['office_focus'] }}</p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Shared workspace dashboard</a>
            @if($queueEnabled)
                <a href="{{ route('office.qr') }}" class="px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">QR code</a>
            @endif
            @if($appointmentsEnabled || $queueEnabled)
                <a href="{{ route('office.reports') }}" class="px-4 py-2 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Reports</a>
            @endif
            <a href="{{ route('office.activity') }}" class="px-4 py-2 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Activity</a>
        </div>
    </div>
</div>

<div class="mb-8 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">{{ $officeLabel }} {{ strtolower($queueLabel) }} and {{ strtolower($appointmentLabel) }}</h2>
        <p class="mt-2 text-sm text-slate-500">The actions below reflect the enabled features and labels configured for this tenant.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <form method="POST" action="{{ route('office.call-next') }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">Call Next</button>
        </form>
    </div>
</div>

<div class="mb-8 grid gap-3 md:grid-cols-2">
    @foreach($dashboardProfile['office_cards'] as $card)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-900">{{ $card['title'] }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $card['body'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-800">{{ $queueLabel }} - Today</h2>
            <span class="text-sm text-slate-500">Live queue controls</span>
        </div>

        @if($currentServing)
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                <p class="text-sm text-emerald-700 font-medium">Now serving</p>
                <p class="text-2xl font-bold text-emerald-800">#{{ $currentServing->queue_number }}</p>
                <p class="text-sm text-emerald-600">{{ $currentServing->display_name }}</p>
                @if($currentServing->service_type)
                    <p class="text-xs text-emerald-700 mt-0.5">{{ $currentServing->service_type }}</p>
                @endif
                <p class="text-xs mt-1 text-slate-600">
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
                <div class="mt-2 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('office.queue.update', $currentServing) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="serving">
                        <button type="submit" class="text-sm px-2 py-1 rounded bg-emerald-600 text-white">Mark Serving</button>
                    </form>
                    <form method="POST" action="{{ route('office.queue.update', $currentServing) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="text-sm px-2 py-1 rounded bg-slate-600 text-white">Complete</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($todayQueue as $q)
                    <li class="px-4 py-3 flex items-center justify-between {{ $q->status === 'called' ? 'bg-amber-50' : '' }}">
                        <div class="min-w-0">
                            <span class="font-semibold text-slate-800">#{{ $q->queue_number }}</span>
                            <span class="text-slate-600 ml-2">{{ $q->display_name }}</span>
                            @if($q->service_type)
                                <span class="ml-2 text-xs text-slate-500">({{ $q->service_type }})</span>
                            @endif
                            <span class="ml-2 text-xs text-slate-500">{{ $q->reference_code }}</span>
                            <p class="text-xs text-slate-500 mt-0.5 truncate">
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
                        <div class="flex gap-2 shrink-0">
                            @if($q->status === 'waiting')
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="called">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200">Call</button>
                                </form>
                            @endif
                            @if(in_array($q->status, ['called', 'serving']))
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="serving">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-800">Serving</button>
                                </form>
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700">Complete</button>
                                </form>
                            @endif
                            @if(in_array($q->status, ['waiting', 'called', 'serving']))
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-red-100 text-red-700">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-slate-500 text-center">No one in queue.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div>
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ $appointmentLabel }} - Today</h2>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($todayAppointments as $a)
                    <li class="px-4 py-3 flex items-center justify-between">
                        <div class="min-w-0">
                            <span class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</span>
                            <span class="text-slate-600 ml-2">{{ $a->display_name }}</span>
                            @if($a->appointment_type)
                                <span class="ml-2 text-xs text-slate-500">({{ $a->appointment_type }})</span>
                            @endif
                            @if($a->purpose)
                                <p class="text-xs text-slate-500 mt-0.5">{{ $a->purpose }}</p>
                            @endif
                            <p class="text-xs text-slate-500 mt-0.5">
                                Contact:
                                @if($a->guest_email)
                                    <a href="mailto:{{ $a->guest_email }}" class="text-blue-600 hover:underline">{{ $a->guest_email }}</a>
                                @endif
                                @if($a->guest_email && $a->guest_phone)
                                    |
                                @endif
                                @if($a->guest_phone)
                                    <a href="tel:{{ $a->guest_phone }}" class="text-blue-600 hover:underline">{{ $a->guest_phone }}</a>
                                @endif
                                @if(!$a->guest_email && !$a->guest_phone)
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            @if($a->status === 'pending')
                                <form method="POST" action="{{ route('office.appointments.accept', $a) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-800">Accept</button>
                                </form>
                            @endif
                            @if(in_array($a->status, ['pending', 'confirmed']))
                                <form method="POST" action="{{ route('office.appointments.complete', $a) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700">Complete</button>
                                </form>
                                <form method="POST" action="{{ route('office.appointments.cancel', $a) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-red-100 text-red-700">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-slate-500 text-center">No appointments today.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
