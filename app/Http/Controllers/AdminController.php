<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\User;
use App\Notifications\AccountConfirmedNotification;
use App\Services\TenantPlanEnforcer;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService,
        private TenantPlanEnforcer $tenantPlanEnforcer,
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

    private function defaultOffice(): ?Office
    {
        return $this->officesQuery()
            ->orderedByName()
            ->first();
    }

    private function findTenantUserOrFail($userId): User
    {
        $query = User::query();

        if ($tid = $this->tenantId()) {
            $query->forTenant($tid);
        }

        return $query->findOrFail($userId);
    }

    private function currentTenant(): ?\App\Models\Tenant
    {
        return app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    }

    public function dashboard()
    {
        $office = $this->defaultOffice();

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

        return view('admin.dashboard', compact('office', 'todayQueues', 'todayAppointments', 'completedToday'));
    }

    /** QR code for the tenant's built-in workspace office. */
    public function qrCodes()
    {
        $office = $this->defaultOffice();
        return view('admin.qr', compact('office'));
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
        return redirect()->route('admin.dashboard')
            ->with('info', 'Office management is no longer needed because each tenant now uses a single built-in workspace.');
    }

    public function createOffice()
    {
        return redirect()->route('admin.dashboard')
            ->with('info', 'Office creation is disabled because each tenant now uses a single built-in workspace.');
    }

    public function storeOffice(Request $request)
    {
        return redirect()->route('admin.dashboard')
            ->with('info', 'Office creation is disabled because each tenant now uses a single built-in workspace.');
    }

    public function editOffice(Office $office)
    {
        return redirect()->route('admin.dashboard')
            ->with('info', 'Office editing is disabled because each tenant now uses a single built-in workspace.');
    }

    public function updateOffice(Request $request, Office $office)
    {
        return redirect()->route('admin.dashboard')
            ->with('info', 'Office editing is disabled because each tenant now uses a single built-in workspace.');
    }

    public function reports(Request $request)
    {
        if (! $this->tenantPlanEnforcer->hasFeature($this->currentTenant(), 'reports')) {
            return redirect()->route('admin.dashboard')->with('error', 'Reports are not available on your current subscription plan.');
        }

        $date = $request->get('date', today()->toDateString());
        $query = QueueEntry::with(['office'])->where('queue_date', $date);
        if ($tid = $this->tenantId()) {
            $query->forTenant($tid);
        }
        $queueEntries = $query->orderBy('office_id')->orderBy('queue_number')->get();

        $appQuery = Appointment::with(['office'])->where('appointment_date', $date);
        if ($tid = $this->tenantId()) {
            $appQuery->forTenant($tid);
        }
        $appointments = $appQuery->orderBy('office_id')->orderBy('appointment_time')->get();

        return view('admin.reports', compact('queueEntries', 'appointments', 'date'));
    }

    /** List approved non-admin tenant user accounts, excluding archived. */
    public function usersIndex()
    {
        $users = User::where('role', '!=', User::ROLE_ADMIN)
            ->whereNotNull('approved_at')
            ->notArchived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->with('office')
            ->orderBy('role')
            ->orderBy('name')
            ->get();
        return view('admin.users.index', compact('users'));
    }

    /** List archived non-admin accounts. */
    public function archivedAccounts()
    {
        $users = User::where('role', '!=', User::ROLE_ADMIN)
            ->archived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->with('office')
            ->orderBy('role')
            ->orderByDesc('archived_at')
            ->get();
        return view('admin.users.archived', compact('users'));
    }

    /** Archive a non-admin account (soft archive via archived_at). */
    public function archiveUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->role === User::ROLE_ADMIN) {
            return back()->withErrors(['user' => 'Admin accounts cannot be archived.']);
        }
        if ($user->archived_at !== null) {
            return back()->with('info', 'That account is already archived.');
        }
        $user->update(['archived_at' => now()]);
        return redirect()->route('admin.users.archived')->with('success', "Account for {$user->name} has been archived.");
    }

    /** Recover an archived non-admin account. */
    public function recoverUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->role === User::ROLE_ADMIN) {
            return back()->withErrors(['user' => 'Admin accounts cannot be recovered here.']);
        }
        if ($user->archived_at === null) {
            return back()->with('info', 'That account is not archived.');
        }
        $user->update(['archived_at' => null]);
        return redirect()->route('admin.users.index')->with('success', "Account for {$user->name} has been recovered.");
    }

    /** Permanently delete a non-admin user. */
    public function destroyUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->role === User::ROLE_ADMIN) {
            return back()->withErrors(['user' => 'Admin accounts cannot be deleted.']);
        }
        $name = $user->name;
        $user->delete();
        return redirect()->route('admin.users.archived')->with('success', "Account for {$name} has been permanently deleted.");
    }

    /** List pending non-admin accounts awaiting admin approval. */
    public function pendingAccounts()
    {
        $users = User::where('role', '!=', User::ROLE_ADMIN)
            ->whereNull('approved_at')
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->with('office')
            ->orderBy('role')
            ->orderBy('created_at')
            ->get();
        return view('admin.users.pending', compact('users'));
    }

    /** Confirm a pending account: set approved_at, email_verified_at, and send confirmation email. */
    public function approveUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->approved_at !== null) {
            return back()->with('info', 'That account is already approved.');
        }
        if ($user->role === User::ROLE_ADMIN) {
            return back()->withErrors(['user' => 'Admin accounts cannot be approved through this screen.']);
        }

        $user->approved_at = now();
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();
        $user->notify(new AccountConfirmedNotification());

        return back()->with('success', "Account for {$user->name} has been confirmed. A confirmation email has been sent to {$user->email}.");
    }
}
