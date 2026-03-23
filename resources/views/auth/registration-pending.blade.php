@extends('layouts.app')

@section('title', 'Account pending approval')

@section('content')
<div class="-mx-4 -my-8 flex min-h-[calc(100vh-4rem)] items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-sky-50 px-4 py-16 sm:px-6">
    <div class="w-full max-w-[480px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-xl ring-1 ring-slate-100 sm:p-10 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600 mb-6">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-3">Account pending approval</h1>

            <p class="text-slate-600 mb-6">
                Your registration has been received. A tenant administrator will review your account and confirm it. You will receive an email at the address you provided once your account is confirmed. After approval, you can log in and use the tenant app.
            </p>

            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
                Back to login
            </a>
        </div>
    </div>
</div>
@endsection
