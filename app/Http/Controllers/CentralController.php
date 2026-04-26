<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentralTenantSignupRequest;
use App\Models\ActivityLog;
use App\Models\Office;
use App\Models\Plan;
use App\Models\QueueEntry;
use App\Models\RegistrationPayment;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Notifications\TenantActivationStatusNotification;
use App\Notifications\TenantCredentialsNotification;
use App\Notifications\TenantSubscriptionUpdatedNotification;
use App\Notifications\TenantWorkspaceAccessNotification;
use App\Services\TenantDatabaseManager;
use App\Services\TenantRbacService;
use App\Services\StripeCheckoutService;
use App\Support\CentralPricing;
use App\Support\TenantDashboardProfile;
use App\Support\TenantDatabaseName;
use App\Support\TenantWorkspaceUrlValidator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CentralController extends Controller
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager,
        private TenantRbacService $tenantRbacService,
        private StripeCheckoutService $stripeCheckoutService,
    ) {}

    public function home(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        abort_unless($user instanceof User && $user->isCentralUser(), 403);

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
        $this->tenantRbacService->updateFromRequest($tenant, $request->all());

        return redirect()->route('central.tenants.rbac.edit', $tenant)
            ->with('success', "Access control for {$tenant->name} was updated successfully.");
    }

    public function editRbac(Tenant $tenant): View
    {
        return view('admin.rbac', array_merge(
            $this->tenantRbacService->viewData($tenant),
            [
                'pageMode' => 'central',
                'pageTitle' => "Access Control: {$tenant->name}",
                'pageDescription' => 'Configure RBAC for this specific registered tenant from the central workspace.',
                'saveAction' => route('central.tenants.rbac', $tenant),
                'saveMethod' => 'PATCH',
                'saveButtonLabel' => 'Save tenant access',
                'backUrl' => route('central.dashboard'),
                'backLabel' => 'Back to central dashboard',
            ]
        ));
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

    public function approve(Tenant $tenant): RedirectResponse
    {
        if ($tenant->approved_at) {
            return redirect()->route('central.dashboard')
                ->with('info', "Tenant {$tenant->name} is already approved.");
        }

        $newPassword = $this->generateReadableTemporaryPassword();
        $tenantApproved = false;
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
                    ->with('error', "No tenant admin account was found for {$tenant->name}. Please reset the tenant setup first.");
            }

            $admin->forceFill([
                'password' => Hash::make($newPassword),
            ])->save();

            $tenant->forceFill([
                'approved_at' => now(),
                'is_active' => true,
            ])->save();
            $tenantApproved = true;

            $admin->notify(new TenantCredentialsNotification($tenant, $newPassword));
            $notificationSent = true;
        } catch (\Throwable $e) {
            report($e);
        }

        $response = redirect()->route('central.dashboard');

        if (! $tenantApproved) {
            return $response->with('error', "Tenant {$tenant->name} could not be approved. Please try again.");
        }

        if (! $notificationSent) {
            return $response
                ->with('info', "Tenant {$tenant->name} was approved, but the credentials email could not be sent.");
        }

        return $response
            ->with('success', "Tenant {$tenant->name} was approved and credentials were sent by email.");
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
        ]);

        if ($validator->fails()) {
            return redirect()->route('central.dashboard')
                ->withErrors($validator, 'tenantSubscription_'.$tenant->id)
                ->withInput()
                ->with('open_modal', 'tenant-subscription-modal-'.$tenant->id);
        }

        $validated = $validator->validated();
        $startsAt = Carbon::parse($validated['starts_at']);

        $plan = Plan::active()->findOrFail($validated['plan_id']);
        $subscription = $tenant->subscriptions()->latest('id')->first() ?? new TenantSubscription(['tenant_id' => $tenant->id]);

        $subscription->fill([
            'plan_id' => $plan->id,
            'status' => $validated['status'],
            'starts_at' => $startsAt,
            'ends_at' => TenantSubscription::calculateMonthlyEndAt($startsAt),
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
        if (! $tenant->approved_at) {
            return redirect()->route('central.dashboard')
                ->with('info', "Approve {$tenant->name} first before sending workspace access details.");
        }

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
        if (! $tenant->approved_at) {
            return redirect()->route('central.dashboard')
                ->with('info', "Approve {$tenant->name} first before sending tenant credentials.");
        }

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
        $plans = $this->availablePlans();
        $planSlugs = $plans->pluck('slug')->filter()->values()->all();
        $planCounts = \App\Models\Plan::whereIn('slug', $planSlugs)
            ->withCount('tenants')
            ->get()
            ->mapWithKeys(fn($p) => [$p->slug => $p->tenants_count]);

        return view('central.register', [
            'plans' => $plans,
            // institutional licenses removed
            'planCounts' => $planCounts,
        ]);
    }

    public function pricing(): View
    {
        $plans = collect(\App\Support\CentralPricing::plans());

        // Count tenants currently assigned to each plan
        $planSlugs = $plans->pluck('slug')->filter()->values()->all();
        $planCounts = \App\Models\Plan::whereIn('slug', $planSlugs)
            ->withCount('tenants')
            ->get()
            ->mapWithKeys(fn($p) => [$p->slug => $p->tenants_count]);

        return view('central.pricing', [
            'plans' => $plans,
            // institutional licenses removed
            'planCounts' => $planCounts,
        ]);
    }

    public function store(CentralTenantSignupRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plan = Plan::active()->findOrFail((int) $validated['plan_id']);
        $amountCents = (int) round(((float) $plan->price_monthly) * 100);
        $simulateCheckout = (bool) config('services.stripe.simulate', false);

        if (! Schema::connection('central')->hasTable('registration_payments')) {
            return back()->withInput()->withErrors([
                'tenant_name' => 'Payment setup is incomplete: registration payments table is missing. Please run central migrations.',
            ]);
        }

        if ($amountCents <= 0) {
            return back()->withInput()->withErrors(['plan_id' => 'Selected plan has invalid payment amount.']);
        }

        try {
            $payment = RegistrationPayment::create([
                'reference' => (string) Str::uuid(),
                'plan_id' => $plan->id,
                'email' => $validated['email'],
                'provider' => 'stripe',
                'amount_cents' => $amountCents,
                'currency' => (string) config('services.stripe.currency', 'usd'),
                'status' => RegistrationPayment::STATUS_PENDING,
                'payload' => $validated,
            ]);

            $successUrl = route('central.register.payment.success', ['ref' => $payment->reference, 'session_id' => '{CHECKOUT_SESSION_ID}']);
            $cancelUrl = route('central.register.payment.cancel', ['ref' => $payment->reference]);

            if ($simulateCheckout) {
                return redirect()->route('central.register.payment.fake', [
                    'ref' => $payment->reference,
                ]);
            }

            $session = $this->stripeCheckoutService->createCheckoutSession([
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $payment->reference,
                'currency' => $payment->currency,
                'amount_cents' => $payment->amount_cents,
                'product_name' => 'Tenant Registration - '.($plan->name ?: strtoupper($plan->slug)),
                'payment_reference' => $payment->reference,
            ]);

            $payment->forceFill([
                'provider_session_id' => $session['id'] ?? null,
            ])->save();

            $checkoutUrl = $session['url'] ?? null;
            if (! $checkoutUrl) {
                throw new \RuntimeException('Payment checkout URL missing.');
            }

            return redirect()->away($checkoutUrl);
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->withErrors([
                'tenant_name' => 'Payment setup is incomplete in central DB. Please run central migrations and try again.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'tenant_name' => 'Unable to start payment checkout. Please try again.',
            ]);
        }
    }

    public function paymentSuccess(Request $request): RedirectResponse
    {
        $ref = (string) $request->query('ref', '');
        $sessionId = (string) $request->query('session_id', '');
        $simulateCheckout = (bool) config('services.stripe.simulate', false);

        $payment = RegistrationPayment::query()->where('reference', $ref)->first();
        if (! $payment || $sessionId === '') {
            return redirect()->route('central.register')->withErrors(['tenant_name' => 'Invalid payment session. Please try again.']);
        }

        if ($payment->finalized_at) {
            return redirect()->route('login')->with('success', 'Registration already completed. You may now log in after central approval.');
        }

        try {
            if ($simulateCheckout) {
                $paid = str_starts_with($sessionId, 'sim_');
            } else {
                $session = $this->stripeCheckoutService->retrieveCheckoutSession($sessionId);
                $paid = ($session['payment_status'] ?? null) === 'paid';
            }

            if (! $paid) {
                return redirect()->route('central.register')->withErrors(['tenant_name' => 'Payment is not completed yet.']);
            }

            $payment->forceFill([
                'status' => RegistrationPayment::STATUS_PAID,
                'provider_session_id' => $sessionId,
                'paid_at' => now(),
            ])->save();

            $tenant = $this->finalizeTenantRegistrationFromPayment($payment);

            return redirect()->route('login')->with(
                'success',
                sprintf('Payment received. Tenant %s has been registered and is pending central approval.', $tenant->name)
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('central.register')->withErrors(['tenant_name' => 'Could not verify payment. Please contact support.']);
        }
    }

    public function paymentCancel(Request $request): RedirectResponse
    {
        $ref = (string) $request->query('ref', '');
        $payment = RegistrationPayment::query()->where('reference', $ref)->first();

        if ($payment && $payment->status === RegistrationPayment::STATUS_PENDING) {
            $payment->forceFill(['status' => RegistrationPayment::STATUS_CANCELLED])->save();
        }

        return redirect()->route('central.register')->withErrors(['tenant_name' => 'Payment was cancelled. Registration was not completed.']);
    }

    public function fakePayment(Request $request): View|RedirectResponse
    {
        $simulateCheckout = (bool) config('services.stripe.simulate', false);
        if (! $simulateCheckout) {
            return redirect()->route('central.register');
        }

        $ref = (string) $request->query('ref', '');
        $payment = RegistrationPayment::query()->where('reference', $ref)->first();
        if (! $payment) {
            return redirect()->route('central.register')->withErrors(['tenant_name' => 'Invalid payment reference.']);
        }

        return view('central.payment-fake', ['payment' => $payment]);
    }

    public function fakePaymentProcess(Request $request): RedirectResponse
    {
        $simulateCheckout = (bool) config('services.stripe.simulate', false);
        if (! $simulateCheckout) {
            return redirect()->route('central.register');
        }

        $validated = $request->validate([
            'ref' => ['required', 'string'],
            'card_name' => ['required', 'string', 'max:255'],
            'card_number' => ['required', 'string', 'max:25'],
            'expiry' => ['required', 'string', 'max:10'],
            'cvc' => ['required', 'string', 'max:6'],
        ]);

        $payment = RegistrationPayment::query()->where('reference', $validated['ref'])->first();
        if (! $payment) {
            return redirect()->route('central.register')->withErrors(['tenant_name' => 'Invalid payment reference.']);
        }

        if ($payment->finalized_at) {
            return redirect()->route('login')->with('success', 'Registration already completed.');
        }

        $payment->forceFill([
            'status' => RegistrationPayment::STATUS_PAID,
            'provider_session_id' => 'fake_'.$payment->reference,
            'paid_at' => now(),
        ])->save();

        $tenant = $this->finalizeTenantRegistrationFromPayment($payment);

        return redirect()->route('login')->with(
            'success',
            sprintf('Payment received. Tenant %s has been registered and is pending central approval.', $tenant->name)
        );
    }

    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = (string) $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret', '');

        if (! $this->isValidStripeWebhookSignature($payload, $signature, $secret)) {
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return response()->json(['ok' => false, 'error' => 'Invalid payload'], 400);
        }

        $eventType = (string) ($event['type'] ?? '');
        $session = $event['data']['object'] ?? null;

        if ($eventType !== 'checkout.session.completed' || ! is_array($session)) {
            return response()->json(['ok' => true]);
        }

        $sessionId = (string) ($session['id'] ?? '');
        $paymentStatus = (string) ($session['payment_status'] ?? '');
        $reference = (string) ($session['metadata']['payment_reference'] ?? $session['client_reference_id'] ?? '');

        if ($sessionId === '' || $reference === '' || $paymentStatus !== 'paid') {
            return response()->json(['ok' => true]);
        }

        $payment = RegistrationPayment::query()
            ->where('reference', $reference)
            ->first();

        if (! $payment) {
            return response()->json(['ok' => true]);
        }

        if ($payment->finalized_at) {
            return response()->json(['ok' => true, 'idempotent' => true]);
        }

        $payment->forceFill([
            'status' => RegistrationPayment::STATUS_PAID,
            'provider_session_id' => $sessionId,
            'paid_at' => now(),
        ])->save();

        $this->finalizeTenantRegistrationFromPayment($payment);

        return response()->json(['ok' => true]);
    }

    private function isValidStripeWebhookSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($payload === '' || $signatureHeader === '' || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            $pair = explode('=', trim($segment), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        $timestamp = $parts['t'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (! $timestamp || ! $v1) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $v1);
    }

    private function finalizeTenantRegistrationFromPayment(RegistrationPayment $payment): Tenant
    {
        $validated = $payment->payload ?? [];
        $plan = Plan::active()->findOrFail((int) $payment->plan_id);

        $tenant = null;
        $generatedPassword = $this->generateReadableTemporaryPassword();

        DB::connection('central')->transaction(function () use (&$tenant, $validated, $plan, $payment): void {
            $tenantName = trim((string) ($validated['tenant_name'] ?? 'Tenant'));
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
                'email' => $validated['email'] ?? $payment->email,
                'contact_number' => $validated['contact_number'] ?? null,
                'settings' => [
                    'database' => ['mode' => 'dedicated'],
                    'theme' => [
                        'primary_color' => '#2563eb',
                        'app_name' => $tenantName,
                        'logo_url' => null,
                    ],
                    'dashboard' => [
                        'profile' => TenantDashboardProfile::inferFromName($tenantName),
                    ],
                ],
                'is_active' => false,
                'approved_at' => null,
            ]);
            $tenant->save();

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'starts_at' => $tenant->created_at ?? now(),
                'ends_at' => TenantSubscription::calculateMonthlyEndAt(($tenant->created_at ?? now())->copy()),
                'status' => TenantSubscription::STATUS_ACTIVE,
            ]);

            $payment->forceFill([
                'tenant_id' => $tenant->id,
                'finalized_at' => now(),
            ])->save();
        });

        if (! $tenant) {
            throw new \RuntimeException('Unable to finalize tenant registration.');
        }

        try {
            $adminData = [
                'name' => $tenant->name.' Admin',
                'username' => (string) ($validated['tenant_admin_username'] ?? 'admin'),
                'email' => (string) ($validated['email'] ?? $payment->email),
                'phone' => (string) ($validated['contact_number'] ?? ''),
                'password' => $generatedPassword,
            ];

            $this->tenantDatabaseManager->provision($tenant, $adminData);
        } catch (\Throwable $e) {
            rescue(fn () => $this->tenantDatabaseManager->deleteTenantArtifacts($tenant), report: false);
            rescue(fn () => $tenant->delete(), report: false);
            throw $e;
        }

        return $tenant;
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

        $latestPayments = \App\Models\RegistrationPayment::query()
            ->whereIn('tenant_id', $tenants->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('tenant_id')
            ->keyBy('tenant_id');

        return [
            'tenantCount' => $tenants->count(),
            'activeTenantCount' => $tenants->where('is_active', true)->count(),
            'planCount' => $this->availablePlans()->count(),
            'subscriptionCount' => \App\Models\TenantSubscription::query()->count(),
            'plans' => $this->availablePlans(),
            'tenants' => $tenants,
            'tenantAdmins' => $tenantAdmins,
            'tenantInsights' => $tenantInsights,
            'latestPayments' => $latestPayments,
        ];
    }

    private function availablePlans()
    {
        $allowedSlugs = array_column(CentralPricing::plans(), 'slug');
        $planOrder = array_flip($allowedSlugs);

        return Plan::active()
            ->whereIn('slug', $allowedSlugs)
            ->get()
            ->sortBy(fn (Plan $plan) => $planOrder[$plan->slug] ?? PHP_INT_MAX)
            ->unique('slug')
            ->values();
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
     * @return array{office_count:int, office_staff_count:int, today_queue_count:int, last_activity_label:string}
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
                'last_activity_label' => $lastActivityLabel,
            ];
        } catch (\Throwable) {
            return [
                'office_count' => 0,
                'office_staff_count' => 0,
                'today_queue_count' => 0,
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
