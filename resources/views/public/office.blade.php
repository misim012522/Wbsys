@extends('layouts.public')

@section('title', $office->name . ' - ' . $custom['app_name'])

@section('content')
@push('styles')
<style>
.public-primary { --tw-bg-opacity: 1; background-color: {{ $custom['primary_color'] }}; }
.public-primary:hover { filter: brightness(0.95); }
.public-ring:focus { --tw-ring-color: {{ $custom['primary_color'] }}; }
</style>
@endpush

<section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-xl shadow-slate-200/50">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_26%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.14),_transparent_24%),linear-gradient(135deg,_#ffffff_0%,_#f8fffc_52%,_#eff6ff_100%)] px-6 py-8 sm:px-8">
        @if($custom['logo_url'])
            <img src="{{ $custom['logo_url'] }}" alt="" class="mb-3 h-10">
        @endif
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-700">{{ $custom['office_label'] }} access</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $office->name }}</h1>
        @if($office->description)
            <p class="mt-2 max-w-2xl text-slate-600">{{ $office->description }}</p>
        @endif
        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500">Enter your details below. We will use your email or phone to contact or remind you.</p>
    </div>
</section>

<div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Track an existing reference</h2>
            <p class="mt-1 text-sm text-slate-500">Already joined the queue or requested an appointment? Enter your reference code to check the latest status.</p>
        </div>
        <form method="GET" action="{{ route('tenant.track.lookup') }}" class="flex w-full flex-col gap-3 sm:flex-row lg:max-w-xl">
            <input
                type="text"
                name="reference_code"
                value="{{ request('reference_code') }}"
                required
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase tracking-[0.2em] focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="Enter reference code">
            <button type="submit" class="rounded-2xl public-primary px-5 py-3 font-medium text-white">Track now</button>
        </form>
    </div>
</div>

@if($custom['guest_queue_enabled'])
<div class="mt-8 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Get {{ strtolower($custom['queue_label']) }} number</h2>
    <form method="POST" action="{{ route('queue.get', $office->slug) }}" class="space-y-4">
        @csrf
        <div>
            <label for="guest_name" class="mb-1 block text-sm font-medium text-slate-700">Your name <span class="text-red-500">*</span></label>
            <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" required
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="e.g. Juan Dela Cruz">
            @error('guest_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="guest_email" class="mb-1 block text-sm font-medium text-slate-700">Email <span class="text-slate-400">(for reminders)</span></label>
            <input type="email" name="guest_email" id="guest_email" value="{{ old('guest_email') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="you@example.com">
            @error('guest_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="guest_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone <span class="text-slate-400">(for reminders)</span></label>
            <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="09XX XXX XXXX">
            @error('guest_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-slate-500">Provide at least email or phone so we can contact or remind you.</p>
        @if($custom['show_service_type'])
        <div>
            <label for="service_type" class="mb-1 block text-sm font-medium text-slate-700">What do you need?</label>
            <select name="service_type" id="service_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring">
                @foreach(\App\Models\QueueEntry::serviceTypeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('service_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="w-full rounded-2xl public-primary py-3 font-medium text-white">Get my number</button>
    </form>
</div>
@endif

@if($custom['appointments_enabled'])
<div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Book an {{ strtolower($custom['appointment_label']) }}</h2>
    <form method="POST" action="{{ route('queue.book', $office->slug) }}" class="space-y-4">
        @csrf
        <div>
            <label for="book_guest_name" class="mb-1 block text-sm font-medium text-slate-700">Your name <span class="text-red-500">*</span></label>
            <input type="text" name="guest_name" id="book_guest_name" value="{{ old('guest_name') }}" required
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="e.g. Juan Dela Cruz">
            @error('guest_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="book_guest_email" class="mb-1 block text-sm font-medium text-slate-700">Email <span class="text-slate-400">(for reminders)</span></label>
            <input type="email" name="guest_email" id="book_guest_email" value="{{ old('guest_email') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="you@example.com">
            @error('guest_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="book_guest_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone <span class="text-slate-400">(for reminders)</span></label>
            <input type="tel" name="guest_phone" id="book_guest_phone" value="{{ old('guest_phone') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring"
                placeholder="09XX XXX XXXX">
            @error('guest_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-slate-500">Provide at least email or phone so we can remind you of your appointment.</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="appointment_date" class="mb-1 block text-sm font-medium text-slate-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="appointment_date" id="appointment_date" value="{{ old('appointment_date') }}" required min="{{ date('Y-m-d') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring">
                @error('appointment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="appointment_time" class="mb-1 block text-sm font-medium text-slate-700">Time <span class="text-red-500">*</span></label>
                <input type="time" name="appointment_time" id="appointment_time" value="{{ old('appointment_time') }}" required
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring">
                @error('appointment_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label for="appointment_type" class="mb-1 block text-sm font-medium text-slate-700">Type of {{ strtolower($custom['appointment_label']) }}</label>
            <select name="appointment_type" id="appointment_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring">
                @foreach(\App\Models\Appointment::appointmentTypeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('appointment_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if($custom['show_purpose_field'])
        <div>
            <label for="purpose" class="mb-1 block text-sm font-medium text-slate-700">Purpose / additional details (optional)</label>
            <textarea name="purpose" id="purpose" rows="2" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-400 focus:ring-2 public-ring" placeholder="e.g. Need transcript for scholarship">{{ old('purpose') }}</textarea>
        </div>
        @endif
        <button type="submit" class="w-full rounded-2xl border border-slate-300 py-3 font-medium text-slate-700 hover:bg-slate-50">Request {{ strtolower($custom['appointment_label']) }}</button>
    </form>
</div>
@endif

@if($office->schedules->isNotEmpty())
    <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
        <p class="font-medium text-slate-700">{{ $custom['office_label'] }} hours</p>
        <ul class="mt-2 space-y-1">
            @foreach($office->schedules->sortBy('day_of_week') as $s)
                @if($s->is_active)
                    <li>{{ \App\Models\OfficeSchedule::DAYS[$s->day_of_week] ?? $s->day_of_week }}: {{ \Carbon\Carbon::parse($s->open_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($s->close_time)->format('g:i A') }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif
@endsection
