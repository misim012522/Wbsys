@extends('layouts.public')

@section('title', ($tenant?->name ?? config('app.name')) . ' - Tenant Workspace')

@section('content')
<section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
    <div class="grid gap-0 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 px-8 py-10 text-white sm:px-10 sm:py-12">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-200">Tenant Workspace</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">
                {{ $tenant?->name ?? config('app.name') }}
            </h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-200 sm:text-base">
                @if($tenant)
                    This tenant domain is the dedicated workspace for {{ $tenant->name }}. Tenant admins handle oversight and settings here, while office staff handle live queue and appointment operations from their own workspace pages.
                @else
                    Open this page from a tenant domain to access that tenant's admin oversight pages, office staff workspace, and public service links.
                @endif
            </p>

            @auth
                @if(auth()->user()->isCentralUser())
                    <div class="mt-6 rounded-2xl border border-white/15 bg-white/10 p-4 text-sm leading-6 text-slate-100">
                        <p class="font-semibold text-white">Central account detected</p>
                        <p class="mt-1">You are currently signed in as a central user. To open the tenant admin dashboard, log out first and then sign in with this tenant's admin or staff account.</p>
                        <form method="POST" action="{{ route('logout') }}" class="mt-4 inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                Log out and switch account
                            </button>
                        </form>
                    </div>
                @endif
            @endauth

            <div class="mt-8 flex flex-wrap gap-3">
                @guest
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                        Open workspace login
                    </a>
                @endguest
                @auth
                    @if(! auth()->user()->isCentralUser())
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                Open tenant admin workspace
                            </a>
                            <a href="{{ route('admin.settings.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-5 py-3 text-sm font-semibold text-white/90">
                                Open admin settings
                            </a>
                        @elseif(auth()->user()->isOfficeStaff())
                            <a href="{{ route('office.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                Open office staff workspace
                            </a>
                            <a href="{{ route('tenant.settings.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-5 py-3 text-sm font-semibold text-white/90">
                                Open workspace settings
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                Open tenant dashboard
                            </a>
                            <a href="{{ route('tenant.settings.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-5 py-3 text-sm font-semibold text-white/90">
                                Open workspace settings
                            </a>
                        @endif
                    @endif
                @endauth
                <span class="inline-flex items-center justify-center rounded-xl border border-white/20 px-5 py-3 text-sm font-semibold text-white/90">
                    End users continue through QR/public links
                </span>
            </div>
        </div>

        <div class="px-8 py-10 sm:px-10 sm:py-12">
            <h2 class="text-lg font-semibold text-slate-900">How this tenant is used</h2>
            <div class="mt-6 space-y-4 text-sm leading-7 text-slate-600">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">Tenant admin pages</p>
                    <p class="mt-1">Admin login, dashboard, reports, user management, public access setup, and settings all live inside the tenant workspace domain.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">Office staff dashboard</p>
                    <p class="mt-1">Logged-in office staff use the office dashboard inside the same tenant workspace to manage queue calls, appointments, office QR access, reports, and activity.</p>
                </div>
                @if($tenant)
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="font-semibold text-emerald-900">Dedicated tenant URLs</p>
                        <p class="mt-1 text-emerald-800">
                            Dashboard: {{ \App\Support\TenantUrl::dashboard($tenant) }}<br>
                            Settings: {{ \App\Support\TenantUrl::forPath($tenant, '/settings') }}
                        </p>
                    </div>
                @endif
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">Public external users</p>
                    <p class="mt-1">Visitors continue using the public QR, queue, and appointment pages without creating or signing in to a tenant workspace account.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
