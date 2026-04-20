@extends('layouts.public')

@section('title', 'Queue status - QueueLess')

@section('content')
@php
    $office = $queueEntry->office;
@endphp

<div class="mt-2 text-center">
    <p class="info-kicker">Queue</p>
    <h1 class="mt-3 text-2xl font-bold text-slate-800 sm:text-3xl">{{ $office->name }}</h1>
    <p class="mt-1 text-sm text-slate-600">Check your place in line.</p>
</div>

<div class="panel mt-8 border-2 border-emerald-200 p-8 text-center shadow-lg">
    <p class="mb-1 text-sm uppercase tracking-wide text-slate-500">Queue number</p>
    <p class="text-5xl font-bold text-emerald-600">#{{ $queueEntry->queue_number }}</p>
    <p class="mt-4 font-mono text-sm text-slate-600">{{ $queueEntry->reference_code }}</p>
</div>

<div class="panel-soft mt-6 p-6 text-center">
    <p class="mb-1 text-slate-600">Position</p>
    <p class="text-3xl font-bold text-slate-800">{{ $position }}</p>
    @if($ahead > 0)
        <p class="mt-2 text-sm text-slate-500">{{ $ahead }} {{ Str::plural('person', $ahead) }} ahead of you</p>
    @else
        <p class="mt-2 text-sm font-medium text-emerald-600">You are next.</p>
    @endif
</div>

<div class="panel mt-6 p-6 text-center">
    <p class="text-sm text-slate-500">Status</p>
    <p class="mt-2 text-lg font-semibold capitalize text-slate-700">{{ str_replace('_', ' ', $queueEntry->status) }}</p>
    <p class="mt-2 text-xs text-slate-400">Save your number and reference code.</p>
</div>
@endsection
