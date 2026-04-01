<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\Permission;
use App\Models\QueueEntry;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountConfirmedNotification;
use App\Services\TenantPlanEnforcer;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    private const OFFICE_STAFF_PAGE_SIZE = 10;

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

    private function applyOfficeStaffSearch($query, string $search)
    {
        return $query->when($search !== '', function ($builder) use ($search) {
            $builder->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%');
            });
        });
    }

    private function applyOfficeStaffFilters($query, string $search, int $officeId)
    {
        return $this->applyOfficeStaffSearch($query, $search)
            ->when($officeId > 0, fn ($builder) => $builder->where('office_id', $officeId));
    }

    private function officeStaffFilterData(Request $request): array
    {
        return [
            'search' => trim((string) $request->string('search')),
            'officeId' => $request->integer('office_id'),
            'offices' => $this->officesQuery()->orderedByName()->get(),
            'roles' => $this->assignableRoles(),
        ];
    }

    private function assignableRoles()
    {
        return Role::query()
            ->forTenant($this->tenantId())
            ->active()
            ->where('slug', '!=', User::ROLE_TENANT_ADMIN)
            ->orderByRaw("CASE WHEN slug = ? THEN 0 ELSE 1 END", [User::ROLE_OFFICE_STAFF])
            ->orderBy('name')
            ->get();
    }

    private function availablePermissions()
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->module ?: 'general');
    }

    private function resolveAssignableRole(string $slug): ?Role
    {
        return Role::query()
            ->forTenant($this->tenantId())
            ->active()
            ->where('slug', $slug)
            ->where('slug', '!=', User::ROLE_TENANT_ADMIN)
            ->first();
    }

    private function reportData(string $date, int $officeId = 0): array
    {
        $queueQuery = QueueEntry::with(['office'])->where('queue_date', $date);
        $appointmentQuery = Appointment::with(['office'])->where('appointment_date', $date);

        if ($tid = $this->tenantId()) {
            $queueQuery->forTenant($tid);
            $appointmentQuery->forTenant($tid);
        }

        if ($officeId > 0) {
            $queueQuery->where('office_id', $officeId);
            $appointmentQuery->where('office_id', $officeId);
        }

        $queueEntries = $queueQuery->orderBy('office_id')->orderBy('queue_number')->get();
        $appointments = $appointmentQuery->orderBy('office_id')->orderBy('appointment_time')->get();

        return compact('queueEntries', 'appointments');
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

    public function profile()
    {
        $tenant = $this->currentTenant();
        $admin = auth()->user();
        $subscription = $tenant?->subscriptions()->latest('id')->first();
        $workspaceUrl = $tenant ? \App\Support\TenantUrl::workspace($tenant) : null;
        $loginUrl = $tenant ? \App\Support\TenantUrl::login($tenant) : null;

        return view('admin.profile', compact('tenant', 'admin', 'subscription', 'workspaceUrl', 'loginUrl'));
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
        $officeId = $request->integer('office_id');
        ['queueEntries' => $queueEntries, 'appointments' => $appointments] = $this->reportData($date, $officeId);
        $offices = $this->officesQuery()->orderedByName()->get();

        return view('admin.reports', compact('queueEntries', 'appointments', 'date', 'offices', 'officeId'));
    }

    public function downloadReport(Request $request): StreamedResponse|RedirectResponse|Response
    {
        if (! $this->tenantPlanEnforcer->hasFeature($this->currentTenant(), 'reports')) {
            return redirect()->route('admin.dashboard')->with('error', 'Reports are not available on your current subscription plan.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'office_id' => ['nullable', 'integer'],
            'format' => ['required', 'in:csv,print'],
        ]);

        $officeId = (int) ($validated['office_id'] ?? 0);
        ['queueEntries' => $queueEntries, 'appointments' => $appointments] = $this->reportData($validated['date'], $officeId);

        if ($validated['format'] === 'print') {
            $office = $officeId > 0 ? $this->officesQuery()->find($officeId) : null;
            $queueByStatus = $queueEntries->groupBy('status')->map->count();
            $appointmentsByStatus = $appointments->groupBy('status')->map->count();
            $html = view('office.report-print', [
                'office' => $office ?? (object) ['name' => 'All workspace offices'],
                'date' => $validated['date'],
                'queueEntries' => $queueEntries,
                'appointments' => $appointments,
                'queueByStatus' => $queueByStatus,
                'appointmentsByStatus' => $appointmentsByStatus,
            ])->render();

            $filename = 'tenant-report-'.$validated['date'].($officeId > 0 ? '-office-'.Str::slug($office?->name ?? (string) $officeId) : '-all-offices').'.html';

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        $filename = 'tenant-report-'.$validated['date'].($officeId > 0 ? '-office-'.$officeId : '').'.csv';

        $callback = function () use ($validated, $queueEntries, $appointments): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tenant Admin Report']);
            fputcsv($out, ['Date', $validated['date']]);
            fputcsv($out, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($out, []);

            fputcsv($out, ['Queue Entries']);
            fputcsv($out, ['Office', 'Queue #', 'Name', 'Type', 'Reference', 'Status']);
            foreach ($queueEntries as $entry) {
                fputcsv($out, [
                    $entry->office?->name,
                    $entry->queue_number,
                    $entry->display_name,
                    $entry->service_type,
                    $entry->reference_code,
                    $entry->status,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Appointments']);
            fputcsv($out, ['Office', 'Time', 'Name', 'Type', 'Reference', 'Status']);
            foreach ($appointments as $appointment) {
                fputcsv($out, [
                    $appointment->office?->name,
                    optional($appointment->appointment_time)->format('H:i'),
                    $appointment->display_name,
                    $appointment->appointment_type,
                    $appointment->reference_code,
                    $appointment->status,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** List approved non-admin office staff accounts, excluding archived. */
    public function usersIndex(Request $request)
    {
        ['search' => $search, 'officeId' => $officeId, 'offices' => $offices, 'roles' => $roles] = $this->officeStaffFilterData($request);
        $users = User::where('role', '!=', User::ROLE_TENANT_ADMIN)
            ->whereNotNull('approved_at')
            ->notArchived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->tap(fn ($query) => $this->applyOfficeStaffFilters($query, $search, $officeId))
            ->with('office')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(self::OFFICE_STAFF_PAGE_SIZE)
            ->withQueryString();
        return view('admin.users.index', compact('users', 'search', 'officeId', 'offices', 'roles'));
    }

    /** List archived non-admin office staff accounts. */
    public function archivedAccounts(Request $request)
    {
        ['search' => $search, 'officeId' => $officeId, 'offices' => $offices, 'roles' => $roles] = $this->officeStaffFilterData($request);
        $users = User::where('role', '!=', User::ROLE_TENANT_ADMIN)
            ->archived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->tap(fn ($query) => $this->applyOfficeStaffFilters($query, $search, $officeId))
            ->with('office')
            ->orderBy('role')
            ->orderByDesc('archived_at')
            ->paginate(self::OFFICE_STAFF_PAGE_SIZE)
            ->withQueryString();
        return view('admin.users.archived', compact('users', 'search', 'officeId', 'offices', 'roles'));
    }

    /** Archive a non-admin office staff account (soft archive via archived_at). */
    public function archiveUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return back()->withErrors(['user' => 'Administrator accounts cannot be archived from the office staff screens.']);
        }
        if ($user->archived_at !== null) {
            return back()->with('info', 'That office staff account is already archived.');
        }
        $user->update(['archived_at' => now()]);
        return redirect()->route('admin.users.archived')->with('success', "Office staff account for {$user->name} has been archived.");
    }

    /** Recover an archived non-admin office staff account. */
    public function recoverUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return back()->withErrors(['user' => 'Administrator accounts cannot be recovered from the office staff screens.']);
        }
        if ($user->archived_at === null) {
            return back()->with('info', 'That office staff account is not archived.');
        }
        $user->update(['archived_at' => null]);
        return redirect()->route('admin.users.index')->with('success', "Office staff account for {$user->name} has been recovered.");
    }

    /** Permanently delete a non-admin office staff account. */
    public function destroyUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return back()->withErrors(['user' => 'Administrator accounts cannot be deleted from the office staff screens.']);
        }
        $name = $user->name;
        $user->delete();
        return redirect()->route('admin.users.archived')->with('success', "Office staff account for {$name} has been permanently deleted.");
    }

    /** List pending non-admin office staff accounts awaiting admin approval. */
    public function pendingAccounts(Request $request)
    {
        ['search' => $search, 'officeId' => $officeId, 'offices' => $offices] = $this->officeStaffFilterData($request);
        $users = User::where('role', '!=', User::ROLE_TENANT_ADMIN)
            ->whereNull('approved_at')
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->tap(fn ($query) => $this->applyOfficeStaffFilters($query, $search, $officeId))
            ->with('office')
            ->orderBy('role')
            ->orderBy('created_at')
            ->paginate(self::OFFICE_STAFF_PAGE_SIZE)
            ->withQueryString();
        return view('admin.users.pending', compact('users', 'search', 'officeId', 'offices'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $user = $this->findTenantUserOrFail($user->getKey());

        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return back()->withErrors(['user' => 'Tenant administrator role cannot be changed from the staff screens.']);
        }

        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $role = $this->resolveAssignableRole($validated['role']);

        if (! $role) {
            return back()->withErrors(['role' => 'Please choose a valid active tenant role.']);
        }

        $user->update(['role' => $role->slug]);

        return back()->with('success', "{$user->name} is now assigned to the {$role->name} role.");
    }

    public function rolesIndex()
    {
        $roles = Role::query()
            ->forTenant($this->tenantId())
            ->with('permissions')
            ->orderByRaw("CASE WHEN tenant_id IS NULL THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $permissionGroups = $this->availablePermissions();

        return view('admin.roles.index', compact('roles', 'permissionGroups'));
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['integer'],
        ]);

        $existing = Role::query()
            ->forTenant($this->tenantId())
            ->where('slug', $validated['slug'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['role' => 'That role slug is already in use for this tenant.'])->withInput();
        }

        $role = Role::query()->create([
            'tenant_id' => $this->tenantId(),
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, Role $role)
    {
        if ($role->isProtected()) {
            return back()->withErrors(['role' => 'Protected built-in roles cannot be edited.']);
        }

        if ($role->tenant_id !== $this->tenantId()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['integer'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function toggleRoleStatus(Role $role)
    {
        if ($role->isProtected()) {
            return back()->withErrors(['role' => 'Protected built-in roles cannot be disabled.']);
        }

        if ($role->tenant_id !== $this->tenantId()) {
            abort(404);
        }

        $role->update([
            'is_active' => ! $role->is_active,
        ]);

        return redirect()->route('admin.roles.index')->with('success', $role->is_active ? 'Role enabled successfully.' : 'Role disabled successfully.');
    }

    public function destroyRole(Role $role)
    {
        if ($role->isProtected()) {
            return back()->withErrors(['role' => 'Protected built-in roles cannot be deleted.']);
        }

        if ($role->tenant_id !== $this->tenantId()) {
            abort(404);
        }

        if ($role->assignedUsersCount() > 0) {
            return back()->withErrors(['role' => 'Reassign or remove users from this role before deleting it.']);
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    /** Confirm a pending office staff account: set approved_at, email_verified_at, and send confirmation email. */
    public function approveUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->approved_at !== null) {
            return back()->with('info', 'That office staff account is already approved.');
        }
        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return back()->withErrors(['user' => 'Administrator accounts cannot be approved through the office staff screen.']);
        }

        $user->approved_at = now();
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();
        $user->notify(new AccountConfirmedNotification());

        return back()->with('success', "Office staff account for {$user->name} has been confirmed. A confirmation email has been sent to {$user->email}.");
    }
}
