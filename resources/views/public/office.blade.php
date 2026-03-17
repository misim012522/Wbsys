@extends('layouts.public')

@section('title', $office->name . ' — ' . $custom['app_name'])

@section('content')
@push('styles')
<style>
.public-primary { --tw-bg-opacity: 1; background-color: {{ $custom['primary_color'] }}; }
.public-primary:hover { filter: brightness(0.95); }
.public-ring:focus { --tw-ring-color: {{ $custom['primary_color'] }}; }
</style>
@endpush
<div class="mt-6">
    @if($custom['logo_url'])
        <img src="{{ $custom['logo_url'] }}" alt="" class="h-10 mb-3">
    @endif
    <h1 class="text-2xl font-bold text-slate-800">{{ $office->name }}</h1>
    @if($office->description)
        <p class="text-slate-600 mt-1">{{ $office->description }}</p>
    @endif
    <p class="text-sm text-slate-500 mt-2">Enter your details below. We’ll use your email or phone to contact or remind you.</p>
</div>

@if($custom['guest_queue_enabled'])
{{-- Get queue number --}}
<div class="mt-8 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Get {{ strtolower($custom['queue_label']) }} number</h2>
    <form method="POST" action="{{ route('queue.get', $office->slug) }}" class="space-y-4">
        @csrf
        <div>
            <label for="guest_name" class="block text-sm font-medium text-slate-700 mb-1">Your name <span class="text-red-500">*</span></label>
            <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400"
                placeholder="e.g. Juan Dela Cruz">
            @error('guest_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="guest_email" class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-slate-400">(for reminders)</span></label>
            <input type="email" name="guest_email" id="guest_email" value="{{ old('guest_email') }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400"
                placeholder="you@example.com">
            @error('guest_email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="guest_phone" class="block text-sm font-medium text-slate-700 mb-1">Phone <span class="text-slate-400">(for reminders)</span></label>
            <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400"
                placeholder="09XX XXX XXXX">
            @error('guest_phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-slate-500">Provide at least email or phone so we can contact or remind you.</p>
        @if($custom['show_service_type'])
        <div>
            <label for="service_type" class="block text-sm font-medium text-slate-700 mb-1">What do you need?</label>
            <select name="service_type" id="service_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400">
                @foreach(\App\Models\QueueEntry::serviceTypeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('service_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="w-full py-3 rounded-lg public-primary text-white font-medium">Get my number</button>
    </form>
</div>
@endif

@if($custom['appointments_enabled'])
{{-- Book appointment --}}
<div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Book an {{ strtolower($custom['appointment_label']) }}</h2>
    <form method="POST" action="{{ route('queue.book', $office->slug) }}" class="space-y-4">
        @csrf
        <div>
            <label for="book_guest_name" class="block text-sm font-medium text-slate-700 mb-1">Your name <span class="text-red-500">*</span></label>
            <input type="text" name="guest_name" id="book_guest_name" value="{{ old('guest_name') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400"
                placeholder="e.g. Juan Dela Cruz">
            @error('guest_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="book_guest_email" class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-slate-400">(for reminders)</span></label>
            <input type="email" name="guest_email" id="book_guest_email" value="{{ old('guest_email') }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400"
                placeholder="you@example.com">
            @error('guest_email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="book_guest_phone" class="block text-sm font-medium text-slate-700 mb-1">Phone <span class="text-slate-400">(for reminders)</span></label>
            <input type="tel" name="guest_phone" id="book_guest_phone" value="{{ old('guest_phone') }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400"
                placeholder="09XX XXX XXXX">
            @error('guest_phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-slate-500">Provide at least email or phone so we can remind you of your appointment.</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="appointment_date" class="block text-sm font-medium text-slate-700 mb-1">Date <span class="text-red-500">*</span></label>
                <input type="date" name="appointment_date" id="appointment_date" value="{{ old('appointment_date') }}" required min="{{ date('Y-m-d') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400">
                @error('appointment_date')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="appointment_time" class="block text-sm font-medium text-slate-700 mb-1">Time <span class="text-red-500">*</span></label>
                <input type="time" name="appointment_time" id="appointment_time" value="{{ old('appointment_time') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400">
                @error('appointment_time')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label for="appointment_type" class="block text-sm font-medium text-slate-700 mb-1">Type of {{ strtolower($custom['appointment_label']) }}</label>
            <select name="appointment_type" id="appointment_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400">
                @foreach(\App\Models\Appointment::appointmentTypeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('appointment_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if($custom['show_purpose_field'])
        <div>
            <label for="purpose" class="block text-sm font-medium text-slate-700 mb-1">Purpose / additional details (optional)</label>
            <textarea name="purpose" id="purpose" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 public-ring focus:border-slate-400" placeholder="e.g. Need transcript for scholarship">{{ old('purpose') }}</textarea>
        </div>
        @endif
        <button type="submit" class="w-full py-3 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50">Request {{ strtolower($custom['appointment_label']) }}</button>
    </form>
</div>
@endif

@if($office->schedules->isNotEmpty())
    <div class="mt-6 text-sm text-slate-500">
        <p class="font-medium text-slate-700">{{ $custom['office_label'] }} hours</p>
        <ul class="mt-1 space-y-0.5">
            @foreach($office->schedules->sortBy('day_of_week') as $s)
                @if($s->is_active)
                    <li>{{ \App\Models\OfficeSchedule::DAYS[$s->day_of_week] ?? $s->day_of_week }}: {{ \Carbon\Carbon::parse($s->open_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($s->close_time)->format('g:i A') }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif
@endsection
