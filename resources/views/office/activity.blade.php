@extends('layouts.app')

@section('title', 'Activity — ' . $office->name)

@section('content')
@php
    $viewer = auth()->user();
    $canOpenDashboard = $viewer?->hasPermission('office.dashboard');
@endphp
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Activity log — {{ $office->name }}</h1>
    @if($canOpenDashboard)
        <a href="{{ route('office.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">My queue</a>
    @endif
</div>

<p class="text-slate-600 mb-6">Recent activity for your office: logins and queue actions.</p>

<form method="GET" action="{{ route('office.activity') }}" class="flex flex-wrap gap-4 mb-6 items-end">
    <div>
        <label for="action" class="block text-sm font-medium text-slate-700 mb-1">Action</label>
        <select name="action" id="action" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 text-sm">
            @foreach($actionOptions as $value => $label)
                <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
        <input type="date" name="date" id="date" value="{{ request('date') }}" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 text-sm">
    </div>
    <div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200">Filter</button>
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <ul class="divide-y divide-slate-100">
        @forelse($activities as $log)
            <li class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-slate-800">{{ $log->description }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">{{ str_replace('_', ' ', $log->action) }}</span>
                        @if($log->user)
                            · {{ $log->user->name }}
                        @else
                            · Visitor
                        @endif
                        · {{ $log->created_at->diffForHumans() }}
                        @if($log->created_at->format('Y-m-d') !== now()->format('Y-m-d'))
                            ({{ $log->created_at->format('M j, Y g:i A') }})
                        @endif
                    </p>
                </div>
            </li>
        @empty
            <li class="px-4 py-12 text-slate-500 text-center">No activity yet.</li>
        @endforelse
    </ul>
</div>

@if($activities->hasPages())
    <div class="mt-6">
        {{ $activities->links() }}
    </div>
@endif
@endsection
