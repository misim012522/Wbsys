@extends('layouts.app')

@section('title', 'Customization')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">Customization</h1>
    <p class="text-slate-600 mb-6">Customize design and functions for your organization. Changes apply to the public queue page and branding.</p>

    <form method="POST" action="{{ route('admin.customization.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Branding (design) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Branding</h2>
            <div class="space-y-4">
                <div>
                    <label for="app_name" class="block text-sm font-medium text-slate-700 mb-1">App / site name</label>
                    <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $appName) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="e.g. QueueLess">
                </div>
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-slate-700 mb-1">Primary color</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="primary_color" id="primary_color" value="{{ $primaryColor }}"
                            class="h-10 w-14 rounded border border-slate-300 cursor-pointer">
                        <input type="text" name="primary_color_text" id="primary_color_text" value="{{ $primaryColor }}"
                            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="#2563eb" maxlength="7">
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Used for buttons and links on public pages.</p>
                </div>
                <div>
                    <label for="logo_url" class="block text-sm font-medium text-slate-700 mb-1">Logo URL</label>
                    <input type="url" name="logo_url" id="logo_url" value="{{ old('logo_url', $logoUrl) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="https://example.com/logo.png">
                    @if($logoUrl)
                        <p class="mt-2"><img src="{{ $logoUrl }}" alt="Logo" class="h-10 object-contain"></p>
                    @endif
                </div>
                <div>
                    <label for="support_url" class="block text-sm font-medium text-slate-700 mb-1">Support / help URL</label>
                    <input type="url" name="support_url" id="support_url" value="{{ old('support_url', $supportUrl) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="https://support.example.com">
                </div>
            </div>
        </div>

        {{-- Feature toggles (functions) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Features</h2>
            <p class="text-sm text-slate-600 mb-4">Turn on or off what visitors see on the public office page.</p>
            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="guest_queue" value="0">
                    <input type="checkbox" name="guest_queue" value="1" {{ $guestQueueEnabled ? 'checked' : '' }}
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700">Allow &quot;Get queue number&quot; (walk-in)</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="appointments" value="0">
                    <input type="checkbox" name="appointments" value="1" {{ $appointmentsEnabled ? 'checked' : '' }}
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700">Allow &quot;Book appointment&quot;</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="show_service_type" value="0">
                    <input type="checkbox" name="show_service_type" value="1" {{ $showServiceType ? 'checked' : '' }}
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700">Show &quot;What do you need?&quot; (service type) in queue form</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="show_purpose_field" value="0">
                    <input type="checkbox" name="show_purpose_field" value="1" {{ $showPurposeField ? 'checked' : '' }}
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700">Show &quot;Purpose&quot; field in appointment form</span>
                </label>
            </div>
        </div>

        {{-- Custom labels --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Labels</h2>
            <p class="text-sm text-slate-600 mb-4">Override default words (e.g. &quot;Queue&quot; → &quot;Line&quot;, &quot;Office&quot; → &quot;Counter&quot;).</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="label_queue" class="block text-sm font-medium text-slate-700 mb-1">Queue</label>
                    <input type="text" name="label_queue" id="label_queue" value="{{ old('label_queue', $queueLabel) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="Queue">
                </div>
                <div>
                    <label for="label_office" class="block text-sm font-medium text-slate-700 mb-1">Office</label>
                    <input type="text" name="label_office" id="label_office" value="{{ old('label_office', $officeLabel) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="Office">
                </div>
                <div>
                    <label for="label_appointment" class="block text-sm font-medium text-slate-700 mb-1">Appointment</label>
                    <input type="text" name="label_appointment" id="label_appointment" value="{{ old('label_appointment', $appointmentLabel) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="Appointment">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">Save customization</button>
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('primary_color').addEventListener('input', function() {
    document.getElementById('primary_color_text').value = this.value;
});
document.getElementById('primary_color_text').addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        document.getElementById('primary_color').value = this.value;
    }
});
document.querySelector('form').addEventListener('submit', function() {
    var hex = document.getElementById('primary_color_text').value;
    if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        document.getElementById('primary_color').value = hex;
    }
});
</script>
@endsection
