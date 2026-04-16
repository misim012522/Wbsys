@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
<div class="auth-stage flex items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-sky-50">
    <div class="w-full max-w-[420px]">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/50 ring-1 ring-slate-100 sm:p-10">
            <div class="mb-6 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-emerald-600 font-bold text-xl">QueueLess</a>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Reset password</h1>
            <p class="mt-1 text-sm text-slate-500">Enter your new password below.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">New password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    Reset password
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" class="font-medium text-emerald-600 hover:underline">Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection
