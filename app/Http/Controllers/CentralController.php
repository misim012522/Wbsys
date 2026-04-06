<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentralTenantSignupRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Office;
use App\Models\Plan;
use App\Models\QueueEntry;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Notifications\TenantActivationStatusNotification;
use App\Notifications\TenantCredentialsNotification;
use App\Notifications\TenantSubscriptionUpdatedNotification;
use App\Notifications\TenantWorkspaceAccessNotification;
use App\Services\TenantDatabaseManager;
use App\Support\CentralPricing;
use App\Support\TenantDashboardProfile;
use App\Support\TenantDatabaseName;
use App\Support\TenantWorkspaceUrlValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CentralController extends Controller
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    public function home(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        abort_unless(auth()->user()?->isCentralUser(), 403);

        return view('central.dashboard', $this->dashboardViewData());
    }

    public function dashboard(): View
    {
        return view('central.dashboard', $this->dashboardViewData());
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        try {
            $this->tenantDatabaseManager->deleteTenantArtifacts($tenant);
            $tenant->delete();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Tenant {$tenant->name} could not be deleted. Please try again.");
        }

        return redirect()->route('central.dashboard')
            ->with('success', "Tenant {$tenant->name} was deleted successfully.");
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'contact_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tenants,email,'.$tenant->id],
            'subdomain' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:tenants,subdomain,'.$tenant->id],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain,'.$tenant->id],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $domain = $request->filled('domain') ? (string) $request->input('domain') : null;
            $subdomain = $domain ? null : (string) $request->input('subdomain');

            foreach (TenantWorkspaceUrlValidator::validate($domain, $subdomain) as $message) {
                $validator->errors()->add($domain ? 'domain' : 'subdomain', $message);
            }
        });

        if ($validator->fails()) {
            return redirect()->route('central.dashboard')
                ->withErrors($validator, 'tenantUpdate_'.$tenant->id)
                ->withInput()
                ->with('open_modal', 'tenant-edit-modal-'.$tenant->id);
        }

        $validated = $validator->validated();

        $tenant->update([
            'name' => trim($validated['name']),
            'address' => trim($validated['address']),
            'contact_number' => trim($validated['contact_number']),
            'email' => $validated['email'],
            'subdomain' => $validated['domain'] ? null : ($validated['subdomain'] ?: null),
            'domain' => $validated['domain'] ?: null,
        ]);

        return redirect()->route('central.dashboard')
            ->with('success', "Tenant {$tenant->name} was updated successfully.");
    }

    public function updateRbac(Request $request, Tenant $tenant): RedirectResponse
    {
        foreach (User::tenantAdminPermissionDefinitions() as $definition) {
            if (($definition['setting'] ?? null) === null || ($definition['input'] ?? null) === null) {
                continue;
            }

            $tenant->setSetting($definition['setting'], $request->boolean($definition['input']));
        }

        foreach (User::officeStaffPermissionDefinitions() as $definition) {
            $tenant->setSetting($definition['setting'], $request->boolean($definition['input']));
        }

        return redirect()->route('central.dashboard')
            ->with('success', "Access control for {$tenant->name} was updated successfully.");
    }

    public function toggleActivation(Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill([
            'is_active' => ! $tenant->is_active,
        ])->save();

        $statusLabel = $tenant->is_active ? 'activated' : 'deactivated';
        $notificationSent = $this->notifyTenantAdmin(
            $tenant,
            fn (User $admin) => $admin->notify(new TenantActivationStatusNotification($tenant))
        );

        $response = redirect()->route('central.dashboard')
            ->with('success', "Tenant {$tenant->name} was {$statusLabel} successfully.");

        if (! $notificationSent) {
            $response->with('info', 'The tenant status was updated, but no tenant admin email could be delivered from the central dashboard.');
        }

        return $response;
    }

    public function updateSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'string', 'in:'.implode(',', [
                TenantSubscription::STATUS_ACTIVE,
                TenantSubscription::STATUS_CANCELLED,
                TenantSubscription::STATUS_EXPIRED,
                TenantSubscription::STATUS_TRIALING,
            ])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('central.dashboard')
                ->withErrors($validator, 'tenantSubscription_'.$tenant->id)
                ->withInput()
                ->with('open_modal', 'tenant-subscription-modal-'.$tenant->id);
        }

        $validated = $validator->validated();

        $plan = Plan::active()->findOrFail($validated['plan_id']);
        $subscription = $tenant->subscriptions()->latest('id')->first() ?? new TenantSubscription(['tenant_id' => $tenant->id]);

        $subscription->fill([
            'plan_id' => $plan->id,
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?: null,
        ]);
        $subscription->save();

        $tenant->forceFill(['plan_id' => $plan->id])->save();

        $notificationSent = $this->notifyTenantAdmin(
            $tenant,
            fn (User $admin) => $admin->notify(new TenantSubscriptionUpdatedNotification($tenant, $plan, $subscription))
        );

        $response = redirect()->route('central.dashboard')
            ->with('success', "Subscription for {$tenant->name} was updated successfully.");

        if (! $notificationSent) {
            $response->with('info', 'Subscription was updated, but no tenant admin email could be delivered from the central dashboard.');
        }

        return $response;
    }

    public function sendWorkspaceAccess(Tenant $tenant): RedirectResponse
    {
        $notificationSent = $this->notifyTenantAdmin(
            $tenant,
            fn (User $admin) => $admin->notify(new TenantWorkspaceAccessNotification($tenant))
        );

        return redirect()->route('central.dashboard')->with(
            $notificationSent ? 'success' : 'error',
            $notificationSent
                ? "Workspace access details were emailed to {$tenant->name}."
                : "Workspace access email could not be sent for {$tenant->name}."
        );
    }

    public function resetTenantPassword(Tenant $tenant): RedirectResponse
    {
        $newPassword = $this->generateReadableTemporaryPassword();
        $passwordReset = false;
        $notificationSent = false;

        try {
            $this->tenantDatabaseManager->activate($tenant);

            $admin = User::on('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('role', User::ROLE_TENANT_ADMIN)
                ->orderBy('id')
                ->first();

            if (! $admin) {
                return redirect()->route('central.dashboard')
                    ->with('error', "No tenant admin account was found for {$tenant->name}.");
            }

            $admin->forceFill([
                'password' => Hash::make($newPassword),
            ])->save();
            $passwordReset = true;

            $admin->notify(new TenantCredentialsNotification($tenant, $newPassword));
            $notificationSent = true;
        } catch (\Throwable $e) {
            report($e);
        }

        $response = redirect()->route('central.dashboard');

        if (! $passwordReset) {
            return $response->with('error', "The temporary password could not be reset for {$tenant->name}.");
        }

        if (! $notificationSent) {
            return $response->with('info', "A new temporary password was generated for {$tenant->name}, but the credentials email could not be sent.");
        }

        return $response->with('success', "A new temporary password was generated and emailed for {$tenant->name}.");
    }

    public function create(): View
    {
        return view('central.register', [
            'plans' => Plan::active()
                ->get()
                ->sortBy(fn (Plan $plan) => array_search($plan->slug, array_column(CentralPricing::plans(), 'slug'), true))
                ->values(),
        ]);
    }

    public function store(CentralTenantSignupRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plan = Plan::findOrFail($validated['plan_id']);
        $tenant = null;
        $generatedPassword = $this->generateReadableTemporaryPassword();
        $admin = null;
        $mailDeliveryFailed = false;

        try {
            DB::connection('central')->transaction(function () use ($validated, $plan, &$tenant): void {
                $tenantName = trim($validated['tenant_name']);
                $slug = $this->generateUniqueTenantValue('slug', $tenantName);
                $subdomain = $this->generateUniqueTenantValue('subdomain', $tenantName);
                $databaseName = $this->generateUniqueTenantDatabaseName($tenantName);

                $tenant = new Tenant([
                    'name' => $tenantName,
                    'slug' => $slug,
                    'plan_id' => $plan->id,
                    'domain' => null,
                    'subdomain' => $subdomain,
                    'database_name' => $databaseName,
                    'address' => $validated['address'] ?? null,
                    'email' => $validated['email'],
                    'contact_number' => $validated['contact_number'],
                    'settings' => [
                        'database' => [
                            'mode' => 'dedicated',
                        ],
                        'theme' => [
                            'primary_color' => '#2563eb',
                            'app_name' => $tenantName,
                            'logo_url' => null,
                        ],
                        'dashboard' => [
                            'profile' => TenantDashboardProfile::inferFromName($tenantName),
                        ],
                    ],
                    'is_active' => true,
                ]);
                $tenant->save();

                TenantSubscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'starts_at' => $tenant->created_at ?? now(),
                    'status' => TenantSubscription::STATUS_ACTIVE,
                ]);
            });

            if (! $tenant) {
                throw new \RuntimeException('Tenant signup could not be completed.');
            }

            $adminData = [
                'name' => $tenant->name.' Admin',
                'username' => $validated['tenant_admin_username'],
                'email' => $validated['email'],
                'phone' => $validated['contact_number'],
                'password' => $generatedPassword,
            ];

            $admin = $this->tenantDatabaseManager->provision($tenant, $adminData);

            try {
                $admin->notify(new TenantCredentialsNotification($tenant, $generatedPassword));
            } catch (\Throwable $e) {
                $mailDeliveryFailed = true;
                report($e);
            }
        } catch (\Throwable $e) {
            if ($tenant) {
                rescue(fn () => $this->tenantDatabaseManager->deleteTenantArtifacts($tenant), report: false);
                rescue(fn () => $tenant->delete(), report: false);
            }

            report($e);
        }

        if (! isset($tenant) || ! $tenant) {
            return back()
                ->withInput()
                ->withErrors(['tenant_name' => 'Tenant registration failed while preparing the tenant database. Please try again.']);
        }

        $response = redirect()->route('login')->with(
            'success',
            $mailDeliveryFailed
                ? sprintf('Tenant %s has been registered, but the credentials email could not be sent right now.', $tenant->name)
                : sprintf('Tenant %s has been registered. Credentials were sent to %s.', $tenant->name, $validated['email'])
        );

        if ($mailDeliveryFailed) {
            $response->with(
                'info',
                'Please verify your mail server settings or try resending the tenant credentials.'
            );
        }

        return $response;
    }

    private function generateUniqueTenantValue(string $column, string $source): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'tenant';
        $candidate = $base;
        $counter = 2;

        while (Tenant::where($column, $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardViewData(): array
    {
        $tenants = Tenant::query()
            ->with(['plan', 'subscriptions'])
            ->orderByDesc('created_at')
            ->get();

        $tenantAdmins = $tenants->mapWithKeys(function (Tenant $tenant) {
            return [$tenant->id => $this->resolveTenantAdmin($tenant)];
        });

        $tenantInsights = $tenants->mapWithKeys(function (Tenant $tenant) {
            return [$tenant->id => $this->tenantInsightData($tenant)];
        });

        return [
            'tenantCount' => $tenants->count(),
            'activeTenantCount' => $tenants->where('is_active', true)->count(),
            'planCount' => Plan::active()->count(),
            'subscriptionCount' => \App\Models\TenantSubscription::query()->count(),
            'plans' => Plan::active()
                ->get()
                ->sortBy(fn (Plan $plan) => array_search($plan->slug, array_column(CentralPricing::plans(), 'slug'), true))
                ->values(),
            'tenants' => $tenants,
            'tenantAdmins' => $tenantAdmins,
            'tenantInsights' => $tenantInsights,
        ];
    }

    private function notifyTenantAdmin(Tenant $tenant, callable $callback): bool
    {
        try {
            $admin = $this->resolveTenantAdmin($tenant);

            if (! $admin) {
                return false;
            }

            $callback($admin);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function resolveTenantAdmin(Tenant $tenant): ?User
    {
        try {
            $this->tenantDatabaseManager->activate($tenant);

            return User::on('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('role', User::ROLE_TENANT_ADMIN)
                ->orderBy('id')
                ->first();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @return array{office_count:int, office_staff_count:int, today_queue_count:int, today_appointment_count:int, last_activity_label:string}
     */
    private function tenantInsightData(Tenant $tenant): array
    {
        try {
            $this->tenantDatabaseManager->activate($tenant);

            $officeCount = Office::query()
                ->where('tenant_id', $tenant->id)
                ->count();

            $officeStaffCount = User::on('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('role', User::ROLE_OFFICE_STAFF)
                ->count();

            $todayQueueCount = QueueEntry::query()
                ->where('tenant_id', $tenant->id)
                ->whereDate('queue_date', today())
                ->count();

            $todayAppointmentCount = Appointment::query()
                ->where('tenant_id', $tenant->id)
                ->whereDate('appointment_date', today())
                ->count();

            $lastActivity = ActivityLog::query()
                ->where('tenant_id', $tenant->id)
                ->latest('created_at')
                ->first();

            $lastActivityLabel = $lastActivity
                ? sprintf('%s, %s', str($lastActivity->action)->replace('_', ' ')->title(), $lastActivity->created_at?->diffForHumans() ?? 'recently')
                : 'No recent workspace activity';

            return [
                'office_count' => $officeCount,
                'office_staff_count' => $officeStaffCount,
                'today_queue_count' => $todayQueueCount,
                'today_appointment_count' => $todayAppointmentCount,
                'last_activity_label' => $lastActivityLabel,
            ];
        } catch (\Throwable) {
            return [
                'office_count' => 0,
                'office_staff_count' => 0,
                'today_queue_count' => 0,
                'today_appointment_count' => 0,
                'last_activity_label' => 'Unavailable',
            ];
        }
    }

    private function generateUniqueTenantDatabaseName(string $source): string
    {
        return TenantDatabaseName::generate(
            $source,
            fn (string $candidate): bool => Tenant::where('database_name', $candidate)->exists()
        );
    }

    public function generateReadableTemporaryPassword(int $length = 14): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }
}
