<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    private ?bool $hasAssignedStaffColumn = null;

    private function assignedStaffColumnAvailable(): bool
    {
        if ($this->hasAssignedStaffColumn !== null) {
            return $this->hasAssignedStaffColumn;
        }

        return $this->hasAssignedStaffColumn = Schema::connection('tenant')->hasColumn('queue_entries', 'assigned_staff_user_id');
    }

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
            'guest_queue_enabled' => $tenant ? $tenant->getSetting('customization.guest_queue', true) : true,
            'show_service_type' => $tenant ? $tenant->getSetting('customization.show_service_type', true) : true,
        ];

        return view('public.office', [
            'office' => $office,
            'custom' => $custom,
            'preferredStaff' => null,
        ]);
    }

    /** Public page when user scans an office-staff-specific QR (signed URL). */
    public function officeForStaff(string $slug, int $userId)
    {
        $office = Office::query()
            ->when(app()->bound('current_tenant_id'), fn ($q) => $q->where('tenant_id', app('current_tenant_id')))
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = User::query()
            ->where('id', $userId)
            ->where('role', User::ROLE_OFFICE_STAFF)
            ->where('office_id', $office->id)
            ->where('tenant_id', $office->tenant_id)
            ->firstOrFail();

        $office->load('schedules');
        $tenant = $office->tenant;
        $custom = [
            'primary_color' => $tenant ? $tenant->getSetting('theme.primary_color', '#2563eb') : '#059669',
            'app_name' => $tenant ? $tenant->getSetting('theme.app_name', config('app.name')) : config('app.name'),
            'logo_url' => $tenant ? $tenant->getSetting('theme.logo_url') : null,
            'queue_label' => $tenant ? $tenant->getSetting('customization.labels.queue', 'Queue') : 'Queue',
            'office_label' => $tenant ? $tenant->getSetting('customization.labels.office', 'Office') : 'Office',
            'guest_queue_enabled' => $tenant ? $tenant->getSetting('customization.guest_queue', true) : true,
            'show_service_type' => $tenant ? $tenant->getSetting('customization.show_service_type', true) : true,
        ];

        return view('public.office', [
            'office' => $office,
            'custom' => $custom,
            'preferredStaff' => $user,
        ]);
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

        if (! $this->guestQueueEnabled($office)) {
            return back()->with('error', 'This office is not accepting queue numbers right now.');
        }
        $canUseAssignedStaff = $this->assignedStaffColumnAvailable();

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'preferred_staff_user_id' => ['nullable', 'integer'],
        ], [], [
            'guest_email' => 'email address',
            'guest_phone' => 'phone number',
        ]);

        $preferredStaffId = isset($validated['preferred_staff_user_id'])
            ? (int) $validated['preferred_staff_user_id']
            : null;

        $assignedStaff = null;
        if ($canUseAssignedStaff && $preferredStaffId) {
            $assignedStaff = User::query()
                ->where('id', $preferredStaffId)
                ->where('role', User::ROLE_OFFICE_STAFF)
                ->where('tenant_id', $office->tenant_id)
                ->where('office_id', $office->id)
                ->first();
        }

        if (empty($validated['guest_email']) && empty($validated['guest_phone'])) {
            return back()->withErrors(['guest_email' => 'Please provide at least an email or phone so we can contact or remind you.'])->withInput();
        }

        $today = today();
        $entry = DB::transaction(function () use ($office, $today, $validated, $assignedStaff, $canUseAssignedStaff) {
            $currentMax = QueueEntry::query()
                ->where('office_id', $office->id)
                ->where('queue_date', $today)
                ->lockForUpdate()
                ->max('queue_number') ?? 0;

            $nextNumber = $currentMax + 1;

            if ($nextNumber > $office->max_daily_queue) {
                return null;
            }

            $payload = [
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
                'reference_code' => $this->generateUniqueReferenceCode(QueueEntry::class),
            ];

            if ($canUseAssignedStaff) {
                $payload['assigned_staff_user_id'] = $assignedStaff?->id;
            }

            return QueueEntry::create($payload);
        });

        if (! $entry) {
            return back()->with('error', 'Daily queue limit reached for this office.');
        }

        ActivityLog::log(
            $office->id,
            'queue_joined',
            $validated['guest_name'].' joined the queue as #'.$entry->queue_number,
            null,
            QueueEntry::class,
            $entry->id,
            [
                'queue_number' => $entry->queue_number,
                'guest_name' => $validated['guest_name'],
                'service_type' => $validated['service_type'] ?? null,
                'assigned_staff_user_id' => $assignedStaff?->id,
            ]
        );

        return redirect()->route('queue.track', ['referenceCode' => $entry->reference_code])
            ->with('success', "You are #{$entry->queue_number} in line. Save your reference code to track your position.");
    }

    /** Public queue tracker by reference code (no login). */
    public function track(string $referenceCode)
    {
        $queueEntry = QueueEntry::with('office')
            ->when(app()->bound('current_tenant_id'), fn ($q) => $q->where('tenant_id', app('current_tenant_id')))
            ->where('reference_code', $referenceCode)
            ->first();

        abort_unless($queueEntry, 404);

        $position = $queueEntry->office->queueEntries()
            ->where('queue_date', $queueEntry->queue_date)
            ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->where('queue_number', '<=', $queueEntry->queue_number)
            ->count();

        return view('public.track', [
            'queueEntry' => $queueEntry,
            'position' => $position,
            'ahead' => $position - 1,
        ]);
    }

    private function guestQueueEnabled(Office $office): bool
    {
        return (bool) optional($office->tenant)->getSetting('customization.guest_queue', true);
    }

    private function generateUniqueReferenceCode(string $modelClass): string
    {
        do {
            $referenceCode = strtoupper(Str::random(8));
        } while ($modelClass::query()->where('reference_code', $referenceCode)->exists());

        return $referenceCode;
    }
}
