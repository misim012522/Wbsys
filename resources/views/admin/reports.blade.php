@extends('layouts.app')

@section('title', 'Daily Reports')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Daily Reports</h1>
    <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Dashboard</a>
</div>

<form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap gap-4 mb-8">
    <div>
        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
        <input type="date" name="date" id="date" value="{{ $date }}" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
    </div>
    <div>
        <label for="office_id" class="block text-sm font-medium text-slate-700 mb-1">Office</label>
        <select name="office_id" id="office_id" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
            <option value="">All offices</option>
            @foreach($offices as $o)
                <option value="{{ $o->id }}" {{ $officeId == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end">
        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Apply</button>
    </div>
</form>

<h2 class="text-lg font-semibold text-slate-800 mb-2">Queue entries ({{ $date }})</h2>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Office</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">#</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Type</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Contact (remind)</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Reference</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($queueEntries as $e)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-800">{{ $e->office->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $e->queue_number }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $e->display_name }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $e->service_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">@if($e->guest_email)<a href="mailto:{{ $e->guest_email }}" class="text-blue-600 hover:underline">{{ $e->guest_email }}</a>@endif @if($e->guest_email && $e->guest_phone)<br>@endif @if($e->guest_phone)<a href="tel:{{ $e->guest_phone }}" class="text-blue-600 hover:underline">{{ $e->guest_phone }}</a>@endif @if(!$e->guest_email && !$e->guest_phone)—@endif</td>
                    <td class="px-4 py-3 text-slate-600 font-mono text-sm">{{ $e->reference_code }}</td>
                    <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $e->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-slate-500 text-center">No queue entries for this date.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-lg font-semibold text-slate-800 mb-2">Appointments ({{ $date }})</h2>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Office</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Time</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Type</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Contact (remind)</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Reference</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $a)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-800">{{ $a->office->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $a->display_name }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $a->appointment_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">@if($a->guest_email)<a href="mailto:{{ $a->guest_email }}" class="text-blue-600 hover:underline">{{ $a->guest_email }}</a>@endif @if($a->guest_email && $a->guest_phone)<br>@endif @if($a->guest_phone)<a href="tel:{{ $a->guest_phone }}" class="text-blue-600 hover:underline">{{ $a->guest_phone }}</a>@endif @if(!$a->guest_email && !$a->guest_phone)—@endif</td>
                    <td class="px-4 py-3 text-slate-600 font-mono text-sm">{{ $a->reference_code }}</td>
                    <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $a->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-slate-500 text-center">No appointments for this date.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
