@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="auth-stage bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(14,165,233,0.12),_transparent_24%),linear-gradient(180deg,_#f7faf9_0%,_#eef4f2_100%)]">
    <div class="mx-auto w-full max-w-xl">
        <section class="rounded-[2rem] border border-white/70 bg-white/90 p-8 shadow-[0_30px_80px_rgba(15,23,42,0.08)] backdrop-blur sm:p-10">
            <div class="mx-auto max-w-[450px]">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm ring-1 ring-slate-200/70">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div>
                            <p class="text-xl font-bold tracking-tight text-slate-900">QueueLess</p>
                            <p class="text-sm text-slate-500">Smart queue management</p>
                        </div>
                    </div>
                </div>

                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Sign in to continue</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    @if(app()->bound('current_tenant'))
                        Enter your workspace credentials below.
                    @else
                        Enter your account credentials below.
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf
                @if(app()->bound('current_tenant'))
                    <input type="hidden" name="tenant_id" value="{{ app('current_tenant')->id }}">
                @endif
                <div>
                    <label for="login" class="mb-2 block text-sm font-medium text-slate-700">Username or email</label>
                    <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                        placeholder="Username or email" autocomplete="username">
                    @error('login')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                        placeholder="Password">
                    @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                </div>
                @if(config('recaptcha.enabled') && config('recaptcha.site_key'))
                <div class="flex justify-center rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
                </div>
                @error('g-recaptcha-response')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                @endif
                <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 px-4 py-3.5 font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-emerald-500/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:scale-[0.99]">
                    Log in
                </button>
            </form>

            @if(! app()->bound('current_tenant'))
                <p class="mt-6 text-center text-sm text-slate-500">
                    Register as tenant? <a href="{{ \App\Support\TenantUrl::centralRegister() }}" class="font-medium text-emerald-600 hover:text-emerald-700 hover:underline">Register here</a>.
                </p>
            @else
                <p class="mt-6 text-center text-sm text-slate-500">
                    Office staff need an account? <a href="{{ route('register') }}" class="font-medium text-emerald-600 hover:text-emerald-700 hover:underline">Register here</a>.
                </p>
                <p class="mt-2 text-center text-sm text-slate-500">
                    Tenant admin access is created from the central registration flow.
                </p>
            @endif
            </div>
        </section>
    </div>
</div>
@if(config('recaptcha.enabled') && config('recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
@endsection
