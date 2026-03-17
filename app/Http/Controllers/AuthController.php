<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Office;
use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private RecaptchaService $recaptchaService
    ) {}

    public function showLogin()
    {
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

        $user = User::where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
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

        Auth::login($user, $request->boolean('remember'));
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

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        $offices = Office::active()->orderedByName()->get();
        return view('auth.register', compact('offices'));
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
        return redirect()->route('home');
    }
}
