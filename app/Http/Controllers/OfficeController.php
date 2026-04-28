<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\ActivityLog;
use App\Models\AppVersion;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\SupportThread;
use App\Models\User;
use App\Services\LimitEnforcer;
use App\Services\QrCodeService;
use App\Services\TenantPlanEnforcer;
use App\Support\TenantUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class OfficeController extends Controller
{
    private ?bool $hasAssignedStaffColumn = null;

    public function __construct(
        private QrCodeService $qrCodeService,
        private TenantPlanEnforcer $tenantPlanEnforcer,
        private \App\Services\LimitEnforcer $limitEnforcer,
    ) {}

    private function currentTenant(): ?\App\Models\Tenant
    {
        return app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    }

    private function assignedStaffColumnAvailable(): bool
    {
        if ($this->hasAssignedStaffColumn !== null) {
            return $this->hasAssignedStaffColumn;
        }

        return $this->hasAssignedStaffColumn = Schema::connection('tenant')->hasColumn('queue_entries', 'assigned_staff_user_id');
    }

    public function dashboard()
    {
        $staffUser = auth()->user();
        $office = $staffUser->office;
        if (! $office) {
            return redirect()->route('dashboard')->with('error', 'No office assigned.');
        }
        $canUseAssignedStaff = $this->assignedStaffColumnAvailable();

        $todayQueue = $office->queueEntries()
            ->activeToday()
            ->when($canUseAssignedStaff, function ($query) use ($staffUser) {
                $query->where(function ($inner) use ($staffUser) {
                    $inner->where('assigned_staff_user_id', $staffUser->id)
                        ->orWhereNull('assigned_staff_user_id');
                });
            })
            ->orderBy('queue_number')
            ->get();
        $currentServing = $office->queueEntries()
            ->today()
            ->when($canUseAssignedStaff, function ($query) use ($staffUser) {
                $query->where(function ($inner) use ($staffUser) {
                    $inner->where('assigned_staff_user_id', $staffUser->id)
                        ->orWhereNull('assigned_staff_user_id');
                });
            })
            ->whereIn('status', [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->orderBy('queue_number')
            ->first();

        // Check for system updates
        $tenant = $this->currentTenant();
        $currentVersion = $tenant->app_version ?? cache('app_current_version', config('app.version', '1.0.0'));
        $latestVersion = AppVersion::latest()->first();
        $updateAvailable = $latestVersion && $latestVersion->isNewerThan($currentVersion) && $latestVersion->version !== AppVersion::normalizeVersion($currentVersion);

        return view('office.dashboard', compact('office', 'todayQueue', 'currentServing', 'updateAvailable', 'latestVersion', 'currentVersion'));
    }

    public function callNext()
    {
        $staffUser = auth()->user();
        $office = $staffUser->office;
        if (! $office) {
            return back()->with('error', 'No office assigned.');
        }
        $canUseAssignedStaff = $this->assignedStaffColumnAvailable();

        $next = $office->queueEntries()
            ->today()
            ->when($canUseAssignedStaff, function ($query) use ($staffUser) {
                $query->where(function ($inner) use ($staffUser) {
                    $inner->where('assigned_staff_user_id', $staffUser->id)
                        ->orWhereNull('assigned_staff_user_id');
                });
            })
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

        ActivityLog::log(
            $office->id,
            'queue_called',
            auth()->user()->name.' called queue #'.$next->queue_number.' ('.$next->display_name.')',
            auth()->id(),
            QueueEntry::class,
            $next->id,
            ['queue_number' => $next->queue_number, 'display_name' => $next->display_name]
        );

        try {
            event(new QueueUpdated((int) $office->tenant_id, (int) $office->id, 'called', (int) $next->id));
        } catch (\Throwable $e) {
            \Log::error('Failed to broadcast queue update', [
                'error' => $e->getMessage(),
                'office_id' => $office->id,
                'queue_entry_id' => $next->id,
            ]);
        }

        \Log::info('Checking email sending', [
            'queue_entry_id' => $next->id,
            'guest_email' => $next->guest_email,
            'guest_email_empty' => empty($next->guest_email),
        ]);

        if (! empty($next->guest_email)) {
            \Log::info('Attempting to send email', [
                'email' => $next->guest_email,
                'subject' => 'Your turn now at '.$office->name,
            ]);

            try {
                \Mail::raw(
                    sprintf('Hi %s! It is your turn now at %s. Your queue number is #%d.', $next->display_name, $office->name, $next->queue_number),
                    function ($message) use ($next, $office) {
                        $message->to($next->guest_email)
                            ->subject('Your turn now at '.$office->name);
                    }
                );
                \Log::info('Email sent successfully', ['queue_entry_id' => $next->id]);
            } catch (\Throwable $e) {
                \Log::error('Failed to send queue email notification.', [
                    'queue_entry_id' => $next->id,
                    'office_id' => $office->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            \Log::warning('Email not sent - guest_email is empty', ['queue_entry_id' => $next->id]);
        }

        return back()->with('success', "Now serving #{$next->queue_number}");
    }

    public function updateQueueStatus(Request $request, QueueEntry $queueEntry)
    {
        $this->authorizeOffice($queueEntry->office_id);
        if ($this->assignedStaffColumnAvailable() && $queueEntry->assigned_staff_user_id !== null && $queueEntry->assigned_staff_user_id !== auth()->id()) {
            abort(403);
        }

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

        ActivityLog::log(
            $queueEntry->office_id,
            'queue_updated',
            auth()->user()->name.' updated queue #'.$queueEntry->queue_number.' to '.$validated['status'],
            auth()->id(),
            QueueEntry::class,
            $queueEntry->id,
            ['queue_number' => $queueEntry->queue_number, 'status' => $validated['status'], 'display_name' => $queueEntry->display_name]
        );

        try {
            event(new QueueUpdated((int) $queueEntry->tenant_id, (int) $queueEntry->office_id, (string) $validated['status'], (int) $queueEntry->id));
        } catch (\Throwable $e) {
            \Log::error('Failed to broadcast queue update', [
                'error' => $e->getMessage(),
                'office_id' => $queueEntry->office_id,
                'queue_entry_id' => $queueEntry->id,
            ]);
        }

        return back()->with('success', 'Queue status updated.');
    }

    public function clearAllQueues(Request $request): RedirectResponse
    {
        $staffUser = auth()->user();
        $office = $staffUser->office;
        if (! $office) {
            return redirect()->route('office.dashboard')->with('error', 'No office assigned.');
        }

        $this->authorizeOffice($office->id);

        $queueEntries = QueueEntry::where('office_id', $office->id)
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->get();

        $count = $queueEntries->count();

        if ($count > 0) {
            foreach ($queueEntries as $queueEntry) {
                $queueEntry->update(['status' => 'cancelled']);
            }

            ActivityLog::log(
                $office->id,
                'queue_cleared',
                auth()->user()->name.' cleared all queues ('.$count.' entries)',
                auth()->id(),
                QueueEntry::class,
                null,
                ['count' => $count]
            );
        }

        return back()->with('success', $count > 0 ? "Cleared {$count} queue entries." : 'No queues to clear.');
    }

    /** Show QR code for the officer's office (so end users can scan to get number or book). */
    public function qr()
    {
        $staffUser = auth()->user();
        $office = $staffUser->office;
        if (! $office) {
            return redirect()->route('office.dashboard')->with('error', 'No office assigned.');
        }

        $queuePath = URL::signedRoute('queue.office.staff', [
            'slug' => $office->slug,
            'userId' => $staffUser->id,
        ], null, false);
        $queueUrl = \App\Support\TenantUrl::forPath($office->tenant, $queuePath);

        return view('office.qr', compact('office', 'queueUrl'));
    }

    /** Generate QR code image for the officer's office. */
    public function qrCodeImage(Request $request): Response
    {
        $staffUser = auth()->user();
        $office = $staffUser->office;
        if (! $office) {
            abort(403);
        }
        if (! $this->limitEnforcer->canIssueQr($this->currentTenant())) {
            abort(403, 'QR issuance limit reached for your plan.');
        }
        $path = URL::signedRoute('queue.office.staff', [
            'slug' => $office->slug,
            'userId' => $staffUser->id,
        ], null, false);
        $url = \App\Support\TenantUrl::forPath($office->tenant, $path);
        $result = $this->qrCodeService->build($url);
        $response = response($result->getString())
            ->header('Content-Type', $result->getMimeType());

        if ($request->boolean('download')) {
            $ext = $result->getMimeType() === 'image/svg+xml' ? 'svg' : 'png';
            $filename = 'qr-'.\Illuminate\Support\Str::slug($office->name).'.'.$ext;
            $response->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }

        return $response;
    }

    /** Activity log for the officer's office. */
    public function activity(Request $request)
    {
        $office = auth()->user()->office;
        if (! $office) {
            return redirect()->route('office.dashboard')->with('error', 'No office assigned.');
        }

        $query = $office->activityLogs()->with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $activities = $query->paginate(30)->withQueryString();

        $actionOptions = [
            '' => 'All actions',
            'login' => 'Login',
            'logout' => 'Logout',
            'queue_joined' => 'Queue joined',
            'queue_called' => 'Queue called',
            'queue_updated' => 'Queue updated',
        ];

        return view('office.activity', compact('office', 'activities', 'actionOptions'));
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

        return view('office.notifications', [
            'notifications' => $this->paginateCollection($items, 25, $request),
            'unreadCount' => $items->where('is_unread', true)->count(),
        ]);
    }

    /** Daily report for the officer's office only. */
    public function reports(Request $request)
    {
        if (! $this->tenantPlanEnforcer->hasFeature($this->currentTenant(), 'reports')) {
            return redirect()->route('office.dashboard')->with('error', 'Reports are not available on your current subscription plan.');
        }

        $office = auth()->user()->office;
        if (! $office) {
            return redirect()->route('office.dashboard')->with('error', 'No office assigned.');
        }
        $date = $request->get('date', today()->toDateString());
        $queueEntries = $office->queueEntries()->where('queue_date', $date)->orderBy('queue_number')->get();

        return view('office.reports', compact('office', 'queueEntries', 'date'));
    }

    /** Generate and download report (CSV or PDF). */
    public function downloadReport(Request $request)
    {
        if (! $this->tenantPlanEnforcer->hasFeature($this->currentTenant(), 'reports')) {
            return redirect()->route('office.dashboard')->with('error', 'Reports are not available on your current subscription plan.');
        }

        $office = auth()->user()->office;
        if (! $office) {
            return redirect()->route('office.dashboard')->with('error', 'No office assigned.');
        }

        $request->validate([
            'date' => ['required', 'date'],
            'format' => ['required', 'in:csv,pdf'],
        ]);

        $date = $request->date;
        $queueEntries = $office->queueEntries()->where('queue_date', $date)->orderBy('queue_number')->get();

        $queueByStatus = $queueEntries->groupBy('status')->map->count();

        if ($request->format === 'csv') {
            return $this->downloadReportCsv($office, $date, $queueEntries, $queueByStatus);
        }

        return $this->downloadReportPdf($office, $date, $queueEntries, $queueByStatus);
    }

    private function downloadReportCsv($office, $date, $queueEntries, $queueByStatus): Response
    {
        $filename = 'report-'.\Illuminate\Support\Str::slug($office->name).'-'.$date.'.csv';

        $callback = function () use ($office, $date, $queueEntries, $queueByStatus) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['QueueLess — Office Report']);
            fputcsv($out, ['Office', $office->name]);
            fputcsv($out, ['Report Date', $date]);
            fputcsv($out, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($out, []);

            fputcsv($out, ['Queue Summary']);
            foreach ($queueByStatus as $status => $count) {
                fputcsv($out, [$status, $count]);
            }
            fputcsv($out, ['Total', $queueEntries->count()]);
            fputcsv($out, []);

            fputcsv($out, ['Queue Entries', 'Number', 'Name', 'Service Type', 'Email', 'Phone', 'Status']);
            foreach ($queueEntries as $e) {
                fputcsv($out, [
                    '',
                    $e->queue_number,
                    $e->display_name,
                    $e->service_type ?? '',
                    $e->guest_email ?? '',
                    $e->guest_phone ?? '',
                    $e->status,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function downloadReportPdf($office, $date, $queueEntries, $queueByStatus): Response
    {
        $html = view('office.report-print', compact('office', 'date', 'queueEntries', 'queueByStatus'))->render();

        $filename = 'report-'.\Illuminate\Support\Str::slug($office->name).'-'.$date.'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function authorizeOffice(int $officeId): void
    {
        if (auth()->user()->office_id !== $officeId) {
            abort(403);
        }
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
