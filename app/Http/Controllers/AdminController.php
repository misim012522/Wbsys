<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\User;
use App\Notifications\AccountConfirmedNotification;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService
    ) {}

    private function tenantId(): ?int
    {
        return auth()->user()?->tenant_id;
    }

    private function officesQuery()
    {
        $q = Office::query();
        if ($tid = $this->tenantId()) {
            $q->forTenant($tid);
        }
        return $q;
    }

    public function dashboard()
    {
        $offices = $this->officesQuery()
            ->withCount(['queueEntries' => fn ($q) => $q->where('queue_date', today())->whereIn('status', ['waiting', 'called', 'serving'])])
            ->withCount(['appointments' => fn ($q) => $q->where('appointment_date', today())->whereIn('status', ['pending', 'confirmed'])])
            ->get();

        $baseQueue = QueueEntry::query();
        $baseAppt = Appointment::query();
        if ($tid = $this->tenantId()) {
            $baseQueue->forTenant($tid);
            $baseAppt->forTenant($tid);
        }
        $todayQueues = (clone $baseQueue)->where('queue_date', today())->whereIn('status', ['waiting', 'called', 'serving'])->count();
        $todayAppointments = (clone $baseAppt)->where('appointment_date', today())->whereIn('status', ['pending', 'confirmed'])->count();
        $completedToday = (clone $baseQueue)->where('queue_date', today())->where('status', 'completed')->count()
            + (clone $baseAppt)->where('appointment_date', today())->where('status', 'completed')->count();

        return view('admin.dashboard', compact('offices', 'todayQueues', 'todayAppointments', 'completedToday'));
    }

    /** Page listing offices with QR codes for scanning. */
    public function qrCodes()
    {
        $offices = $this->officesQuery()->active()->orderedByName()->get();
        return view('admin.qr', compact('offices'));
    }

    /** Generate QR code image for an office (URL that end users scan). Uses APP_URL so QR works from any device. */
    public function qrCodeImage(Office $office): Response
    {
        $url = $this->qrCodeService->queueOfficeUrl($office->slug);
        $result = $this->qrCodeService->build($url, true);
        return response($result->getString())
            ->header('Content-Type', $result->getMimeType());
    }

    /** Admin office panel: serve queue and appointments for one office. */
    public function serveOffice(Office $office)
    {
        $todayQueue = $office->queueEntries()->activeToday()->orderBy('queue_number')->get();
        $todayAppointments = $office->appointments()->upcomingToday()->orderBy('appointment_time')->get();
        $currentServing = $office->queueEntries()
            ->today()
            ->whereIn('status', [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->orderBy('queue_number')
            ->first();

        return view('admin.serve', compact('office', 'todayQueue', 'todayAppointments', 'currentServing'));
    }

    public function callNext(Office $office)
    {
        $next = $office->queueEntries()
            ->today()
            ->where('status', QueueEntry::STATUS_WAITING)
            ->orderBy('queue_number')
            ->first();

        if (! $next) {
            return back()->with('info', 'No one waiting in queue.');
        }

        $next->update([
            'status' => QueueEntry::STATUS_CALLED,
            'called_at' => now(),
        ]);

        return back()->with('success', "Now serving #{$next->queue_number}");
    }

    public function updateQueueStatus(Request $request, QueueEntry $queueEntry)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:serving,completed,cancelled,no_show'],
        ]);

        $data = ['status' => $validated['status']];
        if ($validated['status'] === QueueEntry::STATUS_SERVING) {
            $data['called_at'] = $queueEntry->called_at ?? now();
        }
        if ($validated['status'] === QueueEntry::STATUS_COMPLETED) {
            $data['served_at'] = now();
        }

        $queueEntry->update($data);
        return back()->with('success', 'Queue status updated.');
    }

    public function acceptAppointment(Appointment $appointment)
    {
        $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);
        return back()->with('success', 'Appointment confirmed.');
    }

    public function completeAppointment(Appointment $appointment)
    {
        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);
        return back()->with('success', 'Appointment completed.');
    }

    public function cancelAppointment(Appointment $appointment)
    {
        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);
        return back()->with('success', 'Appointment cancelled.');
    }

    public function offices()
    {
        $offices = $this->officesQuery()->with('schedules')->get();
        return view('admin.offices.index', compact('offices'));
    }

    public function createOffice()
    {
        return view('admin.offices.create');
    }

    public function storeOffice(Request $request)
    {
        $tenantId = $this->tenantId();
        $slugRule = ['required', 'string', 'max:255'];
        if ($tenantId) {
            $slugRule[] = \Illuminate\Validation\Rule::unique('offices', 'slug')->where('tenant_id', $tenantId);
        } else {
            $slugRule[] = 'unique:offices,slug';
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'max_daily_queue' => ['nullable', 'integer', 'min:1', 'max:500'],
            'serving_time_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        if ($tenantId) {
            $validated['tenant_id'] = $tenantId;
        }
        Office::create($validated);
        return redirect()->route('admin.offices')->with('success', 'Office created.');
    }

    public function editOffice(Office $office)
    {
        $office->load('schedules');
        return view('admin.offices.edit', compact('office'));
    }

    public function updateOffice(Request $request, Office $office)
    {
        $tenantId = $this->tenantId();
        $slugRule = ['required', 'string', 'max:255'];
        if ($tenantId) {
            $slugRule[] = \Illuminate\Validation\Rule::unique('offices', 'slug')->where('tenant_id', $tenantId)->ignore($office->id);
        } else {
            $slugRule[] = 'unique:offices,slug,' . $office->id;
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'max_daily_queue' => ['nullable', 'integer', 'min:1', 'max:500'],
            'serving_time_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $office->update($validated);
        return redirect()->route('admin.offices')->with('success', 'Office updated.');
    }

    public function reports(Request $request)
    {
        $date = $request->get('date', today()->toDateString());
        $officeId = $request->get('office_id');

        $query = QueueEntry::with(['office'])->where('queue_date', $date);
        if ($tid = $this->tenantId()) {
            $query->forTenant($tid);
        }
        if ($officeId) {
            $query->where('office_id', $officeId);
        }
        $queueEntries = $query->orderBy('office_id')->orderBy('queue_number')->get();

        $appQuery = Appointment::with(['office'])->where('appointment_date', $date);
        if ($tid = $this->tenantId()) {
            $appQuery->forTenant($tid);
        }
        if ($officeId) {
            $appQuery->where('office_id', $officeId);
        }
        $appointments = $appQuery->orderBy('office_id')->orderBy('appointment_time')->get();

        $offices = $this->officesQuery()->orderBy('name')->get();

        return view('admin.reports', compact('queueEntries', 'appointments', 'offices', 'date', 'officeId'));
    }

    /** List approved staff accounts (office users), excluding archived. */
    public function usersIndex()
    {
        $users = User::where('role', User::ROLE_OFFICE_STAFF)
            ->whereNotNull('approved_at')
            ->notArchived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->with('office')
            ->orderBy('name')
            ->get();
        return view('admin.users.index', compact('users'));
    }

    /** List archived staff accounts. */
    public function archivedAccounts()
    {
        $users = User::where('role', User::ROLE_OFFICE_STAFF)
            ->archived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->with('office')
            ->orderByDesc('archived_at')
            ->get();
        return view('admin.users.archived', compact('users'));
    }

    /** Archive a staff account (soft archive via archived_at). */
    public function archiveUser(User $user)
    {
        if ($user->role !== User::ROLE_OFFICE_STAFF) {
            return back()->withErrors(['user' => 'Only office staff accounts can be archived.']);
        }
        if ($user->archived_at !== null) {
            return back()->with('info', 'That account is already archived.');
        }
        $user->update(['archived_at' => now()]);
        return redirect()->route('admin.users.archived')->with('success', "Account for {$user->name} has been archived.");
    }

    /** Recover an archived staff account. */
    public function recoverUser(User $user)
    {
        if ($user->role !== User::ROLE_OFFICE_STAFF) {
            return back()->withErrors(['user' => 'Only office staff accounts can be recovered.']);
        }
        if ($user->archived_at === null) {
            return back()->with('info', 'That account is not archived.');
        }
        $user->update(['archived_at' => null]);
        return redirect()->route('admin.users.index')->with('success', "Account for {$user->name} has been recovered.");
    }

    /** Permanently delete a user (staff only). */
    public function destroyUser(User $user)
    {
        if ($user->role === User::ROLE_ADMIN) {
            return back()->withErrors(['user' => 'Admin accounts cannot be deleted.']);
        }
        if ($user->role !== User::ROLE_OFFICE_STAFF) {
            return back()->withErrors(['user' => 'Only office staff accounts can be deleted.']);
        }
        $name = $user->name;
        $user->delete();
        return redirect()->route('admin.users.archived')->with('success', "Account for {$name} has been permanently deleted.");
    }

    /** List pending accounts (staff awaiting admin approval). */
    public function pendingAccounts()
    {
        $users = User::where('role', User::ROLE_OFFICE_STAFF)
            ->whereNull('approved_at')
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->with('office')
            ->orderBy('created_at')
            ->get();
        return view('admin.users.pending', compact('users'));
    }

    /** Confirm a pending account: set approved_at, email_verified_at, and send confirmation email. */
    public function approveUser(User $user)
    {
        if ($user->approved_at !== null) {
            return back()->with('info', 'That account is already approved.');
        }
        if ($user->role !== User::ROLE_OFFICE_STAFF) {
            return back()->withErrors(['user' => 'Only office staff accounts can be approved.']);
        }

        $user->approved_at = now();
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();
        $user->notify(new AccountConfirmedNotification());

        return back()->with('success', "Account for {$user->name} has been confirmed. A confirmation email has been sent to {$user->email}.");
    }
}
