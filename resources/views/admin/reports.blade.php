@extends('layouts.app')

@section('title', 'Daily Reports')

@section('content')
@php
    $queueTotal = $queueEntries->count();
    $completedTotal = $queueEntries->where('status', 'completed')->count();
@endphp

@include('admin._workspace-nav', [
    'title' => 'Daily reports',
    'description' => 'Check queue totals for a selected date.',
])

<form method="GET" action="{{ route('admin.reports') }}" class="mb-8 rounded-[1.75rem] border border-slate-200 bg-white/50/50 p-5 shadow-sm">
    <div class="flex flex-wrap gap-4">
        <div>
        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
        <input type="date" name="date" id="date" value="{{ $date }}" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
        </div>
        <div>
            <label for="office_id" class="block text-sm font-medium text-slate-700 mb-1">Office</label>
            <select name="office_id" id="office_id" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                <option value="0">All offices</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}" @selected($officeId === $office->id)>{{ $office->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-[1.75rem] bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Apply</button>
            @if($officeId > 0)
                <a href="{{ route('admin.reports', ['date' => $date]) }}" class="rounded-[1.75rem] border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-white/50/30">Clear office</a>
            @endif
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.download', ['date' => $date, 'office_id' => $officeId, 'format' => 'csv']) }}" class="rounded-[1.75rem] border border-slate-300 bg-white/50/50 px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-white/50/30">
                    CSV
                </a>
                <a href="{{ route('admin.reports.download', ['date' => $date, 'office_id' => $officeId, 'format' => 'pdf']) }}" target="_blank" class="rounded-[1.75rem] border border-slate-300 bg-white/50/50 px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-white/50/30">
                    PDF
                </a>
            </div>
        </div>
    </div>
</form>

<h2 class="text-lg font-semibold text-slate-800 mb-2">Queue list</h2>
<div class="mb-8 overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white/50/50 shadow-sm">
    <table class="w-full">
        <thead class="bg-white/50/30 border-b border-slate-200">
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
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $e->service_type ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">@if($e->guest_email)<a href="mailto:{{ $e->guest_email }}" class="text-blue-600 hover:underline">{{ $e->guest_email }}</a>@endif @if($e->guest_email && $e->guest_phone)<br>@endif @if($e->guest_phone)<a href="tel:{{ $e->guest_phone }}" class="text-blue-600 hover:underline">{{ $e->guest_phone }}</a>@endif @if(!$e->guest_email && !$e->guest_phone)-@endif</td>
                    <td class="px-4 py-3 text-slate-600 font-mono text-sm">{{ $e->reference_code }}</td>
                    <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $e->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-slate-500 text-center">No queue entries for this date.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin._workspace-nav-footer')
@endsection
