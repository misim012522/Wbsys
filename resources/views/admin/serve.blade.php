@extends('layouts.app')

@section('title', 'Serve - ' . $office->name)

@section('content')
@include('admin._workspace-nav', [
    'title' => $office->name . ' live queue monitor',
    'description' => 'This view is available for tenant admin monitoring and backup assistance, but day-to-day live serving should be handled by office staff from their own workspace.',
    'actions' => [
        ['label' => 'QR codes', 'href' => route('admin.qr')],
        ['label' => 'Office staff', 'href' => route('admin.users.index')],
    ],
])

<div class="mb-6 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5 shadow-sm">
    <p class="text-sm font-semibold text-amber-900">Office staff first</p>
    <p class="mt-2 text-sm text-amber-800">Use the office staff workspace as the primary live serving screen for QR, queue calls, and appointment handling. This admin page is best kept for monitoring, supervision, or backup intervention.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-800">Queue - Today</h2>
            <form method="POST" action="{{ route('admin.call-next', $office) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">Call next</button>
            </form>
        </div>

        @if($currentServing)
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                <p class="text-sm text-emerald-700 font-medium">Now serving</p>
                <p class="text-2xl font-bold text-emerald-800">#{{ $currentServing->queue_number }}</p>
                <p class="text-sm text-emerald-600">{{ $currentServing->display_name }}</p>
                @if($currentServing->service_type)<p class="text-xs text-emerald-700 mt-0.5">{{ $currentServing->service_type }}</p>@endif
                <p class="text-xs mt-1 text-slate-600">Contact to remind:
                    @if($currentServing->guest_email)<a href="mailto:{{ $currentServing->guest_email }}" class="text-blue-600 hover:underline">{{ $currentServing->guest_email }}</a>@endif
                    @if($currentServing->guest_email && $currentServing->guest_phone) · @endif
                    @if($currentServing->guest_phone)<a href="tel:{{ $currentServing->guest_phone }}" class="text-blue-600 hover:underline">{{ $currentServing->guest_phone }}</a>@endif
                    @if(!$currentServing->guest_email && !$currentServing->guest_phone)<span class="text-slate-500">-</span>@endif
                </p>
                <form method="POST" action="{{ route('admin.queue.update', $currentServing) }}" class="mt-2 flex gap-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="serving">
                    <button type="submit" class="text-sm px-2 py-1 rounded bg-emerald-600 text-white">Mark Serving</button>
                </form>
                <form method="POST" action="{{ route('admin.queue.update', $currentServing) }}" class="inline mt-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="text-sm px-2 py-1 rounded bg-slate-600 text-white">Complete</button>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($todayQueue as $q)
                    <li class="px-4 py-3 flex items-center justify-between {{ $q->status === 'called' ? 'bg-amber-50' : '' }}">
                        <div class="min-w-0">
                            <span class="font-semibold text-slate-800">#{{ $q->queue_number }}</span>
                            <span class="text-slate-600 ml-2">{{ $q->display_name }}</span>
                            @if($q->service_type)<span class="ml-2 text-xs text-slate-500">({{ $q->service_type }})</span>@endif
                            <span class="ml-2 text-xs text-slate-500">{{ $q->reference_code }}</span>
                            <p class="text-xs text-slate-500 mt-0.5 truncate">Contact:
                                @if($q->guest_email)<a href="mailto:{{ $q->guest_email }}" class="text-blue-600 hover:underline">{{ $q->guest_email }}</a>@endif
                                @if($q->guest_email && $q->guest_phone) · @endif
                                @if($q->guest_phone)<a href="tel:{{ $q->guest_phone }}" class="text-blue-600 hover:underline">{{ $q->guest_phone }}</a>@endif
                                @if(!$q->guest_email && !$q->guest_phone)-@endif
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if($q->status === 'waiting')
                                <form method="POST" action="{{ route('admin.queue.update', $q) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="called">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200">Call</button>
                                </form>
                            @endif
                            @if(in_array($q->status, ['called', 'serving']))
                                <form method="POST" action="{{ route('admin.queue.update', $q) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="serving">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-800">Serving</button>
                                </form>
                                <form method="POST" action="{{ route('admin.queue.update', $q) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700">Complete</button>
                                </form>
                            @endif
                            @if(in_array($q->status, ['waiting', 'called', 'serving']))
                                <form method="POST" action="{{ route('admin.queue.update', $q) }}" class="inline">
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
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Appointments - Today</h2>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($todayAppointments as $a)
                    <li class="px-4 py-3 flex items-center justify-between">
                        <div class="min-w-0">
                            <span class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</span>
                            <span class="text-slate-600 ml-2">{{ $a->display_name }}</span>
                            @if($a->appointment_type)<span class="ml-2 text-xs text-slate-500">({{ $a->appointment_type }})</span>@endif
                            @if($a->purpose)<p class="text-xs text-slate-500 mt-0.5">{{ $a->purpose }}</p>@endif
                            <p class="text-xs text-slate-500 mt-0.5">Contact to remind:
                                @if($a->guest_email)<a href="mailto:{{ $a->guest_email }}" class="text-blue-600 hover:underline">{{ $a->guest_email }}</a>@endif
                                @if($a->guest_email && $a->guest_phone) · @endif
                                @if($a->guest_phone)<a href="tel:{{ $a->guest_phone }}" class="text-blue-600 hover:underline">{{ $a->guest_phone }}</a>@endif
                                @if(!$a->guest_email && !$a->guest_phone)-@endif
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if($a->status === 'pending')
                                <form method="POST" action="{{ route('admin.appointments.accept', $a) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-800">Accept</button>
                                </form>
                            @endif
                            @if(in_array($a->status, ['pending', 'confirmed']))
                                <form method="POST" action="{{ route('admin.appointments.complete', $a) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700">Complete</button>
                                </form>
                                <form method="POST" action="{{ route('admin.appointments.cancel', $a) }}" class="inline">
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
@include('admin._workspace-nav-footer')
