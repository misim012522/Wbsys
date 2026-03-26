@extends('layouts.public')

@section('title', ($mode === 'appointment' ? 'Appointment status' : 'Queue status') . ' - QueueLess')

@section('content')
@php
    $office = $mode === 'appointment' ? $appointment->office : $queueEntry->office;
@endphp

<div class="mt-6 text-center">
    <h1 class="text-xl font-bold text-slate-800">{{ $office->name }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $mode === 'appointment' ? 'Your appointment status' : 'Your queue status' }}</p>
</div>

<div class="mt-8 rounded-2xl border-2 border-emerald-200 bg-white p-8 text-center shadow-lg">
    @if($mode === 'appointment')
        <p class="mb-1 text-sm uppercase tracking-wide text-slate-500">Appointment reference</p>
        <p class="text-4xl font-bold text-emerald-600">{{ $appointment->reference_code }}</p>
        <p class="mt-4 text-sm text-slate-600">{{ optional($appointment->appointment_date)->format('F j, Y') }} at {{ optional($appointment->appointment_time)->format('g:i A') }}</p>
    @else
        <p class="mb-1 text-sm uppercase tracking-wide text-slate-500">Your number</p>
        <p class="text-5xl font-bold text-emerald-600">#{{ $queueEntry->queue_number }}</p>
        <p class="mt-4 font-mono text-sm text-slate-600">{{ $queueEntry->reference_code }}</p>
    @endif
</div>

@if($mode === 'appointment')
    <div class="mt-6 rounded-xl bg-slate-100 p-6 text-center">
        <p class="mb-1 text-slate-600">Appointment for</p>
        <p class="text-3xl font-bold text-slate-800">{{ $appointment->display_name }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ $appointment->appointment_type ?: 'Scheduled appointment' }}</p>
    </div>
@else
    <div class="mt-6 rounded-xl bg-slate-100 p-6 text-center">
        <p class="mb-1 text-slate-600">Position in line</p>
        <p class="text-3xl font-bold text-slate-800">{{ $position }}</p>
        @if($ahead > 0)
            <p class="mt-2 text-sm text-slate-500">{{ $ahead }} {{ Str::plural('person', $ahead) }} ahead of you</p>
        @else
            <p class="mt-2 text-sm font-medium text-emerald-600">You're next or being served!</p>
        @endif
    </div>
@endif

<div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 text-center">
    <p class="text-sm text-slate-500">Status</p>
    <p class="mt-2 text-lg font-semibold capitalize text-slate-700">
        {{ $mode === 'appointment' ? str_replace('_', ' ', $appointment->status) : str_replace('_', ' ', $queueEntry->status) }}
    </p>
    @if($mode === 'appointment')
        <p class="mt-2 text-xs text-slate-400">Save this reference code so you can check back for confirmation updates later.</p>
    @else
        <p class="mt-2 text-xs text-slate-400">Save this page or your reference code to check back later.</p>
    @endif
</div>
@endsection
