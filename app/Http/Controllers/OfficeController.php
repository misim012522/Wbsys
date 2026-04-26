<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\ActivityLog;
use App\Models\AppVersion;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\User;
use App\Services\LimitEnforcer;
use App\Services\QrCodeService;
use App\Services\TenantPlanEnforcer;
use App\Support\TenantUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
        $currentVersion = $tenant->app_version ?? config('app.version', '1.0.0');
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

        event(new QueueUpdated((int) $office->tenant_id, (int) $office->id, 'called', (int) $next->id));

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

        event(new QueueUpdated((int) $queueEntry->tenant_id, (int) $queueEntry->office_id, (string) $validated['status'], (int) $queueEntry->id));

        return back()->with('success', 'Queue status updated.');
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
}
