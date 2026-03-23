@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Admin Dashboard</h1>
        <p class="mt-2 text-sm text-slate-500">Manage approvals, workspace activity, and account controls from one place.</p>
    </div>

    <div class="w-full max-w-3xl space-y-3 lg:w-auto">
        <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-emerald-50/40 p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3 px-1 pb-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Quick actions</p>
                    <p class="mt-1 text-sm text-slate-500">Jump straight into the main workspace tasks.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @if($office)
                    <a href="{{ route('admin.serve', $office) }}" class="group rounded-2xl bg-emerald-600 p-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m4-2a8 8 0 11-16 0 8 8 0 0116 0z" /></svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100">Live</span>
                        </div>
                        <div class="mt-5">
                            <p class="text-base font-semibold">Serve queue</p>
                            <p class="mt-1 text-sm text-emerald-100">Call the next person and update queue progress.</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.qr') }}" class="group rounded-2xl border border-emerald-200 bg-white p-4 text-emerald-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50/70">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zm0 10h6v6H4zm10-10h6v6h-6zm3 10h3v3h-3zm-3 0h3m-3 3h3m3-3v6" /></svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">Access</span>
                        </div>
                        <div class="mt-5">
                            <p class="text-base font-semibold text-slate-800">QR codes</p>
                            <p class="mt-1 text-sm text-slate-500">Open and print the scan link for your workspace.</p>
                        </div>
                    </a>
                @endif
                <a href="{{ route('admin.users.pending') }}" class="group rounded-2xl border border-slate-200 bg-slate-900 p-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2m8-10a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Review</span>
                    </div>
                    <div class="mt-5">
                        <p class="text-base font-semibold">Pending accounts</p>
                        <p class="mt-1 text-sm text-slate-300">Approve newly registered users and unlock access.</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.users.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Staff accounts</a>
            <a href="{{ route('admin.users.archived') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Archived</a>
            <a href="{{ route('admin.reports') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Reports</a>
            <a href="{{ route('admin.customization.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Customization</a>
            <a href="{{ route('admin.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Account settings</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Today's active queue</p>
        <p class="text-3xl font-bold text-emerald-600">{{ $todayQueues }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Today's appointments</p>
        <p class="text-3xl font-bold text-blue-600">{{ $todayAppointments }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Completed today</p>
        <p class="text-3xl font-bold text-slate-700">{{ $completedToday }}</p>
    </div>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Workspace setup</h2>
    <p class="mt-2 text-sm text-slate-600">
        Office management has been removed. This tenant now uses one built-in workspace for queueing, appointments, QR access, and reports.
    </p>
    @if($office)
        <p class="mt-3 text-sm text-slate-500">
            Active workspace: <span class="font-medium text-slate-700">{{ $office->name }}</span>
        </p>
    @endif
</div>
@endsection
