@extends('layouts.app')

@section('title', 'Reports — ' . $office->name)

@section('content')
@php
    $viewer = auth()->user();
@endphp

@include('office._workspace-nav', [
    'title' => 'Reports',
    'description' => 'Generate and download office queue reports for your selected date range.',
])

<div class="panel mb-8 overflow-hidden shadow-xl shadow-slate-200/50">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fffc_42%,_#eef6ff_100%)] p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Office report tools</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $office->name }}</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">Filter by date and export your office queue data in CSV or PDF format.</p>
            </div>

            <form method="GET" action="{{ route('office.reports') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label for="date" class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}" class="rounded-xl border border-slate-300 bg-white/90 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200">Apply</button>
                </div>
                <a href="{{ route('office.reports.download', ['date' => $date, 'format' => 'csv']) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download CSV
                </a>
                <a href="{{ route('office.reports.download', ['date' => $date, 'format' => 'pdf']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white/90 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h8z" /></svg>
                    Print / Save as PDF
                </a>
            </form>
        </div>
    </div>
</div>

<div class="panel overflow-hidden">
    <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
        <h2 class="text-lg font-semibold text-slate-800">Queue ({{ $date }})</h2>
    </div>

    <div class="overflow-x-auto">
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
</div>

@include('office._workspace-nav-footer')

@endsection
