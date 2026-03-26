<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\User;
use App\Support\TenantUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->tenant && ! app()->bound('current_tenant')) {
                return redirect()->away(TenantUrl::dashboard($user->tenant, $user));
            }

            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        if (app()->bound('current_tenant')) {
            return redirect()->away(TenantUrl::login(app('current_tenant')));
        }

        return redirect()->route('login');
    }

    public function dashboard(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isCentralUser()) {
            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isOfficeStaff()) {
            return redirect()->route('office.dashboard');
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $user->tenant;
        $office = $user->office ?: Office::query()
            ->when($user->tenant_id, fn ($query) => $query->forTenant($user->tenant_id))
            ->orderedByName()
            ->first();

        $summary = [
            'active_queue' => 0,
            'today_appointments' => 0,
            'completed_today' => 0,
            'pending_staff' => 0,
            'current_serving' => null,
        ];

        if ($user->isAdmin()) {
            $queueQuery = QueueEntry::query()->when($user->tenant_id, fn ($query) => $query->forTenant($user->tenant_id));
            $appointmentQuery = Appointment::query()->when($user->tenant_id, fn ($query) => $query->forTenant($user->tenant_id));

            $summary['active_queue'] = (clone $queueQuery)
                ->where('queue_date', today())
                ->whereIn('status', ['waiting', 'called', 'serving'])
                ->count();
            $summary['today_appointments'] = (clone $appointmentQuery)
                ->where('appointment_date', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();
            $summary['completed_today'] = (clone $queueQuery)
                ->where('queue_date', today())
                ->where('status', 'completed')
                ->count()
                + (clone $appointmentQuery)
                    ->where('appointment_date', today())
                    ->where('status', 'completed')
                    ->count();
            $summary['pending_staff'] = User::query()
                ->where('role', '!=', User::ROLE_ADMIN)
                ->whereNull('approved_at')
                ->when($user->tenant_id, fn ($query) => $query->forTenant($user->tenant_id))
                ->count();
        } elseif ($office) {
            $summary['active_queue'] = $office->queueEntries()
                ->activeToday()
                ->count();
            $summary['today_appointments'] = $office->appointments()
                ->upcomingToday()
                ->count();
            $summary['completed_today'] = $office->queueEntries()
                ->today()
                ->where('status', QueueEntry::STATUS_COMPLETED)
                ->count()
                + $office->appointments()
                    ->where('appointment_date', today())
                    ->where('status', Appointment::STATUS_COMPLETED)
                    ->count();
            $summary['current_serving'] = $office->queueEntries()
                ->today()
                ->whereIn('status', [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
                ->orderBy('queue_number')
                ->first();
        }

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'user' => $user,
            'office' => $office,
            'summary' => $summary,
        ]);
    }
}
