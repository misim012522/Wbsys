@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="text-center py-16">
    <h1 class="text-4xl font-bold text-slate-800 mb-2">QueueLess</h1>
    <p class="text-lg text-slate-600 mb-8">Smart Appointment & Queue Management for School Offices</p>
    <p class="text-slate-500 max-w-xl mx-auto mb-10">Scan the QR code at the office to get a queue number or book an appointment. No account needed.</p>
    @guest
        <div class="flex gap-4 justify-center flex-wrap">
            <a href="{{ route('login') }}" class="px-6 py-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Log in</a>
            <a href="{{ route('register') }}" class="px-6 py-3 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Create account</a>
        </div>
        <p class="mt-4 text-sm text-slate-500">Office staff (e.g. enrollment officer): create your account, then generate your QR and accept appointments.</p>
    @endguest
</div>
@endsection
