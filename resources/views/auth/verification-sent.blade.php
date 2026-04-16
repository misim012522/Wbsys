@extends('layouts.app')

@section('title', 'Verify your email')

@section('content')
<div class="auth-stage flex items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-sky-50">
    <div class="w-full max-w-[480px]">
        {{-- Modal-style popup card --}}
        <div id="verification-modal" class="animate-fade-in rounded-2xl border border-emerald-200 bg-white p-8 shadow-2xl ring-1 ring-emerald-100 sm:p-10 text-center">
            {{-- Success icon with animation --}}
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-6 animate-bounce-subtle">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            {{-- Main message --}}
            <h1 class="text-2xl font-bold text-slate-800 mb-3">Account Created Successfully!</h1>
            
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6">
                <p class="text-emerald-800 font-medium text-sm mb-2">
                    📧 Confirmation Email Sent
                </p>
                <p class="text-emerald-700 text-sm">
                    Please check your inbox and click the confirmation link to verify your account.
                </p>
            </div>

            <div class="text-left bg-slate-50 rounded-xl p-4 mb-6 space-y-2 text-sm text-slate-600">
                <p class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold">1.</span>
                    <span>Open the email we just sent you</span>
                </p>
                <p class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold">2.</span>
                    <span>Click the confirmation link in the email</span>
                </p>
                <p class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold">3.</span>
                    <span>Return here to log in and start managing your queue</span>
                </p>
            </div>

            <p class="text-sm text-slate-500 mb-6">
                Redirecting to login in <span id="countdown" class="font-bold text-emerald-600">10</span> seconds…
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                    Go to Login
                </a>
            </div>

            <p class="mt-6 text-xs text-slate-400">
                Didn't receive the email? Check your spam folder or contact support.
            </p>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounceSubtle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.animate-bounce-subtle {
    animation: bounceSubtle 2s ease-in-out infinite;
}
</style>

<script>
(function () {
    var seconds = 10;
    var el = document.getElementById('countdown');
    var interval = setInterval(function () {
        seconds--;
        if (el) el.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.href = '{{ route('login') }}';
        }
    }, 1000);
})();
</script>
@endsection
