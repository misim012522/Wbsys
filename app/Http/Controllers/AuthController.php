<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\Office;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoginAccountResolver;
use App\Services\RecaptchaService;
use App\Services\TenantDatabaseManager;
use App\Services\TenantPlanEnforcer;
use App\Support\TenantDisabledResponse;
use App\Support\TenantUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private LoginAccountResolver $loginAccountResolver,
        private RecaptchaService $recaptchaService,
        private TenantDatabaseManager $tenantDatabaseManager,
        private TenantPlanEnforcer $tenantPlanEnforcer,
    ) {}

    public function showLogin(Request $request)
    {
        $hostTenant = $this->tenantForHost($request, includeInactive: true);

        if (
            $hostTenant
            && ! $hostTenant->is_active
            && auth()->check()
            && (int) (auth()->user()?->tenant_id ?? 0) === (int) $hostTenant->id
        ) {
            return $this->logoutDueToDeactivation($request);
        }

        if ($hostTenant && ! $hostTenant->is_active) {
            return TenantDisabledResponse::make($hostTenant, $request);
        }

        if ($hostTenant) {
            app()->instance('current_tenant', $hostTenant);
            app()->instance('current_tenant_id', $hostTenant->id);
        }

        if ($request->boolean('force_login') && auth()->check()) {
            $this->clearAuthenticatedSession($request);

            return $this->loginViewResponse($request);
        }

        if (auth()->check()) {
            $user = auth()->user();
            $tenantWorkspace = $this->currentTenant() ?? $this->tenantForHost($request);
            Log::info('[DEBUG-LOGIN] showLogin: authenticated user', [
                'user_id' => $user->id,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'office_id' => $user->office_id,
                'workspace_id' => $tenantWorkspace?->id,
                'is_central' => $user->isCentralUser(),
                'url_intended' => session()->get('url.intended'),
            ]);

            if ($tenantWorkspace && $user->isCentralUser()) {
                Log::info('[DEBUG-LOGIN] showLogin -> tenant.home (central user)');
                return redirect()->route('tenant.home');
            }

            if ($tenantWorkspace && (int) ($user->tenant_id ?? 0) === (int) $tenantWorkspace->id) {
                Log::info('[DEBUG-LOGIN] showLogin -> dashboardRedirect (tenant user matches workspace)');

                if ($user->isOfficeStaff()) {
                    return $this->officeDashboardResponse($request);
                }

                return $this->dashboardRedirect($user);
            }

            if ($tenantWorkspace) {
                Log::info('[DEBUG-LOGIN] showLogin -> view login (tenant workspace mismatch)');
                return $this->loginViewResponse($request);
            }

            if ($user->tenant && ! $this->currentTenant()) {
                return redirect()->away(TenantUrl::dashboard($user->tenant, $user));
            }

            return $this->dashboardRedirect($user);
        }

        Log::info('[DEBUG-LOGIN] showLogin: not authenticated, showing login form');
        return $this->loginViewResponse($request);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (auth()->check()) {
            $this->clearAuthenticatedSession($request);
        }

        if (config('recaptcha.enabled') && ! app()->environment(['local', 'testing']) && config('recaptcha.secret_key')) {
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

        if ($tenant && ! $tenant->is_active) {
            return TenantDisabledResponse::make($tenant, $request);
        }

        if ($requestedTenant && (! $tenant || (int) $tenant->id !== (int) $requestedTenant->id)) {
            return back()->withErrors([
                'login' => 'Please use the login credentials assigned to this tenant workspace.',
            ])->onlyInput('login');
        }

        if (! $user->isTenantAdmin() && $user->isPending()) {
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

        if ($user->isOfficeStaff()) {
            return $this->officeDashboardResponse($request);
        }

        return $this->dashboardRedirect($user);
    }

    public function continueLogin(Request $request): RedirectResponse|Response
    {
        $payload = $this->handoffPayload((string) $request->query('token'));
        $remember = (bool) ($payload['remember'] ?? false);
        $target = $payload['target'] ?? null;

        if ($target === 'tenant') {
            $tenant = $this->currentTenant();

            abort_unless($tenant && (int) $tenant->id === (int) ($payload['tenant_id'] ?? 0), 403);
            if (! $tenant->is_active) {
                return $this->logoutDueToDeactivation($request);
            }

            $this->tenantDatabaseManager->activate($tenant);

            $user = User::on('tenant')->findOrFail($payload['user_id'] ?? 0);
            abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);
            $user->setConnection('tenant');

            $this->signIn($request, $user, $remember, $tenant);

            return $this->dashboardRedirect($user);
        }

        abort_unless($target === 'central', 403);
        abort_unless(! app()->bound('current_tenant'), 403);

        $user = User::on('central')->whereNull('tenant_id')->findOrFail($payload['user_id'] ?? 0);
        $user->setConnection('central');

        $this->signIn($request, $user, $remember);

        return $this->dashboardRedirect($user);
    }

    public function showRegister()
    {
        $offices = Office::active()->orderedByName()->get();

        return view('auth.register', compact('offices'));
    }

    public function showTenantRegister()
    {
        return redirect()->route('login')
            ->with('info', 'Tenant end users should continue using the QR, queue, and appointment pages. Workspace accounts are only for tenant admins and office staff.');
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

    public function registerTenantUser(Request $request)
    {
        return redirect()->route('login')
            ->with('info', 'Tenant end users should continue using the QR, queue, and appointment pages. Workspace accounts are only for tenant admins and office staff.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->office_id) {
            ActivityLog::log(
                $user->office_id,
                'logout',
                $user->name.' logged out',
                $user->id,
                null,
                null,
                null,
                $request->ip()
            );
        }
        Auth::logout();
        $request->session()->forget('tenant_auth');
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
        $request->session()->regenerateToken();

        if ($tenant) {
            $request->session()->put('tenant_auth', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]);
        } else {
            $request->session()->forget('tenant_auth');
        }

        if ($user->office_id) {
            ActivityLog::log(
                $user->office_id,
                'login',
                $user->name.' logged in',
                $user->id,
                null,
                null,
                null,
                $request->ip()
            );
        }

        $request->session()->save();
    }

    private function tenantForHost(Request $request, bool $includeInactive = false): ?Tenant
    {
        $host = preg_replace('/:\d+$/', '', (string) ($request->header('host') ?: $request->server('HTTP_HOST') ?: $request->getHost()));

        if (! is_string($host) || $host === '') {
            return null;
        }

        $query = $includeInactive ? Tenant::query() : Tenant::active();
        $tenant = $query->where('domain', $host)->first();

        if ($tenant || count(explode('.', $host)) < 2) {
            return $tenant;
        }

        $query = $includeInactive ? Tenant::query() : Tenant::active();
        $tenant = $query->where('subdomain', explode('.', $host)[0])->first();

        if ($tenant || ! app()->environment('testing')) {
            return $tenant;
        }

        $activeTenants = Tenant::active()->get();

        return $activeTenants->count() === 1 ? $activeTenants->first() : null;
    }

    private function dashboardRedirect(User $user): RedirectResponse|Response
    {
        $routeName = $user->dashboardRouteName();
        $allowedPaths = match ($routeName) {
            'admin.dashboard' => ['admin'],
            'office.dashboard' => ['office'],
            'office.reports' => ['office/reports'],
            'tenant.settings.edit' => ['settings'],
            'tenant.home' => ['dashboard', 'tenant'],
            'central.dashboard' => ['central/dashboard'],
            default => [],
        };

        Log::info('[DEBUG-LOGIN] dashboardRedirect', [
            'route' => $routeName,
            'user_id' => $user->id,
            'role' => $user->role,
            'office_id' => $user->office_id,
        ]);

        if ($routeName === 'login') {
            return redirect()->route('login');
        }

        $intendedUrl = session()->pull('url.intended');
        $currentHost = request()->getSchemeAndHttpHost();

        if (is_string($intendedUrl) && $intendedUrl !== '') {
            $intendedUrl = $this->normalizeLegacyTenantUrl($intendedUrl, $user);
            $path = trim((string) parse_url($intendedUrl, PHP_URL_PATH), '/');
            Log::info('[DEBUG-LOGIN] dashboardRedirect: url.intended=' . $intendedUrl . ' path=' . $path);

            if (
                ! in_array($path, ['login', 'auth/continue', 'logout'], true)
                && ($allowedPaths === [] || in_array($path, $allowedPaths, true))
            ) {
                if (str_starts_with($intendedUrl, $currentHost)) {
                    $relativeTarget = substr($intendedUrl, strlen($currentHost));

                    return redirect($relativeTarget !== '' ? $relativeTarget : '/', 303);
                }

                return redirect()->to($intendedUrl);
            }
        }

        $targetUrl = route($routeName);
        Log::info('[DEBUG-LOGIN] dashboardRedirect -> route(' . $routeName . ') = ' . $targetUrl);

        if (str_starts_with($targetUrl, $currentHost)) {
            $relativeTarget = substr($targetUrl, strlen($currentHost));

            return redirect($relativeTarget !== '' ? $relativeTarget : '/', 303);
        }

        return redirect()->to($targetUrl, 303);
    }

    private function normalizeLegacyTenantUrl(string $url, User $user): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || ! str_ends_with($host, '.localhost')) {
            return $url;
        }

        $currentWorkspaceUrl = TenantUrl::forUserDashboard($user);
        $currentHost = parse_url($currentWorkspaceUrl, PHP_URL_HOST);

        if (! is_string($currentHost) || $currentHost === '') {
            return $url;
        }

        return str_replace($host, $currentHost, $url);
    }

    private function logoutDueToDeactivation(Request $request): RedirectResponse
    {
        $this->clearAuthenticatedSession($request);

        return redirect()->away(TenantUrl::login(null, true))
            ->with('info', 'Logging out due to deactivation.');
    }

    private function clearAuthenticatedSession(Request $request): void
    {
        Auth::logout();
        $request->session()->forget('tenant_auth');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function loginViewResponse(Request $request): Response
    {
        $request->session()->regenerateToken();

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    private function officeDashboardResponse(Request $request): Response|RedirectResponse
    {
        $request->session()->forget('url.intended');
        $request->session()->save();

        return response()
            ->view('auth.redirecting', ['target' => route('office.dashboard')])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }
}
