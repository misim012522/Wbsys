@extends('layouts.public')

@section('title', ($mode === 'appointment' ? 'Appointment status' : 'Queue status') . ' - QueueLess')

@section('content')
@php
    $office = $mode === 'appointment' ? $appointment->office : $queueEntry->office;
@endphp

<div class="mt-2 text-center">
    <p class="info-kicker">{{ $mode === 'appointment' ? 'Appointment' : 'Queue' }}</p>
    <h1 class="mt-3 text-2xl font-bold text-slate-800 sm:text-3xl">{{ $office->name }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $mode === 'appointment' ? 'Check your booking.' : 'Check your place in line.' }}</p>
</div>

<div class="panel mt-8 border-2 border-emerald-200 p-8 text-center shadow-lg">
    @if($mode === 'appointment')
        <p class="mb-1 text-sm uppercase tracking-wide text-slate-500">Reference</p>
        <p class="text-4xl font-bold text-emerald-600">{{ $appointment->reference_code }}</p>
        <p class="mt-4 text-sm text-slate-600">{{ optional($appointment->appointment_date)->format('F j, Y') }} at {{ optional($appointment->appointment_time)->format('g:i A') }}</p>
    @else
        <p class="mb-1 text-sm uppercase tracking-wide text-slate-500">Queue number</p>
        <p class="text-5xl font-bold text-emerald-600">#{{ $queueEntry->queue_number }}</p>
        <p class="mt-4 font-mono text-sm text-slate-600">{{ $queueEntry->reference_code }}</p>
    @endif
</div>

@if($mode === 'appointment')
    <div class="panel-soft mt-6 p-6 text-center">
        <p class="mb-1 text-slate-600">Name</p>
        <p class="text-3xl font-bold text-slate-800">{{ $appointment->display_name }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ $appointment->appointment_type ?: 'Scheduled appointment' }}</p>
    </div>
@else
    <div class="panel-soft mt-6 p-6 text-center">
        <p class="mb-1 text-slate-600">Position</p>
        <p class="text-3xl font-bold text-slate-800">{{ $position }}</p>
        @if($ahead > 0)
            <p class="mt-2 text-sm text-slate-500">{{ $ahead }} {{ Str::plural('person', $ahead) }} ahead of you</p>
        @else
            <p class="mt-2 text-sm font-medium text-emerald-600">You are next.</p>
        @endif
    </div>
@endif

<div class="panel mt-6 p-6 text-center">
    <p class="text-sm text-slate-500">Status</p>
    <p class="mt-2 text-lg font-semibold capitalize text-slate-700">
        {{ $mode === 'appointment' ? str_replace('_', ' ', $appointment->status) : str_replace('_', ' ', $queueEntry->status) }}
    </p>
    @if($mode === 'appointment')
        <p class="mt-2 text-xs text-slate-400">Save your reference code.</p>
    @else
        <p class="mt-2 text-xs text-slate-400">Save your number and reference code.</p>
    @endif
</div>
@endsection
