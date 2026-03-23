@extends('layouts.public')

@section('title', ($tenant?->name ?? config('app.name')) . ' - Tenant App')

@section('content')
<div class="mt-6">
    <h1 class="text-2xl font-bold text-slate-800">
        {{ $tenant?->name ?? config('app.name') }}
    </h1>
    @if($tenant)
        <p class="mt-2 text-sm text-slate-600">
            This app is for approved members of {{ $tenant->name }}. Create an account or log in to access the workspace.
        </p>
    @else
        <p class="mt-2 text-sm text-slate-600">
            Open this page from your tenant workspace link or tenant subdomain to register and access the tenant app.
        </p>
    @endif
</div>

@if($tenant)
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Get started</h2>
                <p class="mt-1 text-sm text-slate-500">Register for an account, wait for admin approval, then log in to request queue numbers and appointments.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('tenant.register') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Create account
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Log in
                </a>
            </div>
        </div>
    </div>
@endif
@endsection
