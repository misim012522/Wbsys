@extends('layouts.app')

@section('title', 'Office Staff Dashboard')

@section('content')
@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $dashboardProfile = \App\Support\TenantDashboardProfile::for($tenant);
    $queueLabel = $tenantTheme['queue_label'] ?? 'Queue';
    $appointmentLabel = $tenantTheme['appointment_label'] ?? 'Appointment';
    $officeLabel = $tenantTheme['office_label'] ?? 'Office';
    $queueEnabled = (bool) ($tenantTheme['guest_queue_enabled'] ?? true);
    $appointmentsEnabled = (bool) ($tenantTheme['appointments_enabled'] ?? true);
@endphp

<div class="mb-6 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-xl shadow-slate-200/50">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fffc_45%,_#eef6ff_100%)] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-600">Office staff workspace</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $office->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">{{ $officeLabel }} operations stay inside this tenant workspace.</p>
                <p class="mt-3 text-sm text-slate-500">{{ $dashboardProfile['office_focus'] }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tenant.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Workspace settings</a>
                @if($queueEnabled)
                    <a href="{{ route('office.qr') }}" class="rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">QR code</a>
                @endif
                @if($appointmentsEnabled || $queueEnabled)
                    <a href="{{ route('office.reports') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Reports</a>
                @endif
                <a href="{{ route('office.activity') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Activity</a>
            </div>
        </div>
    </div>
</div>

<div class="mb-8 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $officeLabel }} {{ strtolower($queueLabel) }} and {{ strtolower($appointmentLabel) }}</h2>
            <p class="mt-2 text-sm text-slate-500">The actions below reflect the enabled features and labels configured for this tenant.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('office.call-next') }}" class="inline">
                @csrf
                <button type="submit" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Call next</button>
            </form>
        </div>
    </div>
</div>

<div class="mb-8 grid gap-4 md:grid-cols-2">
    @foreach($dashboardProfile['office_cards'] as $card)
        <div class="rounded-[1.5rem] border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-900">{{ $card['title'] }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $card['body'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">{{ $queueLabel }} - Today</h2>
            <span class="text-sm text-slate-500">Live queue controls</span>
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

        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <ul class="divide-y divide-slate-100">
                @forelse($todayQueue as $q)
                    <li class="flex items-center justify-between px-4 py-3 {{ $q->status === 'called' ? 'bg-amber-50' : '' }}">
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
                        <div class="flex shrink-0 gap-2">
                            @if($q->status === 'waiting')
                                <form method="POST" action="{{ route('office.queue.update', $q) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="called">
                                    <button type="submit" class="rounded-full bg-amber-100 px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-200">Call</button>
                                </form>
                            @endif
                            @if(in_array($q->status, ['called', 'serving']))
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
                            @if(in_array($q->status, ['waiting', 'called', 'serving']))
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

    <div>
        <h2 class="mb-4 text-lg font-semibold text-slate-800">{{ $appointmentLabel }} - Today</h2>
        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <ul class="divide-y divide-slate-100">
                @forelse($todayAppointments as $a)
                    <li class="flex items-center justify-between px-4 py-3">
                        <div class="min-w-0">
                            <span class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</span>
                            <span class="ml-2 text-slate-600">{{ $a->display_name }}</span>
                            @if($a->appointment_type)
                                <span class="ml-2 text-xs text-slate-500">({{ $a->appointment_type }})</span>
                            @endif
                            @if($a->purpose)
                                <p class="mt-0.5 text-xs text-slate-500">{{ $a->purpose }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-slate-500">
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
                        <div class="flex shrink-0 gap-2">
                            @if($a->status === 'pending')
                                <form method="POST" action="{{ route('office.appointments.accept', $a) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs text-emerald-800">Accept</button>
                                </form>
                            @endif
                            @if(in_array($a->status, ['pending', 'confirmed']))
                                <form method="POST" action="{{ route('office.appointments.complete', $a) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-700">Complete</button>
                                </form>
                                <form method="POST" action="{{ route('office.appointments.cancel', $a) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-red-100 px-3 py-1.5 text-xs text-red-700">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500">No appointments today.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
