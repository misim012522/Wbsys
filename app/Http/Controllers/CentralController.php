<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentralTenantSignupRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Notifications\TenantCredentialsNotification;
use App\Services\TenantDatabaseManager;
use App\Support\CentralPricing;
use App\Support\TenantDashboardProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        $generatedPassword = Str::password(14, true, true, false, false);
        $admin = null;
        $mailDeliveryFailed = false;

        try {
            DB::connection('central')->transaction(function () use ($validated, $plan, &$tenant): void {
                $tenantName = trim($validated['tenant_name']);
                $slug = $this->generateUniqueTenantValue('slug', $tenantName);
                $subdomain = $this->generateUniqueTenantValue('subdomain', $tenantName);

                $tenant = new Tenant([
                    'name' => $tenantName,
                    'slug' => $slug,
                    'plan_id' => $plan->id,
                    'domain' => null,
                    'subdomain' => $subdomain,
                    'database_name' => $this->generateUniqueTenantDatabaseName($subdomain ?: $slug),
                    'address' => $validated['address'] ?? null,
                    'email' => $validated['email'],
                    'contact_number' => $validated['contact_number'],
                    'settings' => [
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

            $admin = $this->tenantDatabaseManager->provision($tenant, [
                'name' => $tenant->name.' Admin',
                'username' => 'admin',
                'email' => $validated['email'],
                'phone' => $validated['contact_number'],
                'password' => $generatedPassword,
            ]);

            try {
                $admin->notify(new TenantCredentialsNotification($tenant, $generatedPassword));
            } catch (\Throwable $e) {
                $mailDeliveryFailed = true;
                report($e);
            }
        } catch (\Throwable $e) {
            if ($tenant) {
                rescue(fn () => $this->tenantDatabaseManager->deleteDatabase($tenant), report: false);
                rescue(fn () => $tenant->delete(), report: false);
            }

            report($e);

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
            $candidate = $base . '-' . $counter;
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
        ];
    }

    private function generateUniqueTenantDatabaseName(string $source): string
    {
        $prefix = (string) config('database.tenant_database_prefix', 'tenant_');
        $base = Str::snake(Str::slug($source, '_'));
        $base = trim($base, '_') !== '' ? trim($base, '_') : 'tenant';

        $maxBaseLength = max(1, 64 - strlen($prefix));
        $base = substr($base, 0, $maxBaseLength);
        $candidate = $prefix.$base;
        $counter = 2;

        while (Tenant::where('database_name', $candidate)->exists()) {
            $suffix = '_'.$counter;
            $trimmedBase = substr($base, 0, max(1, 64 - strlen($prefix) - strlen($suffix)));
            $candidate = $prefix.$trimmedBase.$suffix;
            $counter++;
        }

        return $candidate;
    }
}
