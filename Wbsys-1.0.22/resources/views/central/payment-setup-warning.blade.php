@extends('layouts.public')

@section('title', 'Payment Setup Required')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-10 sm:px-6">
    <div class="mx-auto max-w-3xl rounded-3xl border border-amber-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-amber-700">
            Payment Setup Required
        </div>

        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Tenant registration is temporarily unavailable</h1>
        <p class="mt-3 text-slate-600">
            Stripe configuration is missing or invalid. Please update environment variables before accepting paid tenant registrations.
        </p>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-600">Detected issues</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @foreach ($issues as $issue)
                    <li>- {{ $issue }}</li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-900 p-4 text-sm text-slate-100">
            <p class="font-semibold">Required .env values</p>
            <pre class="mt-2 overflow-x-auto text-xs leading-6 text-slate-200">STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=usd</pre>
        </div>
    </div>
</div>
@endsection
