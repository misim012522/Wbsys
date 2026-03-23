@extends('layouts.app')

@section('title', 'My Workspace')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">My Workspace</h1>
    <a href="{{ route('student.offices') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Queue Services</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
        <h2 class="text-lg font-semibold text-slate-800 mb-4">My Queue Numbers</h2>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($myQueues as $q)
                    <li class="px-4 py-3 flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-slate-800">{{ $q->office->name }}</span>
                            <span class="text-slate-600 ml-2">#{{ $q->queue_number }}</span>
                            <span class="ml-2 text-xs text-slate-500">{{ $q->queue_date->format('M d') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                                @if($q->status === 'waiting') bg-amber-100 text-amber-800
                                @elseif($q->status === 'called' || $q->status === 'serving') bg-emerald-100 text-emerald-800
                                @else bg-slate-100 text-slate-600 @endif">{{ $q->status }}</span>
                            <a href="{{ route('student.queue-tracker', $q->reference_code) }}" class="text-sm text-emerald-600 hover:underline">Track</a>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-slate-500 text-center">You have no active queue numbers. <a href="{{ route('student.offices') }}" class="text-emerald-600 hover:underline">Get a number</a></li>
                @endforelse
            </ul>
        </div>
    </div>

    <div>
        <h2 class="text-lg font-semibold text-slate-800 mb-4">My Appointments</h2>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($myAppointments as $a)
                    <li class="px-4 py-3 flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-slate-800">{{ $a->office->name }}</span>
                            <span class="text-slate-600 ml-2">{{ $a->appointment_date->format('M d') }} - {{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</span>
                        </div>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                            @if($a->status === 'pending') bg-amber-100 text-amber-800
                            @elseif($a->status === 'confirmed') bg-emerald-100 text-emerald-800
                            @else bg-slate-100 text-slate-600 @endif">{{ $a->status }}</span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-slate-500 text-center">You have no upcoming appointments. <a href="{{ route('student.offices') }}" class="text-emerald-600 hover:underline">Book one</a></li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
