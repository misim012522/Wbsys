{{-- Dashboard with Real-Time Updates Example --}}
{{-- This can be used as a reference for implementing real-time refresh --}}
@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Admin Dashboard</h1>
    <div id="refresh-status" class="text-sm text-slate-500">
        <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
        Live updates enabled
    </div>
</div>

<!-- Statistics Cards with Real-Time Updates -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm text-slate-500 mb-1">Today's Active Queue</p>
        <p class="text-3xl font-bold text-emerald-600" id="total-queue-count">0</p>
        <p class="text-xs text-slate-400 mt-2">Updates every 5 seconds</p>
    </div>
    
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm text-slate-500 mb-1">Today's Appointments</p>
        <p class="text-3xl font-bold text-blue-600" id="total-appointments-count">0</p>
        <p class="text-xs text-slate-400 mt-2">Updates every 5 seconds</p>
    </div>
    
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-sm text-slate-500 mb-1">Completed Today</p>
        <p class="text-3xl font-bold text-slate-700" id="total-completed-count">0</p>
        <p class="text-xs text-slate-400 mt-2">Updates every 5 seconds</p>
    </div>
</div>

<!-- Offices with Per-Office Real-Time Stats -->
<h2 class="text-lg font-semibold text-slate-800 mb-4">Offices Overview</h2>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @forelse($offices as $office)
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-3">
                <h3 class="font-semibold text-slate-800">{{ $office->name }}</h3>
                <a href="{{ route('admin.offices.edit', $office) }}" class="text-xs text-slate-500 hover:underline">Edit</a>
            </div>
            
            <!-- Per-Office Stats -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-emerald-50 rounded-lg p-2">
                    <p class="text-xs text-slate-600">Queue</p>
                    <p class="text-xl font-bold text-emerald-600" id="queue-count-{{ $office->id }}">0</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-2">
                    <p class="text-xs text-slate-600">Appointments</p>
                    <p class="text-xl font-bold text-blue-600" id="appointments-count-{{ $office->id }}">0</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('admin.serve', $office) }}" class="flex-1 text-center text-sm px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                    Serve →
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-12 text-slate-500">
            <p>No offices created yet</p>
            <a href="{{ route('admin.offices.create') }}" class="text-emerald-600 hover:underline">Create one</a>
        </div>
    @endforelse
</div>

<!-- Add Office Button -->
@if($offices->count() > 0)
    <div class="mt-4">
        <a href="{{ route('admin.offices.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
            + Add Office
        </a>
    </div>
@endif

<!-- Real-Time Refresh Setup -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Setup aggregate statistics (sum of all offices)
    @foreach($offices as $office)
        // Setup per-office real-time stats
        window.setupQueueRefresh('queue-count-{{ $office->id }}', {{ $office->id }}, 5000);
        window.setupAppointmentsRefresh('appointments-count-{{ $office->id }}', {{ $office->id }}, 5000);
    @endforeach
    
    // Setup handler for tab focus changes
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            document.getElementById('refresh-status').innerHTML = 
                '<span class="inline-block w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>' +
                'Live updates paused';
        } else {
            document.getElementById('refresh-status').innerHTML = 
                '<span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>' +
                'Live updates enabled';
        }
    });
    
    console.log('Dashboard real-time updates initialized');
});
</script>

<style>
    [id*="queue-count"], [id*="appointments-count"] {
        transition: color 0.3s ease;
    }
</style>
@endsection
