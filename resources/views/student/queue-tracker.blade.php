@extends('layouts.app')

@section('title', 'Queue Tracker')

@section('content')
<div class="max-w-md mx-auto text-center">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ $entry->office->name }}</h1>
    <p class="text-slate-600 mb-8">Your queue status</p>

    <div class="bg-white rounded-2xl border-2 border-emerald-200 shadow-lg p-8 mb-6">
        <p class="text-sm text-slate-500 uppercase tracking-wide mb-1">Your number</p>
        <p class="text-5xl font-bold text-emerald-600 mb-4">#{{ $entry->queue_number }}</p>
        <p class="text-sm text-slate-600 font-mono">{{ $entry->reference_code }}</p>
    </div>

    <div class="bg-slate-50 rounded-xl p-6 mb-6">
        <p class="text-slate-600 mb-1">Position in line</p>
        <p class="text-3xl font-bold text-slate-800">{{ $position }}</p>
        @if($ahead > 0)
            <p class="text-sm text-slate-500 mt-2">{{ $ahead }} {{ Str::plural('person', $ahead) }} ahead of you</p>
        @else
            <p class="text-sm text-emerald-600 font-medium mt-2">You're next or being served!</p>
        @endif
    </div>

    <p class="text-sm text-slate-500 mb-2">Status: <span class="font-medium text-slate-700">{{ $entry->status }}</span></p>
    <p class="text-xs text-slate-400">Save this page or your reference code to check back later.</p>

    <div class="mt-8">
        <a href="{{ route('student.dashboard') }}" class="text-emerald-600 hover:underline font-medium">Back to Dashboard</a>
    </div>
</div>
@endsection
