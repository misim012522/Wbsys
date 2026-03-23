@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="-mx-4 -my-8 flex min-h-[calc(100vh-4rem)] items-center justify-center bg-white px-4 py-16 sm:px-6">
    <div class="w-full max-w-[420px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
            <div class="mb-8 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <span class="text-xl font-bold tracking-tight">QueueLess</span>
                </a>
                <p class="mt-3 text-sm text-slate-500">Smart appointment & queue management</p>
            </div>

            <h1 class="text-2xl font-bold text-slate-800">Welcome back</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if(app()->bound('current_tenant'))
                    Sign in with your tenant administrator or staff account to open this workspace.
                @else
                    Use your account and we will route you to the correct app automatically.
                @endif
            </p>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf
                @if(app()->bound('current_tenant'))
                    <input type="hidden" name="tenant_id" value="{{ app('current_tenant')->id }}">
                @endif
                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700 mb-1.5">Username or email</label>
                    <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="Username or email" autocomplete="username">
                    @error('login')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="Password">
                    @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                </div>
                @if(config('recaptcha.site_key'))
                <div class="flex justify-center">
                    <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
                </div>
                @error('g-recaptcha-response')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                @endif
                <button type="submit"
                    class="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:scale-[0.99]">
                    Log in
                </button>
            </form>

            @if(! app()->bound('current_tenant'))
                <p class="mt-4 text-center text-sm text-slate-500">
                    Register as tenant? <a href="{{ route('central.register') }}" class="font-medium text-emerald-600 hover:text-emerald-700 hover:underline">Register here</a>.
                </p>
            @else
                <p class="mt-4 text-center text-sm text-slate-500">
                    Tenant admin access is created from the central registration flow.
                </p>
            @endif
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">QueueLess - Account-based sign in</p>
    </div>
</div>
@if(config('recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
@endsection
