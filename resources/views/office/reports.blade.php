@extends('layouts.app')

@section('title', 'Reports — ' . $office->name)

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Reports — {{ $office->name }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('office.activity') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Activity</a>
        <a href="{{ route('office.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">My queue</a>
    </div>
</div>

<form method="GET" action="{{ route('office.reports') }}" class="flex flex-wrap gap-4 mb-8 items-end">
    <div>
        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
        <input type="date" name="date" id="date" value="{{ $date }}" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
    </div>
    <div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200">Apply</button>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('office.reports.download', ['date' => $date, 'format' => 'csv']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Download CSV
        </a>
        <a href="{{ route('office.reports.download', ['date' => $date, 'format' => 'pdf']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h8z" /></svg>
            Print / Save as PDF
        </a>
    </div>
</form>

<h2 class="text-lg font-semibold text-slate-800 mb-2">Queue ({{ $date }})</h2>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">#</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Type</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Contact (remind)</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($queueEntries as $e)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-600">{{ $e->queue_number }}</td>
                    <td class="px-4 py-3 text-slate-800">{{ $e->display_name }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $e->service_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">@if($e->guest_email)<a href="mailto:{{ $e->guest_email }}" class="text-blue-600 hover:underline">{{ $e->guest_email }}</a>@endif @if($e->guest_email && $e->guest_phone)<br>@endif @if($e->guest_phone)<a href="tel:{{ $e->guest_phone }}" class="text-blue-600 hover:underline">{{ $e->guest_phone }}</a>@endif @if(!$e->guest_email && !$e->guest_phone)—@endif</td>
                    <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $e->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-slate-500 text-center">No queue entries for this date.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-lg font-semibold text-slate-800 mb-2">Appointments ({{ $date }})</h2>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Time</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Type</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Contact (remind)</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $a)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</td>
                    <td class="px-4 py-3 text-slate-800">{{ $a->display_name }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $a->appointment_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">@if($a->guest_email)<a href="mailto:{{ $a->guest_email }}" class="text-blue-600 hover:underline">{{ $a->guest_email }}</a>@endif @if($a->guest_email && $a->guest_phone)<br>@endif @if($a->guest_phone)<a href="tel:{{ $a->guest_phone }}" class="text-blue-600 hover:underline">{{ $a->guest_phone }}</a>@endif @if(!$a->guest_email && !$a->guest_phone)—@endif</td>
                    <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $a->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-slate-500 text-center">No appointments for this date.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
