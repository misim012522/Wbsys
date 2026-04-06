@extends('layouts.app')

@section('title', 'Access Control')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Access control',
    'description' => 'Simple role-based access for this workspace. Tenant admins always keep full admin access, while office staff access can be toggled here.',
])

@php
    $enabledCount = collect($officeStaffPermissions)->filter()->count();
    $workspaceHost = $tenant?->subdomain ? $tenant->subdomain.'.lvh.me' : ($tenant?->domain ?: 'N/A');
@endphp

<div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.10),_transparent_24%),linear-gradient(135deg,_#ffffff_0%,_#f8fbff_48%,_#f1fdf7_100%)] p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Simple RBAC</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Role access setup</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">This workspace only uses two roles. No custom roles, no complex permission matrix, just a clean admin-and-staff split.</p>
            </div>
        </div>

        <div class="space-y-5 p-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.5rem] border border-emerald-200 bg-[linear-gradient(135deg,_rgba(236,253,245,0.95)_0%,_rgba(255,255,255,0.98)_100%)] p-5 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Tenant admin</p>
                        <span class="rounded-full bg-emerald-600 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Fixed</span>
                    </div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">Full workspace control</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Admins can open the admin dashboard, approve office staff, adjust workspace settings, view reports, and manage the built-in office workflow.</p>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-[linear-gradient(135deg,_rgba(248,250,252,0.95)_0%,_rgba(255,255,255,1)_100%)] p-5 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Office staff</p>
                        <span class="rounded-full bg-slate-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white">{{ $enabledCount }} active</span>
                    </div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">Toggle-based access</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Turn the key office staff capabilities on or off below without creating extra role types or custom permission groups.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.rbac.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Office staff</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Allowed actions</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Choose what office staff can access in their workspace.</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <label class="flex items-start gap-4 rounded-[1.25rem] border border-slate-200 bg-[linear-gradient(135deg,_#ffffff_0%,_#f8fffc_100%)] p-4 transition hover:border-emerald-300 hover:shadow-sm">
                            <input type="checkbox" name="office_staff_office_serve" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ $officeStaffPermissions['office.serve'] ? 'checked' : '' }}>
                            <span class="flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="block text-sm font-semibold text-slate-900">Serve queues and appointments</span>
                                    <span class="rounded-full {{ $officeStaffPermissions['office.serve'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                        {{ $officeStaffPermissions['office.serve'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </span>
                                <span class="mt-2 block text-sm leading-6 text-slate-600">Lets office staff open their dashboard, use QR tools, handle queue calls, and process appointments.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-4 rounded-[1.25rem] border border-slate-200 bg-[linear-gradient(135deg,_#ffffff_0%,_#f8fbff_100%)] p-4 transition hover:border-sky-300 hover:shadow-sm">
                            <input type="checkbox" name="office_staff_reports_view" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ $officeStaffPermissions['reports.view'] ? 'checked' : '' }}>
                            <span class="flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="block text-sm font-semibold text-slate-900">View reports</span>
                                    <span class="rounded-full {{ $officeStaffPermissions['reports.view'] ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                        {{ $officeStaffPermissions['reports.view'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </span>
                                <span class="mt-2 block text-sm leading-6 text-slate-600">Lets office staff open and download office reports when your plan supports reports.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save access</button>
                </div>
            </form>
        </div>
    </section>

    <section class="space-y-5">
        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Role map</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Current structure</h2>
            </div>

            <div class="space-y-4 p-6">
                <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50/60 p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm font-semibold text-slate-900">`tenant_admin`</p>
                        <span class="rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Admin</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Dedicated for admin workspace pages under `/admin`.</p>
                </div>
                <div class="rounded-[1.25rem] border border-sky-200 bg-sky-50/60 p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm font-semibold text-slate-900">`office_staff`</p>
                        <span class="rounded-full bg-sky-600 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Staff</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Dedicated for office workspace pages under `/office`, with access based on the toggles on this page.</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Workspace</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $tenant?->name ?? 'Workspace' }}</h2>
                <p class="mt-2 text-sm text-slate-500">A simple RBAC page for this tenant only.</p>
            </div>

            <div class="space-y-4 p-6">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace host</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $workspaceHost }}</p>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Notes</p>
                    <div class="mt-2 space-y-2 text-sm leading-6 text-slate-600">
                        <p>Admins keep full access so the workspace never gets locked out by accident.</p>
                        <p>Office staff access stays simple and can be adjusted here anytime.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
