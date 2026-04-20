@extends('layouts.app')

@section('title', 'Customization')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Customization',
    'description' => 'Control branding, tenant dashboard profile, labels, and visible public features for this tenant workspace.',
])

<div class="max-w-6xl overflow-x-hidden">
    <form method="POST" action="{{ route('admin.customization.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="min-w-0 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Branding</h2>
                <div class="space-y-4">
                    <div>
                        <label for="app_name" class="mb-1 block text-sm font-medium text-slate-700">App / site name</label>
                        <input
                            type="text"
                            name="app_name"
                            id="app_name"
                            value="{{ old('app_name', $appName) }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            placeholder="e.g. QueueLess"
                        >
                    </div>

                    <div>
                        <label for="primary_color" class="mb-1 block text-sm font-medium text-slate-700">Primary color</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                name="primary_color"
                                id="primary_color"
                                value="{{ $primaryColor }}"
                                class="h-10 w-14 cursor-pointer rounded border border-slate-300"
                            >
                            <input
                                type="text"
                                name="primary_color_text"
                                id="primary_color_text"
                                value="{{ $primaryColor }}"
                                class="flex-1 rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                                placeholder="#2563eb"
                                maxlength="7"
                            >
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Used for buttons and links on public pages.</p>
                    </div>

                    <div>
                        <label for="logo_url" class="mb-1 block text-sm font-medium text-slate-700">Logo URL</label>
                        <input
                            type="url"
                            name="logo_url"
                            id="logo_url"
                            value="{{ old('logo_url', $logoUrl) }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            placeholder="https://example.com/logo.png"
                        >
                        @if($logoUrl)
                            <p class="mt-2"><img src="{{ $logoUrl }}" alt="Logo" class="h-10 object-contain"></p>
                        @endif
                    </div>

                    <div>
                        <label for="support_url" class="mb-1 block text-sm font-medium text-slate-700">Support / help URL</label>
                        <input
                            type="url"
                            name="support_url"
                            id="support_url"
                            value="{{ old('support_url', $supportUrl) }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            placeholder="https://support.example.com"
                        >
                    </div>
                </div>
            </div>

            <div class="min-w-0 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Features</h2>
                <p class="mb-4 text-sm text-slate-600">Turn on or off what visitors see on the public office page.</p>
                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="guest_queue" value="0">
                        <input type="checkbox" name="guest_queue" value="1" {{ $guestQueueEnabled ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Allow "Get queue number" (walk-in)</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="appointments" value="0">
                        <input type="checkbox" name="appointments" value="1" {{ $appointmentsEnabled ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Allow "Book appointment"</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="show_service_type" value="0">
                        <input type="checkbox" name="show_service_type" value="1" {{ $showServiceType ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Show "What do you need?" service type in queue form</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="show_purpose_field" value="0">
                        <input type="checkbox" name="show_purpose_field" value="1" {{ $showPurposeField ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Show "Purpose" field in appointment form</span>
                    </label>
                </div>
            </div>

            <div class="min-w-0 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Dashboard Profile</h2>
                <p class="mb-4 text-sm text-slate-600">Choose which tenant dashboard style fits this workspace.</p>
                <div>
                    <label for="dashboard_profile" class="mb-1 block text-sm font-medium text-slate-700">Profile</label>
                    <select
                        name="dashboard_profile"
                        id="dashboard_profile"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                    >
                        @foreach($dashboardProfiles as $value => $label)
                            <option value="{{ $value }}" @selected(old('dashboard_profile', $dashboardProfile) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Example: use `Registrar` for document-heavy workflows, or `Cashier` for payment counters.</p>
                </div>
            </div>

            <div class="min-w-0 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Labels</h2>
                <p class="mb-4 text-sm text-slate-600">Override default words used across the tenant workspace.</p>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <label for="label_queue" class="mb-1 block text-sm font-medium text-slate-700">Queue</label>
                        <input
                            type="text"
                            name="label_queue"
                            id="label_queue"
                            value="{{ old('label_queue', $queueLabel) }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            placeholder="Queue"
                        >
                    </div>
                    <div>
                        <label for="label_office" class="mb-1 block text-sm font-medium text-slate-700">Office</label>
                        <input
                            type="text"
                            name="label_office"
                            id="label_office"
                            value="{{ old('label_office', $officeLabel) }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            placeholder="Office"
                        >
                    </div>
                    <div>
                        <label for="label_appointment" class="mb-1 block text-sm font-medium text-slate-700">Appointment</label>
                        <input
                            type="text"
                            name="label_appointment"
                            id="label_appointment"
                            value="{{ old('label_appointment', $appointmentLabel) }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                            placeholder="Appointment"
                        >
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">Save customization</button>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>

@include('admin._workspace-nav-footer')

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
