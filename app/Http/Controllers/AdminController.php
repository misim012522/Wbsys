<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use App\Models\ActivityLog;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\SupportThread;
use App\Models\User;
use App\Notifications\AccountConfirmedNotification;
use App\Services\QrCodeService;
use App\Services\TenantPlanEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private const OFFICE_STAFF_PAGE_SIZE = 10;

    private ?bool $hasAssignedStaffColumn = null;

    public function __construct(
        private QrCodeService $qrCodeService,
        private TenantPlanEnforcer $tenantPlanEnforcer,
        private \App\Services\LimitEnforcer $limitEnforcer,
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

    private function staffQueueSignedUrl(Office $office, User $staff): string
    {
        $path = URL::signedRoute('queue.office.staff', [
            'slug' => $office->slug,
            'userId' => $staff->id,
        ], null, false);

        return \App\Support\TenantUrl::forPath($office->tenant, $path);
    }

    private function assignedStaffColumnAvailable(): bool
    {
        if ($this->hasAssignedStaffColumn !== null) {
            return $this->hasAssignedStaffColumn;
        }

        return $this->hasAssignedStaffColumn = Schema::connection('tenant')->hasColumn('queue_entries', 'assigned_staff_user_id');
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
        ];
    }

    private function reportData(string $date, int $officeId = 0): array
    {
        $queueQuery = QueueEntry::with(['office'])->where('queue_date', $date);

        if ($tid = $this->tenantId()) {
            $queueQuery->forTenant($tid);
        }

        if ($officeId > 0) {
            $queueQuery->where('office_id', $officeId);
        }

        $queueEntries = $queueQuery->orderBy('office_id')->orderBy('queue_number')->get();

        return compact('queueEntries');
    }

    public function dashboard()
    {
        $office = $this->defaultOffice();

        $baseQueue = QueueEntry::query();
        if ($tid = $this->tenantId()) {
            $baseQueue->forTenant($tid);
        }
        $todayQueues = (clone $baseQueue)->where('queue_date', today())->whereIn('status', ['waiting', 'called', 'serving'])->count();
        $completedToday = (clone $baseQueue)->where('queue_date', today())->where('status', 'completed')->count();
        $canUseAssignedStaff = $this->assignedStaffColumnAvailable();

        $staffQueueStats = User::query()
            ->when($this->tenantId(), fn ($query, $tenantId) => $query->where('tenant_id', $tenantId))
            ->where('role', User::ROLE_OFFICE_STAFF)
            ->whereNotNull('office_id')
            ->with(['office'])
            ->orderBy('name')
            ->get()
            ->map(function (User $staff) use ($canUseAssignedStaff) {
                $waitingCount = 0;
                $completedCount = 0;

                if ($canUseAssignedStaff) {
                    $waitingCount = QueueEntry::query()
                        ->where('office_id', $staff->office_id)
                        ->where('queue_date', today())
                        ->where('assigned_staff_user_id', $staff->id)
                        ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
                        ->count();

                    $completedCount = QueueEntry::query()
                        ->where('office_id', $staff->office_id)
                        ->where('queue_date', today())
                        ->where('assigned_staff_user_id', $staff->id)
                        ->where('status', QueueEntry::STATUS_COMPLETED)
                        ->count();
                }

                return [
                    'name' => $staff->name,
                    'office_name' => $staff->office?->name,
                    'waiting_count' => $waitingCount,
                    'completed_count' => $completedCount,
                ];
            });

        // Check for system updates
        $tenant = $this->currentTenant();
        $currentVersion = $tenant->app_version ?? cache('app_current_version', config('app.version', '1.0.0'));
        $latestVersion = AppVersion::latest()->first();
        $updateAvailable = $latestVersion && $latestVersion->isNewerThan($currentVersion) && $latestVersion->version !== AppVersion::normalizeVersion($currentVersion);

        return view('admin.dashboard', compact('office', 'todayQueues', 'completedToday', 'staffQueueStats', 'updateAvailable', 'latestVersion', 'currentVersion'));
    }

    public function profile()
    {
        return redirect()->route('admin.settings.edit')->with('info', 'Workspace info is now included in Admin settings.');
    }

    /** QR code for the tenant's built-in workspace office. */
    public function qrCodes()
    {
        if (! ($this->currentTenant()?->getSetting('customization.guest_queue', true) ?? true)) {
            return redirect()->route('admin.dashboard')->with('info', 'Public QR access is disabled for this tenant workspace.');
        }

        $office = $this->defaultOffice();
        $officeStaff = User::query()
            ->when($this->tenantId(), fn ($query, $tenantId) => $query->where('tenant_id', $tenantId))
            ->where('role', User::ROLE_OFFICE_STAFF)
            ->where('office_id', $office?->id)
            ->orderBy('name')
            ->get();

        $staffQrCards = collect();
        if ($office) {
            $staffQrCards = $officeStaff->map(function (User $staff) use ($office) {
                $queueUrl = $this->staffQueueSignedUrl($office, $staff);

                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'queue_url' => $queueUrl,
                    'qr_image_url' => route('admin.qr.image', ['office_staff_id' => $staff->id]),
                ];
            });
        }

        return view('admin.qr', compact('office', 'staffQrCards'));
    }

    /** Generate QR code image for the tenant's default office. Uses APP_URL so QR works from any device. */
    public function qrCodeImage(): Response
    {
        abort_unless($this->currentTenant()?->getSetting('customization.guest_queue', true) ?? true, 404);
        abort_unless($this->limitEnforcer->canIssueQr($this->currentTenant()), 403);

        $office = $this->defaultOffice();
        abort_unless($office, 404);

        $staffId = request()->integer('office_staff_id');
        $url = $this->qrCodeService->queueOfficeUrl($office->slug, $this->currentTenant());
        if ($staffId > 0) {
            $staff = User::query()
                ->when($this->tenantId(), fn ($query, $tenantId) => $query->where('tenant_id', $tenantId))
                ->where('role', User::ROLE_OFFICE_STAFF)
                ->where('office_id', $office->id)
                ->findOrFail($staffId);
            $url = $this->staffQueueSignedUrl($office, $staff);
        }
        $result = $this->qrCodeService->build($url);

        return response($result->getString())
            ->header('Content-Type', $result->getMimeType());
    }

    /** Admin office panel: queue-only monitoring for one office. */
    public function serveOffice(Office $office)
    {
        return redirect()->route('admin.dashboard')->with('info', 'Queue serving is now handled only by office staff dashboards.');
    }

    public function callNext(Office $office)
    {
        return redirect()->route('admin.dashboard')->with('info', 'Queue serving is now handled only by office staff dashboards.');
    }

    public function updateQueueStatus(Request $request, QueueEntry $queueEntry)
    {
        return redirect()->route('admin.dashboard')->with('info', 'Queue serving is now handled only by office staff dashboards.');
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
        ['queueEntries' => $queueEntries] = $this->reportData($date, $officeId);
        $offices = $this->officesQuery()->orderedByName()->get();

        return view('admin.reports', compact('queueEntries', 'date', 'offices', 'officeId'));
    }

    public function activity(Request $request)
    {
        $tenantId = $this->tenantId();
        abort_unless($tenantId, 403);

        $selectedAction = trim((string) $request->string('action'));
        $selectedRole = trim((string) $request->string('role'));

        $query = ActivityLog::query()
            ->with(['user:id,name,role', 'office:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereHas('user', function ($q) {
                $q->whereIn('role', [User::ROLE_TENANT_ADMIN, User::ROLE_OFFICE_STAFF]);
            })
            ->latest('created_at');

        if ($selectedAction !== '') {
            $query->where('action', $selectedAction);
        }

        if ($selectedRole !== '') {
            $query->whereHas('user', function ($q) use ($selectedRole) {
                $q->where('role', $selectedRole);
            });
        }

        $activities = $query->paginate(30)->withQueryString();

        $actionOptions = ActivityLog::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('user', function ($q) {
                $q->whereIn('role', [User::ROLE_TENANT_ADMIN, User::ROLE_OFFICE_STAFF]);
            })
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.activity', [
            'activities' => $activities,
            'actionOptions' => $actionOptions,
            'selectedAction' => $selectedAction,
            'selectedRole' => $selectedRole,
        ]);
    }

    public function notifications(Request $request)
    {
        $tenant = $this->currentTenant();
        $items = collect();

        if ($tenant && SupportThread::supportTablesExist()) {
            $threads = SupportThread::query()
                ->where('tenant_id', $tenant->id)
                ->latest('last_message_at')
                ->limit(200)
                ->get();

            $items = $items->merge($threads->map(function (SupportThread $thread) {
                return [
                    'created_at' => $thread->last_message_at ?? $thread->created_at,
                    'title' => 'Support thread update',
                    'message' => $thread->subject,
                    'is_unread' => $thread->hasUnreadForTenant(),
                    'kind' => 'support',
                ];
            }));
        }

        try {
            $databaseNotifications = auth()->user()?->notifications()->latest()->limit(200)->get() ?? collect();
            $items = $items->merge($databaseNotifications->map(function ($notification) {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'created_at' => $notification->created_at,
                    'title' => str(class_basename((string) $notification->type))->replace('Notification', '')->headline()->toString(),
                    'message' => (string) ($data['message'] ?? $data['subject'] ?? 'Notification received.'),
                    'is_unread' => $notification->read_at === null,
                    'kind' => 'system',
                ];
            }));
        } catch (\Throwable) {
            // Ignore notification-source issues and continue with support notifications.
        }

        $items = $items
            ->sortByDesc(fn (array $row) => optional($row['created_at'])->timestamp ?? 0)
            ->values();

        return view('admin.notifications', [
            'notifications' => $this->paginateCollection($items, 25, $request),
            'unreadCount' => $items->where('is_unread', true)->count(),
        ]);
    }

    public function downloadReport(Request $request): StreamedResponse|RedirectResponse|Response
    {
        if (! $this->tenantPlanEnforcer->hasFeature($this->currentTenant(), 'reports')) {
            return redirect()->route('admin.dashboard')->with('error', 'Reports are not available on your current subscription plan.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'office_id' => ['nullable', 'integer'],
            'format' => ['required', 'in:csv,pdf'],
        ]);

        $officeId = (int) ($validated['office_id'] ?? 0);
        ['queueEntries' => $queueEntries] = $this->reportData($validated['date'], $officeId);

        if ($validated['format'] === 'pdf') {
            $office = $officeId > 0 ? $this->officesQuery()->find($officeId) : null;
            $queueByStatus = $queueEntries->groupBy('status')->map->count();
            $html = view('office.report-print', [
                'office' => $office ?? (object) ['name' => 'All workspace offices'],
                'date' => $validated['date'],
                'queueEntries' => $queueEntries,
                'queueByStatus' => $queueByStatus,
            ])->render();

            $filename = 'tenant-report-'.$validated['date'].($officeId > 0 ? '-office-'.Str::slug($office?->name ?? (string) $officeId) : '-all-offices').'.html';

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        $filename = 'tenant-report-'.$validated['date'].($officeId > 0 ? '-office-'.$officeId : '').'.csv';

        $callback = function () use ($validated, $queueEntries): void {
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
        ['search' => $search, 'officeId' => $officeId, 'offices' => $offices] = $this->officeStaffFilterData($request);
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

        return view('admin.users.index', compact('users', 'search', 'officeId', 'offices'));
    }

    /** List archived non-admin office staff accounts. */
    public function archivedAccounts(Request $request)
    {
        ['search' => $search, 'officeId' => $officeId, 'offices' => $offices] = $this->officeStaffFilterData($request);
        $users = User::where('role', '!=', User::ROLE_TENANT_ADMIN)
            ->archived()
            ->when($this->tenantId(), fn ($q) => $q->forTenant($this->tenantId()))
            ->tap(fn ($query) => $this->applyOfficeStaffFilters($query, $search, $officeId))
            ->with('office')
            ->orderBy('role')
            ->orderByDesc('archived_at')
            ->paginate(self::OFFICE_STAFF_PAGE_SIZE)
            ->withQueryString();

        return view('admin.users.archived', compact('users', 'search', 'officeId', 'offices'));
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

    /** Confirm a pending office staff account: set approved_at, email_verified_at, and send confirmation email. */
    public function approveUser($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->approved_at !== null) {
            return redirect()->route('admin.users.pending')->with('info', 'That office staff account is already approved.');
        }
        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return redirect()->route('admin.users.pending')->withErrors(['user' => 'Administrator accounts cannot be approved through the office staff screen.']);
        }

        $user->approved_at = now();
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();
        $user->notify(new AccountConfirmedNotification);

        return redirect()
            ->route('admin.users.pending')
            ->with('success', 'Office staff approved successfully.');
    }

    /**
     * Show a small confirmation page for approving a pending account.
     */
    public function approveUserConfirm($user)
    {
        $user = $this->findTenantUserOrFail($user);

        if ($user->approved_at !== null) {
            return redirect()->route('admin.users.pending')->with('info', 'That office staff account is already approved.');
        }

        return view('admin.users.approve-confirm', compact('user'));
    }

    /**
     * Approve user via a signed one-click link. The route is protected by the 'signed' middleware
     * and the existing tenant + permission middleware in routes/admin.php.
     */
    public function approveUserSigned($user, \Illuminate\Http\Request $request)
    {
        // The 'signed' middleware already validated signature; double-check user exists and tenant scoping.
        $user = $this->findTenantUserOrFail($user);

        if ($user->approved_at !== null) {
            return redirect()->route('admin.users.pending')->with('info', 'That office staff account is already approved.');
        }

        if ($user->role === User::ROLE_TENANT_ADMIN) {
            return redirect()->route('admin.users.pending')->withErrors(['user' => 'Administrator accounts cannot be approved through this flow.']);
        }

        $user->approved_at = now();
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();
        $user->notify(new AccountConfirmedNotification);

        return redirect()->route('admin.users.pending')->with('success', "Office staff account for {$user->name} has been confirmed.");
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->integer('page', 1));
        $total = $items->count();
        $results = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
