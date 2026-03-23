@extends('layouts.app')

@section('title', 'Book Appointment')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="{{ route('student.offices') }}" class="text-sm text-slate-600 hover:text-slate-800">&lt; Back to offices</a>
    </div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">Book appointment - {{ $office->name }}</h1>
    <p class="text-slate-600 mb-6">Choose a date and time. Office hours are based on the schedule below.</p>

    @if($office->schedules->isNotEmpty())
        <div class="bg-slate-50 rounded-lg p-4 mb-6 text-sm text-slate-700">
            <p class="font-medium text-slate-800 mb-2">Office schedule</p>
            <ul class="space-y-1">
                @foreach($office->schedules->sortBy('day_of_week') as $s)
                    @if($s->is_active)
                        <li>{{ \App\Models\OfficeSchedule::DAYS[$s->day_of_week] ?? $s->day_of_week }}: {{ \Carbon\Carbon::parse($s->open_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($s->close_time)->format('g:i A') }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('student.book.store', $office) }}" class="space-y-4">
        @csrf
        <div>
            <label for="appointment_date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
            <input type="date" name="appointment_date" id="appointment_date" value="{{ old('appointment_date') }}" required min="{{ date('Y-m-d') }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            @error('appointment_date')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="appointment_time" class="block text-sm font-medium text-slate-700 mb-1">Time</label>
            <input type="time" name="appointment_time" id="appointment_time" value="{{ old('appointment_time') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            @error('appointment_time')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="purpose" class="block text-sm font-medium text-slate-700 mb-1">Purpose (optional)</label>
            <textarea name="purpose" id="purpose" rows="2" placeholder="e.g. Transcript request" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('purpose') }}</textarea>
            @error('purpose')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">Request Appointment</button>
            <a href="{{ route('student.offices') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
