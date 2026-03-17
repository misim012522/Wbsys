@extends('layouts.app')

@section('title', 'Offices')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">School Offices</h1>
    <a href="{{ route('student.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">My Dashboard</a>
</div>

<p class="text-slate-600 mb-6">Select an office to get a queue number or book an appointment.</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach($offices as $office)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col">
            <h2 class="text-lg font-semibold text-slate-800">{{ $office->name }}</h2>
            @if($office->description)
                <p class="text-sm text-slate-600 mt-1 mb-3">{{ $office->description }}</p>
            @endif
            @if($office->location)
                <p class="text-xs text-slate-500 mb-3">{{ $office->location }}</p>
            @endif
            <div class="mt-auto flex flex-wrap gap-2">
                <form method="POST" action="{{ route('student.get-queue', $office) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Get Queue Number</button>
                </form>
                <a href="{{ route('student.book', $office) }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Book Appointment</a>
                <a href="{{ route('student.live-queue', $office) }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50">Live Queue</a>
            </div>
        </div>
    @endforeach
</div>

@if($offices->isEmpty())
    <p class="text-slate-500 text-center py-8">No offices available.</p>
@endif
@endsection
