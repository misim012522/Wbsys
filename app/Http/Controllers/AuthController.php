<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Office;
use App\Models\Tenant;
use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\TenantUserRegisterRequest;
use App\Services\LoginAccountResolver;
use App\Services\RecaptchaService;
use App\Services\TenantPlanEnforcer;
use App\Services\TenantDatabaseManager;
use App\Support\TenantUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private LoginAccountResolver $loginAccountResolver,
        private RecaptchaService $recaptchaService,
        private TenantDatabaseManager $tenantDatabaseManager,
        private TenantPlanEnforcer $tenantPlanEnforcer,
    ) {}

    public function showLogin()
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->tenant && ! $this->currentTenant()) {
                return redirect()->away(TenantUrl::dashboard($user->tenant, $user));
            }

            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (config('recaptcha.secret_key')) {
            if (! $this->recaptchaService->verify($request->input('g-recaptcha-response'), $request->ip())) {
                return back()->withErrors([
                    'g-recaptcha-response' => 'reCAPTCHA verification failed. Please try again.',
                ])->onlyInput('login');
            }
        }

        $account = $this->loginAccountResolver->resolve($validated['login'], $validated['password']);

        if ($account['status'] === 'ambiguous') {
            return back()->withErrors([
                'login' => 'This account matches more than one workspace. Use a unique email or username for that account.',
            ])->onlyInput('login');
        }

        if ($account['status'] !== 'matched') {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->onlyInput('login');
        }

        /** @var User $user */
        $user = $account['user'];
        /** @var Tenant|null $tenant */
        $tenant = $account['tenant'] ?? null;
        $requestedTenant = $this->requestedTenant($request);

        if ($requestedTenant && (! $tenant || (int) $tenant->id !== (int) $requestedTenant->id)) {
            return back()->withErrors([
                'login' => 'Please use the login credentials assigned to this tenant workspace.',
            ])->onlyInput('login');
        }

        if (! $user->isAdmin() && $user->isPending()) {
            return back()->withErrors([
                'login' => 'Your account is pending approval. You will receive an email when an administrator confirms your account.',
            ])->onlyInput('login');
        }

        if ($user->isArchived()) {
            return back()->withErrors([
                'login' => 'Your account has been archived. Contact your administrator.',
            ])->onlyInput('login');
        }

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'login' => 'You must verify your email before signing in. Please check your inbox for the confirmation link.',
            ])->onlyInput('login');
        }

        if (! $this->isCurrentHostFor($tenant)) {
            if ($tenant !== null && $this->isTenantHostMismatch($request, $tenant)) {
                return back()->withErrors([
                    'login' => 'Please use your assigned tenant domain: '.TenantUrl::login($tenant),
                ])->onlyInput('login');
            }

            return redirect()->away($this->handoffUrl($tenant, $user, $request->boolean('remember')));
        }

        $this->signIn($request, $user, $request->boolean('remember'), $tenant);

        return redirect()->away(TenantUrl::forUserDashboard($user));
    }

    public function continueLogin(Request $request): RedirectResponse
    {
        $payload = $this->handoffPayload((string) $request->query('token'));
        $remember = (bool) ($payload['remember'] ?? false);
        $target = $payload['target'] ?? null;

        if ($target === 'tenant') {
            $tenant = $this->currentTenant();

            abort_unless($tenant && (int) $tenant->id === (int) ($payload['tenant_id'] ?? 0), 403);

            $this->tenantDatabaseManager->activate($tenant);

            $user = User::on('tenant')->findOrFail($payload['user_id'] ?? 0);
            abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);
            $user->setConnection('tenant');

            $this->signIn($request, $user, $remember, $tenant);

            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        abort_unless($target === 'central', 403);
        abort_unless(! app()->bound('current_tenant'), 403);

        $user = User::on('central')->whereNull('tenant_id')->findOrFail($payload['user_id'] ?? 0);
        $user->setConnection('central');

        $this->signIn($request, $user, $remember);

        return redirect()->away(TenantUrl::forUserDashboard($user));
    }

    public function showRegister()
    {
        $offices = Office::active()->orderedByName()->get();
        return view('auth.register', compact('offices'));
    }

    public function showTenantRegister()
    {
        $tenant = $this->currentTenant();

        if (! $tenant) {
            return redirect()->route('tenant.home')->with('error', 'Tenant signup is only available inside a tenant workspace.');
        }

        return view('tenant.register', compact('tenant'));
    }

    public function showVerificationSent()
    {
        return view('auth.verification-sent');
    }

    public function showRegistrationPending()
    {
        return view('auth.registration-pending');
    }

    /** Officer self-registration (select office, then pending until admin approves). */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $office = \App\Models\Office::find($validated['office_id']);

        if ($office?->tenant && $this->tenantPlanEnforcer->userLimitReached($office->tenant)) {
            return back()->withInput()->withErrors([
                'office_id' => 'This tenant has reached the maximum number of users allowed by its current subscription plan.',
            ]);
        }

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_OFFICE_STAFF,
            'tenant_id' => $office?->tenant_id,
            'office_id' => $validated['office_id'],
            'approved_at' => null,
        ]);

        return redirect()->route('registration.pending');
    }

    public function registerTenantUser(TenantUserRegisterRequest $request)
    {
        $tenant = $this->currentTenant();

        if (! $tenant) {
            return redirect()->route('tenant.home')->with('error', 'Tenant signup is only available inside a tenant workspace.');
        }

        if ($this->tenantPlanEnforcer->userLimitReached($tenant)) {
            return back()->withInput()->withErrors([
                'email' => 'This tenant has reached the maximum number of users allowed by its current subscription plan.',
            ]);
        }

        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_STUDENT,
            'tenant_id' => $tenant->id,
            'office_id' => null,
            'approved_at' => null,
        ]);

        return redirect()->route('registration.pending')->with('status', 'Your tenant account has been created and is waiting for admin approval.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->office_id) {
            ActivityLog::log(
                $user->office_id,
                'logout',
                $user->name . ' logged out',
                $user->id,
                null,
                null,
                null,
                $request->ip()
            );
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function currentTenant(): ?Tenant
    {
        return app()->bound('current_tenant') ? app('current_tenant') : null;
    }

    private function requestedTenant(Request $request): ?Tenant
    {
        $currentTenant = $this->currentTenant();

        if ($currentTenant) {
            return $currentTenant;
        }

        $tenantId = (int) $request->integer('tenant_id');

        if ($tenantId <= 0) {
            return null;
        }

        return Tenant::active()->find($tenantId);
    }

    private function handoffUrl(?Tenant $tenant, User $user, bool $remember): string
    {
        $payload = Crypt::encryptString(json_encode([
            'target' => $tenant ? 'tenant' : 'central',
            'tenant_id' => $tenant?->id,
            'user_id' => $user->id,
            'remember' => $remember,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ], JSON_THROW_ON_ERROR));

        return TenantUrl::authContinue($tenant).'?token='.urlencode($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function handoffPayload(string $token): array
    {
        abort_if($token === '', 403);

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            abort(403);
        }

        abort_unless(is_array($payload), 403);
        abort_unless((int) ($payload['expires_at'] ?? 0) >= now()->timestamp, 403);

        return $payload;
    }

    private function isCurrentHostFor(?Tenant $tenant): bool
    {
        $currentTenant = $this->currentTenant();

        if ($tenant === null) {
            return $currentTenant === null;
        }

        return $currentTenant && (int) $currentTenant->id === (int) $tenant->id;
    }

    private function isTenantHostMismatch(Request $request, Tenant $tenant): bool
    {
        $currentHost = preg_replace('/:\d+$/', '', (string) ($request->server('HTTP_HOST') ?: $request->getHost()));
        $tenantHost = parse_url(TenantUrl::login($tenant), PHP_URL_HOST);

        if (! is_string($tenantHost) || $tenantHost === '') {
            return false;
        }

        $rootHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $currentHost !== ''
            && $currentHost !== $tenantHost
            && $currentHost !== $rootHost
            && ! in_array($currentHost, ['127.0.0.1', 'localhost'], true);
    }

    private function signIn(Request $request, User $user, bool $remember, ?Tenant $tenant = null): void
    {
        if ($tenant) {
            $this->tenantDatabaseManager->activate($tenant);
            app()->instance('current_tenant', $tenant);
            app()->instance('current_tenant_id', $tenant->id);
            $user->setConnection('tenant');
        } else {
            $user->setConnection('central');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->office_id) {
            ActivityLog::log(
                $user->office_id,
                'login',
                $user->name . ' logged in',
                $user->id,
                null,
                null,
                null,
                $request->ip()
            );
        }
    }
}
