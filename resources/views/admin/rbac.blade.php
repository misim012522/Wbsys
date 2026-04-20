@extends('layouts.app')

@section('title', $pageTitle ?? 'Access Control')

@section('content')
@if(($pageMode ?? 'tenant') === 'tenant')
    @include('admin._workspace-nav', [
        'title' => 'Access control',
        'description' => 'Role-based access for this workspace. Permissions are saved separately for the current tenant.',
    ])
@else
    <div class="mb-6 rounded-[1.75rem] border border-slate-200 bg-white p-4 md:p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Central RBAC</p>
                <h1 class="mt-1 text-xl md:text-2xl font-extrabold text-slate-900">{{ $pageTitle ?? 'Access Control' }}</h1>
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $pageDescription ?? 'Configure RBAC for this specific registered tenant.' }}</p>
            </div>
            @if(!empty($backUrl))
                <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ $backLabel ?? 'Back' }}
                </a>
            @endif
        </div>
    </div>
@endif

@php
    $tenantAdminEnabledCount = collect($tenantAdminPermissions)->filter()->count();
    $enabledCount = collect($officeStaffPermissions)->filter()->count();
    $workspaceHost = $tenant?->subdomain ? $tenant->subdomain.'.lvh.me' : ($tenant?->domain ?: 'N/A');
    $tenantAdminTotal = count($tenantAdminPermissionDefinitions);
    $officeStaffTotal = count($permissionDefinitions);
    $lockedTenantAdminCount = collect($tenantAdminPermissionDefinitions)->filter(fn ($definition) => $definition['locked'] ?? false)->count();
    $badgeClasses = [
        'emerald' => ['enabled' => 'bg-emerald-100 text-emerald-700', 'accent' => 'hover:border-emerald-300'],
        'teal' => ['enabled' => 'bg-teal-100 text-teal-700', 'accent' => 'hover:border-teal-300'],
        'amber' => ['enabled' => 'bg-amber-100 text-amber-700', 'accent' => 'hover:border-amber-300'],
        'rose' => ['enabled' => 'bg-rose-100 text-rose-700', 'accent' => 'hover:border-rose-300'],
        'slate' => ['enabled' => 'bg-slate-200 text-slate-700', 'accent' => 'hover:border-slate-300'],
        'sky' => ['enabled' => 'bg-sky-100 text-sky-700', 'accent' => 'hover:border-sky-300'],
    ];
@endphp

<div class="space-y-6">
    <div class="grid gap-6">
    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.10),_transparent_24%),linear-gradient(135deg,_#ffffff_0%,_#f8fbff_48%,_#f1fdf7_100%)] p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Simple RBAC</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Role access setup</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Permissions on this screen are stored on the selected tenant, so every registered tenant can keep its own admin-and-staff access rules.</p>
            </div>
        </div>

        <div class="space-y-5 p-6">
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">How to use this page</p>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">1. Review the role</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Each section controls one built-in role for this tenant.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">2. Toggle permissions</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Enable or disable actions depending on what this tenant should access.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">3. Save per tenant</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Any changes saved here only affect this tenant.</p>
                    </div>
                </div>
            </div>

                <form id="tenant-rbac-form" method="POST" action="{{ $saveAction ?? route('admin.rbac.update') }}" class="space-y-5">
                @csrf
                @method($saveMethod ?? 'PUT')

                <div class="grid gap-5 xl:grid-cols-2">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant admin</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-900">Allowed admin actions</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Choose which admin workspace features remain available for tenant administrators.</p>
                        </div>

                        <div class="scroll-region max-h-[32rem] space-y-4 overflow-y-auto p-5 pr-3">
                            @foreach($tenantAdminPermissionDefinitions as $slug => $definition)
                                @php
                                    $enabled = $tenantAdminPermissions[$slug] ?? false;
                                    $styles = $badgeClasses[$definition['badge']] ?? $badgeClasses['slate'];
                                    $isLocked = $definition['locked'] ?? false;
                                @endphp
                                <label class="flex items-start gap-4 rounded-[1.25rem] border border-slate-200 bg-[linear-gradient(135deg,_#ffffff_0%,_#f8fbff_100%)] p-4 transition {{ $styles['accent'] }} hover:shadow-sm">
                                    <input type="checkbox" name="{{ $definition['input'] }}" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ $enabled ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                    <span class="flex min-w-0 flex-1 flex-col gap-2">
                                        <span class="block text-sm font-semibold leading-6 text-slate-900">{{ $definition['label'] }}</span>
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full {{ $enabled ? $styles['enabled'] : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                                {{ $enabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                            @if($isLocked)
                                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Always on</span>
                                            @endif
                                        </span>
                                        <span class="block text-sm leading-6 text-slate-600">{{ $definition['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Office staff</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-900">Allowed actions</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Choose what office staff can access in their workspace.</p>
                        </div>

                        <div class="scroll-region max-h-[32rem] space-y-4 overflow-y-auto p-5 pr-3">
                            @foreach($permissionDefinitions as $slug => $definition)
                                @php
                                    $enabled = $officeStaffPermissions[$slug] ?? false;
                                    $styles = $badgeClasses[$definition['badge']] ?? $badgeClasses['slate'];
                                @endphp
                                <label class="flex items-start gap-4 rounded-[1.25rem] border border-slate-200 bg-[linear-gradient(135deg,_#ffffff_0%,_#f8fbff_100%)] p-4 transition {{ $styles['accent'] }} hover:shadow-sm">
                                    <input type="checkbox" name="{{ $definition['input'] }}" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ $enabled ? 'checked' : '' }}>
                                    <span class="flex min-w-0 flex-1 flex-col gap-2">
                                        <span class="block text-sm font-semibold leading-6 text-slate-900">{{ $definition['label'] }}</span>
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full {{ $enabled ? $styles['enabled'] : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                                {{ $enabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </span>
                                        <span class="block text-sm leading-6 text-slate-600">{{ $definition['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </section>

    @if(($pageMode ?? 'tenant') !== 'central')
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
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Implementation notes</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Safety and scope</h2>
            </div>

            <div class="space-y-4 p-6">
                <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50/60 p-4">
                    <p class="text-sm font-semibold text-slate-900">Recovery-safe admin access</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Locked tenant admin permissions stay enabled so a workspace cannot accidentally remove its own recovery path.</p>
                </div>
                <div class="rounded-[1.25rem] border border-sky-200 bg-sky-50/60 p-4">
                    <p class="text-sm font-semibold text-slate-900">Separate tenant storage</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">This configuration is saved on the current tenant record and does not modify the RBAC settings of other registered tenants.</p>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Clean role model</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">The page keeps the model intentionally simple: one admin role, one office staff role, and permission toggles arranged around real workspace tasks.</p>
                </div>
                @if(($pageMode ?? 'tenant') === 'central')
                    <div class="rounded-[1.25rem] border border-amber-200 bg-amber-50/70 p-4">
                        <p class="text-sm font-semibold text-slate-900">Central admin reminder</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">You are editing RBAC for a specific tenant from the central workspace. Review the tenant name and host above before saving.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
    @endif
    </div>
    
    <section class="rounded-[1.75rem] border border-slate-200 bg-white p-4 md:p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Save changes</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">Apply this tenant's access configuration</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">After saving, the selected tenant will use these updated permissions immediately on its workspace routes.</p>
            </div>
            <button type="submit" form="tenant-rbac-form" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">{{ $saveButtonLabel ?? 'Save access' }}</button>
        </div>
    </section>
</div>
@include('admin._workspace-nav-footer')
@endsection
