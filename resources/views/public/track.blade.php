@extends('layouts.public')

@section('title', 'Queue status — QueueLess')

@section('content')
<div class="mt-6 text-center">
    <h1 class="text-xl font-bold text-slate-800">{{ $entry->office->name }}</h1>
    <p class="text-slate-600 text-sm mt-1">Your queue status</p>
</div>

<div class="mt-8 bg-white rounded-2xl border-2 border-emerald-200 shadow-lg p-8 text-center">
    <p class="text-sm text-slate-500 uppercase tracking-wide mb-1">Your number</p>
    <p class="text-5xl font-bold text-emerald-600">#{{ $entry->queue_number }}</p>
    <p class="text-sm text-slate-600 font-mono mt-4">{{ $entry->reference_code }}</p>
</div>

<div class="mt-6 bg-slate-100 rounded-xl p-6 text-center">
    <p class="text-slate-600 mb-1">Position in line</p>
    <p class="text-3xl font-bold text-slate-800">{{ $position }}</p>
    @if($ahead > 0)
        <p class="text-sm text-slate-500 mt-2">{{ $ahead }} {{ Str::plural('person', $ahead) }} ahead of you</p>
    @else
        <p class="text-sm text-emerald-600 font-medium mt-2">You're next or being served!</p>
    @endif
</div>

<p class="mt-6 text-center text-sm text-slate-500">Status: <span class="font-medium text-slate-700">{{ $entry->status }}</span></p>
<p class="mt-2 text-center text-xs text-slate-400">Save this page or your reference code to check back later.</p>
@endsection
