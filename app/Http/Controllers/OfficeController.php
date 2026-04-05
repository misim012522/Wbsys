<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Services\QrCodeService;
use App\Services\TenantPlanEnforcer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfficeController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService,
        private TenantPlanEnforcer $tenantPlanEnforcer,
    ) {}

    private function currentTenant(): ?\App\Models\Tenant
    {
        return app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    }

    public function dashboard()
    {
        $office = auth()->user()->office;
        if (! $office) {
            return redirect()->route('dashboard')->with('error', 'No office assigned.');
        }

        $todayQueue = $office->queueEntries()->activeToday()->orderBy('queue_number')->get();
        $todayAppointments = $office->appointments()->upcomingToday()->orderBy('appointment_time')->get();
        $currentServing = $office->queueEntries()
            ->today()
            ->whereIn('status', [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->orderBy('queue_number')
            ->first();

        return view('office.dashboard', compact('office', 'todayQueue', 'todayAppointments', 'currentServing'));
    }

    public function callNext()
    {
        $office = auth()->user()->office;
        if (! $office) {
            return back()->with('error', 'No office assigned.');
        }

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

        ActivityLog::log(
            $office->id,
            'queue_called',
            auth()->user()->name.' called queue #'.$next->queue_number.' ('.$next->display_name.')',
            auth()->id(),
            QueueEntry::class,
            $next->id,
            ['queue_number' => $next->queue_number, 'display_name' => $next->display_name]
        );

        return back()->with('success', "Now serving #{$next->queue_number}");
    }

    public function updateQueueStatus(Request $request, QueueEntry $queueEntry)
    {
        $this->authorizeOffice($queueEntry->office_id);

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

        return back()->with('success', 'Queue status updated.');
    }

    public function acceptAppointment(Appointment $appointment)
    {
        $this->authorizeOffice($appointment->office_id);
        $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);

        ActivityLog::log(
            $appointment->office_id,
            'appointment_accepted',
            auth()->user()->name.' accepted appointment for '.$appointment->display_name.' at '.\Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A'),
            auth()->id(),
            Appointment::class,
            $appointment->id,
            ['display_name' => $appointment->display_name, 'appointment_time' => $appointment->appointment_time]
        );

        return back()->with('success', 'Appointment confirmed.');
    }

    public function completeAppointment(Appointment $appointment)
    {
        $this->authorizeOffice($appointment->office_id);
        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);

        ActivityLog::log(
            $appointment->office_id,
            'appointment_completed',
            auth()->user()->name.' completed appointment for '.$appointment->display_name,
            auth()->id(),
            Appointment::class,
            $appointment->id,
            ['display_name' => $appointment->display_name]
        );

        return back()->with('success', 'Appointment completed.');
    }

    public function cancelAppointment(Appointment $appointment)
    {
        $this->authorizeOffice($appointment->office_id);
        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);

        ActivityLog::log(
            $appointment->office_id,
            'appointment_cancelled',
            auth()->user()->name.' cancelled appointment for '.$appointment->display_name,
            auth()->id(),
            Appointment::class,
            $appointment->id,
            ['display_name' => $appointment->display_name]
        );

        return back()->with('success', 'Appointment cancelled.');
    }

    /** Show QR code for the officer's office (so end users can scan to get number or book). */
    public function qr()
    {
        $office = auth()->user()->office;
        if (! $office) {
            return redirect()->route('office.dashboard')->with('error', 'No office assigned.');
        }

        return view('office.qr', compact('office'));
    }

    /** Generate QR code image for the officer's office. Uses SVG so it works without the GD extension. */
    public function qrCodeImage(Request $request): Response
    {
        $office = auth()->user()->office;
        if (! $office) {
            abort(403);
        }
        $url = $this->qrCodeService->queueOfficeUrl($office->slug);
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
            'appointment_booked' => 'Appointment booked',
            'appointment_accepted' => 'Appointment accepted',
            'appointment_completed' => 'Appointment completed',
            'appointment_cancelled' => 'Appointment cancelled',
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
        $appointments = $office->appointments()->where('appointment_date', $date)->orderBy('appointment_time')->get();

        return view('office.reports', compact('office', 'queueEntries', 'appointments', 'date'));
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
        $appointments = $office->appointments()->where('appointment_date', $date)->orderBy('appointment_time')->get();

        $queueByStatus = $queueEntries->groupBy('status')->map->count();
        $appointmentsByStatus = $appointments->groupBy('status')->map->count();

        if ($request->format === 'csv') {
            return $this->downloadReportCsv($office, $date, $queueEntries, $appointments, $queueByStatus, $appointmentsByStatus);
        }

        return $this->downloadReportPdf($office, $date, $queueEntries, $appointments, $queueByStatus, $appointmentsByStatus);
    }

    private function downloadReportCsv($office, $date, $queueEntries, $appointments, $queueByStatus, $appointmentsByStatus): Response
    {
        $filename = 'report-'.\Illuminate\Support\Str::slug($office->name).'-'.$date.'.csv';

        $callback = function () use ($office, $date, $queueEntries, $appointments, $queueByStatus, $appointmentsByStatus) {
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
            fputcsv($out, []);

            fputcsv($out, ['Appointments Summary']);
            foreach ($appointmentsByStatus as $status => $count) {
                fputcsv($out, [$status, $count]);
            }
            fputcsv($out, ['Total', $appointments->count()]);
            fputcsv($out, []);

            fputcsv($out, ['Appointments', 'Time', 'Name', 'Type', 'Email', 'Phone', 'Status']);
            foreach ($appointments as $a) {
                fputcsv($out, [
                    '',
                    \Carbon\Carbon::parse($a->appointment_time)->format('H:i'),
                    $a->display_name,
                    $a->appointment_type ?? '',
                    $a->guest_email ?? '',
                    $a->guest_phone ?? '',
                    $a->status,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function downloadReportPdf($office, $date, $queueEntries, $appointments, $queueByStatus, $appointmentsByStatus): Response
    {
        $html = view('office.report-print', compact('office', 'date', 'queueEntries', 'appointments', 'queueByStatus', 'appointmentsByStatus'))->render();

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
