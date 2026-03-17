<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Office;
use App\Models\OfficeSchedule;
use App\Models\QueueEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    /** Public page when user scans QR for an office (no login). */
    public function office(string $slug)
    {
        $office = Office::query()
            ->when(app()->bound('current_tenant_id'), fn ($q) => $q->where('tenant_id', app('current_tenant_id')))
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        $office->load('schedules');
        $tenant = $office->tenant;
        $custom = [
            'primary_color' => $tenant ? $tenant->getSetting('theme.primary_color', '#2563eb') : '#059669',
            'app_name' => $tenant ? $tenant->getSetting('theme.app_name', config('app.name')) : config('app.name'),
            'logo_url' => $tenant ? $tenant->getSetting('theme.logo_url') : null,
            'queue_label' => $tenant ? $tenant->getSetting('customization.labels.queue', 'Queue') : 'Queue',
            'office_label' => $tenant ? $tenant->getSetting('customization.labels.office', 'Office') : 'Office',
            'appointment_label' => $tenant ? $tenant->getSetting('customization.labels.appointment', 'Appointment') : 'Appointment',
            'guest_queue_enabled' => $tenant ? $tenant->getSetting('customization.guest_queue', true) : true,
            'appointments_enabled' => $tenant ? $tenant->getSetting('customization.appointments', true) : true,
            'show_service_type' => $tenant ? $tenant->getSetting('customization.show_service_type', true) : true,
            'show_purpose_field' => $tenant ? $tenant->getSetting('customization.show_purpose_field', true) : true,
        ];
        return view('public.office', compact('office', 'custom'));
    }

    /** Get a queue number (guest: name + optional contact). */
    public function getQueue(Request $request, string $slug)
    {
        $office = Office::query()
            ->when(app()->bound('current_tenant_id'), fn ($q) => $q->where('tenant_id', app('current_tenant_id')))
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        if (! $office->is_active) {
            return back()->with('error', 'This office is not accepting queue numbers.');
        }

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'service_type' => ['nullable', 'string', 'max:100'],
        ], [], [
            'guest_email' => 'email address',
            'guest_phone' => 'phone number',
        ]);

        if (empty($validated['guest_email']) && empty($validated['guest_phone'])) {
            return back()->withErrors(['guest_email' => 'Please provide at least an email or phone so we can contact or remind you.'])->withInput();
        }

        $today = today();
        $count = $office->queueEntries()
            ->where('queue_date', $today)
            ->count();
        $nextNumber = $count + 1;

        if ($nextNumber > $office->max_daily_queue) {
            return back()->with('error', 'Daily queue limit reached for this office.');
        }

        $entry = QueueEntry::create([
            'tenant_id' => $office->tenant_id,
            'office_id' => $office->id,
            'user_id' => null,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'] ?? null,
            'guest_phone' => $validated['guest_phone'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'queue_number' => $nextNumber,
            'queue_date' => $today,
            'status' => QueueEntry::STATUS_WAITING,
            'reference_code' => strtoupper(Str::random(8)),
        ]);

        ActivityLog::log(
            $office->id,
            'queue_joined',
            $validated['guest_name'] . ' joined the queue as #' . $nextNumber,
            null,
            QueueEntry::class,
            $entry->id,
            ['queue_number' => $nextNumber, 'guest_name' => $validated['guest_name'], 'service_type' => $validated['service_type'] ?? null]
        );

        return redirect()->route('queue.track', ['referenceCode' => $entry->reference_code])
            ->with('success', "You are #{$nextNumber} in line. Save your reference code to track your position.");
    }

    /** Public queue tracker by reference code (no login). */
    public function track(string $referenceCode)
    {
        $entry = QueueEntry::with('office')
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        $position = $entry->office->queueEntries()
            ->where('queue_date', $entry->queue_date)
            ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->where('queue_number', '<=', $entry->queue_number)
            ->count();

        $ahead = $position - 1;

        return view('public.track', compact('entry', 'position', 'ahead'));
    }

    /** Book an appointment (guest). */
    public function bookAppointment(Request $request, string $slug)
    {
        $office = Office::query()
            ->when(app()->bound('current_tenant_id'), fn ($q) => $q->where('tenant_id', app('current_tenant_id')))
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        if (! $office->is_active) {
            return back()->with('error', 'This office is not accepting appointments.');
        }

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required'],
            'purpose' => ['nullable', 'string', 'max:500'],
        ], [], [
            'guest_email' => 'email address',
            'guest_phone' => 'phone number',
        ]);

        if (empty($validated['guest_email']) && empty($validated['guest_phone'])) {
            return back()->withErrors(['guest_email' => 'Please provide at least an email or phone so we can remind you of your appointment.'])->withInput();
        }

        $date = $validated['appointment_date'];
        $time = $validated['appointment_time'];

        $dayOfWeek = (int) date('w', strtotime($date));
        $schedule = OfficeSchedule::where('office_id', $office->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $schedule) {
            return back()->with('error', 'Office is closed on the selected date.');
        }

        $existing = Appointment::where('office_id', $office->id)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->exists();

        if ($existing) {
            return back()->with('error', 'That slot is already taken.');
        }

        $appointment = Appointment::create([
            'tenant_id' => $office->tenant_id,
            'office_id' => $office->id,
            'user_id' => null,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'] ?? null,
            'guest_phone' => $validated['guest_phone'] ?? null,
            'appointment_type' => $validated['appointment_type'] ?? null,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'status' => Appointment::STATUS_PENDING,
            'purpose' => $validated['purpose'] ?? null,
            'reference_code' => strtoupper(Str::random(8)),
        ]);

        ActivityLog::log(
            $office->id,
            'appointment_booked',
            $validated['guest_name'] . ' booked an appointment for ' . $date . ' at ' . $time,
            null,
            Appointment::class,
            $appointment->id,
            ['guest_name' => $validated['guest_name'], 'appointment_date' => $date, 'appointment_time' => $time]
        );

        return redirect()->route('queue.office', ['slug' => $office->slug])
            ->with('success', 'Appointment requested. Reference: ' . $appointment->reference_code . ' — we will confirm soon.');
    }
}
