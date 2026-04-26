@extends('layouts.public')

@section('title', 'Simulated Payment')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-10 sm:px-6">
    <div class="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">
            Simulation Mode
        </div>

        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Complete payment</h1>
        <p class="mt-2 text-slate-600">This is a local simulation page. No real card charge will happen.</p>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <p><strong>Reference:</strong> {{ $payment->reference }}</p>
            <p><strong>Amount:</strong> ${{ number_format(($payment->amount_cents ?? 0) / 100, 2) }} {{ strtoupper($payment->currency ?? 'usd') }}</p>
            <p><strong>Email:</strong> {{ $payment->email }}</p>
        </div>

        <form method="POST" action="{{ route('central.register.payment.fake.process') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="ref" value="{{ $payment->reference }}">

            <div>
                <label class="block text-sm font-medium text-slate-700">Card holder name</label>
                <input name="card_name" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Any name" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Card number</label>
                <input name="card_number" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Any dummy number" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Expiry</label>
                    <input name="expiry" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="MM/YY" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">CVC</label>
                    <input name="cvc" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Any" required>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Pay (Simulated)
                </button>

                <a href="{{ route('central.register.payment.cancel', ['ref' => $payment->reference]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
