@extends('layouts.app')

@section('title', 'Central Dashboard')

@section('content')
<div class="space-y-4" data-central-dashboard-root data-open-modal="{{ session('open_modal', '') }}">
    <div class="rounded-2xl border border-slate-200 bg-white p-3 md:p-4 shadow-sm">
        @php
            $centralSupportUnreadCount = \App\Models\SupportThread::unreadCountForCentral();
        @endphp
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1">
                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                    Queueing system
                </span>
                <h1 class="mt-2 text-lg md:text-xl font-extrabold text-slate-900">Tenant Queue Workspaces</h1>
                <p class="mt-1 max-w-3xl text-xs text-slate-600">Approve tenants, check workspace status, and manage access.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('central.support.index') }}" class="inline-flex items-center gap-1 rounded-md bg-sky-600 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-sky-700 transition">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6"/></svg>
                    Support{{ $centralSupportUnreadCount ? ' ('.$centralSupportUnreadCount.')' : '' }}
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-2 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-2 md:p-3 shadow-sm">
            <p class="text-xs text-slate-500">Workspaces</p>
            <p class="mt-1 text-xl md:text-2xl font-extrabold text-slate-900">{{ $tenantCount }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ $activeTenantCount }} active</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-2 md:p-3 shadow-sm">
            <p class="text-xs text-slate-500">Plans</p>
            <p class="mt-1 text-xl md:text-2xl font-extrabold text-slate-900">{{ $planCount }}</p>
            <p class="mt-0.5 text-xs text-slate-500">Subscription plans</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-2 md:p-3 shadow-sm">
            <p class="text-xs text-slate-500">Subscriptions</p>
            <p class="mt-1 text-xl md:text-2xl font-extrabold text-slate-900">{{ $subscriptionCount }}</p>
            <p class="mt-0.5 text-xs text-slate-500">Active records</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-2 text-xs text-amber-900 max-w-lg">
                Approve new workspaces before they can run their queue.
            </div>
            <p class="text-xs text-slate-500">{{ $tenants->count() }} workspaces listed</p>
        </div>
        <div class="scroll-region-x overflow-x-auto rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,_#f8fbff_0%,_#ffffff_12%)] p-3">
            <table class="min-w-full md:min-w-[112rem] border-separate border-spacing-y-2 text-xs">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Tenant Name</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Tenant Domain</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Address</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Contact Number</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Email</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Created At</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Subscription Plan</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Usage Summary</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Last Activity</th>
                        <th class="px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Status</th>
                        <th class="w-[14rem] px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.15em]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        @php
                            $workspaceUrl = \App\Support\TenantUrl::workspace($tenant);
                            $loginUrl = \App\Support\TenantUrl::login($tenant);
                            $workspaceHost = parse_url($workspaceUrl, PHP_URL_HOST) ?: 'N/A';
                            $latestSubscription = $tenant->subscriptions->sortByDesc('id')->first();
                            $tenantAdmin = $tenantAdmins[$tenant->id] ?? null;
                            $tenantInsight = $tenantInsights[$tenant->id] ?? [
                                'office_count' => 0,
                                'office_staff_count' => 0,
                                'today_queue_count' => 0,
                                'today_appointment_count' => 0,
                                'last_activity_label' => 'Unavailable',
                            ];
                        @endphp
                        <tr class="align-top">
                            <td class="rounded-l-2xl border-y border-l border-slate-200 bg-white px-2 py-2 shadow-sm">
                                <div class="text-xs font-semibold text-slate-900">{{ $tenant->name }}</div>
                                <div class="text-[11px] text-slate-500">Slug: {{ $tenant->slug }}</div>
                                <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-2 space-y-0.5">
                                    <p class="text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400">Main tenant account</p>
                                    @if($tenantAdmin)
                                        <div class="text-[11px] font-medium text-slate-800">{{ $tenantAdmin->name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $tenantAdmin->username }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $tenantAdmin->email }}</div>
                                    @else
                                        <div class="text-[10px] text-slate-500">No tenant admin account found.</div>
                                    @endif
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 shadow-sm">
                                <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-2">
                                    <p class="text-[9px] font-semibold uppercase tracking-[0.15em] text-sky-700">Tenant domain</p>
                                    <div class="mt-1 break-all text-xs font-semibold text-slate-900">
                                        {{ $workspaceHost }}
                                    </div>
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2 min-w-[8rem]">
                                    <div class="text-xs font-medium text-slate-900">{{ $tenant->address ?: 'N/A' }}</div>
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2 min-w-[6rem]">
                                    <div class="text-xs font-medium text-slate-900">{{ $tenant->contact_number ?: 'N/A' }}</div>
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2 min-w-[10rem]">
                                    <div class="break-all text-xs font-medium text-slate-900">{{ $tenant->email ?: 'N/A' }}</div>
                                    <div class="mt-0.5 text-[10px] text-slate-400">Registration contact</div>
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2 min-w-[7rem]">
                                    <div class="text-xs font-medium text-slate-900">{{ optional($tenant->created_at)->format('M d, Y') ?: 'N/A' }}</div>
                                    <div class="mt-0.5 text-[10px] text-slate-500">{{ optional($tenant->created_at)->format('h:i A') ?: '' }}</div>
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-2 min-w-[7rem]">
                                    <div class="text-xs font-medium text-slate-900">{{ $tenant->plan?->name ?? 'N/A' }}</div>
                                    @if($latestSubscription)
                                        <div class="mt-0.5 text-[10px] text-slate-500">
                                            {{ str($latestSubscription->status)->replace('_', ' ')->title() }}
                                            @if($latestSubscription->ends_at)
                                                until {{ $latestSubscription->ends_at->format('M d, Y') }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="grid min-w-[8rem] grid-cols-2 gap-1 text-[10px]">
                                    <div class="rounded-lg bg-slate-50 px-2 py-1"><span class="font-semibold text-slate-800">{{ $tenantInsight['office_staff_count'] }}</span> staff</div>
                                    <div class="rounded-lg bg-slate-50 px-2 py-1"><span class="font-semibold text-slate-800">{{ $tenantInsight['office_count'] }}</span> offices</div>
                                    <div class="rounded-lg bg-slate-50 px-2 py-1"><span class="font-semibold text-slate-800">{{ $tenantInsight['today_queue_count'] }}</span> queues</div>
                                    <div class="rounded-lg bg-slate-50 px-2 py-1"><span class="font-semibold text-slate-800">{{ $tenantInsight['today_appointment_count'] }}</span> appt</div>
                                </div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 text-slate-600 shadow-sm">
                                <div class="max-w-[10rem] rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs text-slate-700">{{ $tenantInsight['last_activity_label'] }}</div>
                            </td>
                            <td class="border-y border-slate-200 bg-white px-2 py-2 shadow-sm">
                                <div class="min-w-[7rem]">
                                    @if(! $tenant->approved_at)
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700">
                                            Pending approval
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium {{ $tenant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="rounded-r-2xl border-y border-r border-slate-200 bg-white px-2 py-2 shadow-sm">
                                <div class="w-full min-w-[12rem] space-y-2">
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400">Manage</p>
                                        <div class="grid grid-cols-1 gap-1">
                                            <button
                                                type="button"
                                                data-modal-target="tenant-edit-modal-{{ $tenant->id }}"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-center text-[11px] font-semibold text-slate-700 hover:shadow-sm transition"
                                            >
                                                Edit tenant
                                            </button>

                                            <button
                                                type="button"
                                                data-modal-target="tenant-subscription-modal-{{ $tenant->id }}"
                                                class="rounded-md border border-emerald-200 bg-white px-2 py-1 text-center text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 shadow-sm transition"
                                            >
                                                Edit subscription
                                            </button>

                                            @if(Route::has('central.tenants.rbac.edit'))
                                                <a
                                                    href="{{ route('central.tenants.rbac.edit', $tenant) }}"
                                                    style="position:relative;z-index:20001;pointer-events:auto;"
                                                    aria-label="Open access control for tenant"
                                                    class="central-access-control-button rounded-md inline-flex items-center justify-center gap-1 border border-sky-200 bg-sky-600 px-2 py-1 text-[11px] font-semibold text-white shadow-sm hover:bg-sky-700 transition"
                                                    onclick="event.stopPropagation();window.location.href=this.href;return false;"
                                                >
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Access control
                                                </a>
                                            @else
                                                <button type="button" disabled class="rounded-md border border-slate-200 bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500" title="Access control is managed in-tenant">
                                                    Access control
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400">Access</p>
                                        @if(! $tenant->approved_at)
                                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-[10px] text-amber-800">
                                                Approve this tenant first.
                                            </div>
                                        @else
                                            <div class="grid grid-cols-1 gap-1">
                                                <form method="POST" action="{{ route('central.tenants.workspace-access', $tenant) }}" data-row-action-form>
                                                    @csrf
                                                    <button type="submit" data-row-action-button data-default-label="Send access email" data-loading-label="Sending email..." class="w-full rounded-lg border border-emerald-200 px-2 py-1 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                                        Send access email
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('central.tenants.reset-password', $tenant) }}" data-row-action-form>
                                                    @csrf
                                                    <button type="submit" data-row-action-button data-default-label="Reset temp password" data-loading-label="Resetting..." class="w-full rounded-lg border border-amber-200 px-2 py-1 text-[10px] font-semibold text-amber-700 transition hover:bg-amber-50">
                                                        Reset temp password
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="space-y-1 border-t border-slate-200 pt-1">
                                        <p class="text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400">Status & danger</p>
                                        @if(! $tenant->approved_at)
                                            <form method="POST" action="{{ route('central.tenants.approve', $tenant) }}" data-row-action-form>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" data-row-action-button data-default-label="Approve tenant" data-loading-label="Approving..." class="w-full rounded-lg border border-emerald-200 px-2 py-1 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                                    Approve tenant
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('central.tenants.activation', $tenant) }}" data-row-action-form>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" data-row-action-button data-default-label="{{ $tenant->is_active ? 'Deactivate' : 'Activate' }}" data-loading-label="{{ $tenant->is_active ? 'Deactivating...' : 'Activating...' }}" class="w-full rounded-lg border border-slate-300 px-2 py-1 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    {{ $tenant->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endif

                                        <button
                                            type="button"
                                            data-delete-tenant-trigger
                                            data-tenant-name="{{ $tenant->name }}"
                                            data-tenant-action="{{ route('central.tenants.destroy', $tenant) }}"
                                            class="w-full rounded-lg border border-red-200 px-2 py-1 text-[10px] font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-50"
                                        >
                                            Delete tenant
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-2 py-6">
                                <div class="mx-auto max-w-2xl rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9ZM8 9h8M8 12h8M8 15h5" />
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-lg font-bold text-slate-900">No tenants registered yet</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">
                                        Once a tenant completes registration, their workspace domain, subscription plan, and activity summary will appear here.
                                    </p>
                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                        <a href="{{ route('central.register') }}" class="rounded-lg bg-emerald-600 px-2 py-1 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                            Register first tenant
                                        </a>
                                        <span class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-500">
                                            Activity will appear after onboarding
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($tenants as $tenant)
    @php
        $latestSubscription = $tenant->subscriptions->sortByDesc('id')->first();
        $tenantUpdateErrorBag = 'tenantUpdate_'.$tenant->id;
        $tenantRbacErrorBag = 'tenantRbac_'.$tenant->id;
        $tenantSubscriptionErrorBag = 'tenantSubscription_'.$tenant->id;
        $isTenantUpdateModalOpen = session('open_modal') === 'tenant-edit-modal-'.$tenant->id;
        $isTenantRbacModalOpen = session('open_modal') === 'tenant-rbac-modal-'.$tenant->id;
        $isTenantSubscriptionModalOpen = session('open_modal') === 'tenant-subscription-modal-'.$tenant->id;
        $tenantAdminPermissionDefinitions = \App\Models\User::tenantAdminPermissionDefinitions();
        $tenantAdminPermissionStates = \App\Models\User::tenantAdminPermissionStates($tenant);
        $tenantPermissionDefinitions = \App\Models\User::officeStaffPermissionDefinitions();
        $tenantPermissionStates = \App\Models\User::officeStaffPermissionStates($tenant);
        $badgeClasses = [
            'emerald' => ['enabled' => 'bg-emerald-100 text-emerald-700', 'accent' => 'border-emerald-200 bg-emerald-50/60'],
            'teal' => ['enabled' => 'bg-teal-100 text-teal-700', 'accent' => 'border-teal-200 bg-teal-50/60'],
            'amber' => ['enabled' => 'bg-amber-100 text-amber-700', 'accent' => 'border-amber-200 bg-amber-50/60'],
            'rose' => ['enabled' => 'bg-rose-100 text-rose-700', 'accent' => 'border-rose-200 bg-rose-50/60'],
            'slate' => ['enabled' => 'bg-slate-200 text-slate-700', 'accent' => 'border-slate-200 bg-slate-50/80'],
            'sky' => ['enabled' => 'bg-sky-100 text-sky-700', 'accent' => 'border-sky-200 bg-sky-50/60'],
        ];
    @endphp
    <div id="tenant-edit-modal-{{ $tenant->id }}" data-dashboard-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Step 2</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Update tenant details</h2>
                    <p class="mt-1 text-xs text-slate-500">Edit the tenant profile from the central app.</p>
                </div>
                <button type="button" data-modal-close class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close dialog">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('central.tenants.update', $tenant) }}" class="mt-3" data-modal-submit-form>
                @csrf
                @method('PATCH')
                @if($errors->{$tenantUpdateErrorBag}->any())
                    <div class="mb-2 rounded-lg border border-red-200 bg-red-50 px-2 py-2 text-xs text-red-700">
                        Please review the tenant details below and fix the highlighted fields.
                    </div>
                @endif
                <div class="grid gap-2 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-0.5 block text-[10px] font-medium uppercase tracking-wide text-slate-500">Tenant name</label>
                        <input type="text" name="name" value="{{ $isTenantUpdateModalOpen ? old('name', $tenant->name) : $tenant->name }}" class="w-full rounded-lg border px-2 py-1 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('name') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('name'))
                            <p class="mt-0.5 text-[10px] text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('name') }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-0.5 block text-[10px] font-medium uppercase tracking-wide text-slate-500">Address</label>
                        <textarea name="address" rows="2" class="w-full rounded-lg border px-2 py-1 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('address') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">{{ $isTenantUpdateModalOpen ? old('address', $tenant->address) : $tenant->address }}</textarea>
                        @if($errors->{$tenantUpdateErrorBag}->has('address'))
                            <p class="mt-0.5 text-[10px] text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('address') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-0.5 block text-[10px] font-medium uppercase tracking-wide text-slate-500">Contact number</label>
                        <input type="text" name="contact_number" value="{{ $isTenantUpdateModalOpen ? old('contact_number', $tenant->contact_number) : $tenant->contact_number }}" class="w-full rounded-lg border px-2 py-1 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('contact_number') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('contact_number'))
                            <p class="mt-0.5 text-[10px] text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('contact_number') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Email</label>
                        <input type="email" name="email" value="{{ $isTenantUpdateModalOpen ? old('email', $tenant->email) : $tenant->email }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('email') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('email'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('email') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Subdomain</label>
                        <input type="text" name="subdomain" value="{{ $isTenantUpdateModalOpen ? old('subdomain', $tenant->subdomain) : $tenant->subdomain }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('subdomain') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('subdomain'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('subdomain') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Custom domain</label>
                        <input type="text" name="domain" value="{{ $isTenantUpdateModalOpen ? old('domain', $tenant->domain) : $tenant->domain }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('domain') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('domain'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('domain') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                    Use either a subdomain or a custom domain. If a custom domain is set, it overrides the generated subdomain host.
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <p data-submit-status class="hidden text-sm font-medium text-slate-500">Saving tenant details...</p>
                    <div class="flex justify-end gap-3">
                    <button type="button" data-modal-close class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" data-submit-button data-default-label="Save tenant" data-loading-label="Saving tenant..." class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Save tenant
                    </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="tenant-rbac-modal-{{ $tenant->id }}" data-dashboard-modal class="scroll-region fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 sm:px-4 sm:py-6">
        <div class="w-full max-w-[56rem] rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-2xl sm:p-5 lg:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">Access Control</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-900 sm:text-2xl">Manage {{ $tenant->name }} RBAC</h2>
                    <p class="mt-2 text-sm text-slate-500">Control which tenant admin and office staff features are enabled for this tenant without signing in to the tenant workspace.</p>
                </div>
                <button type="button" data-modal-close class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close dialog">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            @if(Route::has('central.tenants.rbac'))
                <form method="POST" action="{{ route('central.tenants.rbac', $tenant) }}" class="mt-6" data-modal-submit-form>
                    @csrf
                    @method('PATCH')
            @else
                <div class="mt-6 p-4 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-600">
                    Access control is managed within each tenant workspace; central editing is unavailable.
                </div>
            @endif
                @if($errors->{$tenantRbacErrorBag}->any())
                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Please review the access control settings below.
                    </div>
                @endif

                <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Core recovery pages stay enabled for tenant admins. Other admin tools and office staff features can be controlled below.
                </div>

                <div class="mb-6">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant admin</p>
                    <div class="grid gap-3 xl:grid-cols-2">
                        @foreach($tenantAdminPermissionDefinitions as $slug => $definition)
                            @php
                                $oldValue = $isTenantRbacModalOpen ? old($definition['input']) : null;
                                $enabled = ($definition['setting'] ?? null) === null
                                    ? ($tenantAdminPermissionStates[$slug] ?? false)
                                    : ($isTenantRbacModalOpen ? filled($oldValue) : ($tenantAdminPermissionStates[$slug] ?? false));
                                $styles = $badgeClasses[$definition['badge']] ?? $badgeClasses['slate'];
                                $isLocked = $definition['locked'] ?? false;
                            @endphp
                            <label class="flex items-start gap-3 rounded-[1.25rem] border p-3.5 sm:p-4 {{ $styles['accent'] }}">
                                <input type="checkbox" name="{{ $definition['input'] }}" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" {{ $enabled ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
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

                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Office staff</p>
                <div class="grid gap-3 xl:grid-cols-2">
                    @foreach($tenantPermissionDefinitions as $slug => $definition)
                        @php
                            $oldValue = $isTenantRbacModalOpen ? old($definition['input']) : null;
                            $enabled = $isTenantRbacModalOpen
                                ? filled($oldValue)
                                : ($tenantPermissionStates[$slug] ?? false);
                            $styles = $badgeClasses[$definition['badge']] ?? $badgeClasses['slate'];
                        @endphp
                        <label class="flex items-start gap-3 rounded-[1.25rem] border p-3.5 sm:p-4 {{ $styles['accent'] }}">
                            <input type="checkbox" name="{{ $definition['input'] }}" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" {{ $enabled ? 'checked' : '' }}>
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

                <div class="mt-6 flex items-center justify-between gap-3">
                    <p data-submit-status class="hidden text-sm font-medium text-sky-700">Saving access control...</p>
                    <div class="flex justify-end gap-3">
                        <button type="button" data-modal-close class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" data-submit-button data-default-label="Save access control" data-loading-label="Saving access control..." class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Save access control
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="tenant-subscription-modal-{{ $tenant->id }}" data-dashboard-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
        <div class="w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Step 3</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Update subscription</h2>
                    <p class="mt-2 text-sm text-slate-500">Change the tenant plan, lifecycle status, and monthly billing start date from the central dashboard.</p>
                </div>
                <button type="button" data-modal-close class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close dialog">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('central.tenants.subscription', $tenant) }}" class="mt-6" data-modal-submit-form>
                @csrf
                @method('PATCH')
                @if($errors->{$tenantSubscriptionErrorBag}->any())
                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Please review the subscription details below and fix the highlighted fields.
                    </div>
                @endif
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Plan</label>
                        <select name="plan_id" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantSubscriptionErrorBag}->has('plan_id') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected((string) ($isTenantSubscriptionModalOpen ? old('plan_id', $tenant->plan_id) : $tenant->plan_id) === (string) $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                        @if($errors->{$tenantSubscriptionErrorBag}->has('plan_id'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantSubscriptionErrorBag}->first('plan_id') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                        <select name="status" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantSubscriptionErrorBag}->has('status') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                            @foreach([\App\Models\TenantSubscription::STATUS_ACTIVE, \App\Models\TenantSubscription::STATUS_TRIALING, \App\Models\TenantSubscription::STATUS_CANCELLED, \App\Models\TenantSubscription::STATUS_EXPIRED] as $status)
                                <option value="{{ $status }}" @selected(($isTenantSubscriptionModalOpen ? old('status', $latestSubscription?->status ?? \App\Models\TenantSubscription::STATUS_ACTIVE) : ($latestSubscription?->status ?? \App\Models\TenantSubscription::STATUS_ACTIVE)) === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        @if($errors->{$tenantSubscriptionErrorBag}->has('status'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantSubscriptionErrorBag}->first('status') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Starts at</label>
                        <input type="datetime-local" name="starts_at" value="{{ $isTenantSubscriptionModalOpen ? old('starts_at', optional($latestSubscription?->starts_at)->format('Y-m-d\\TH:i') ?? optional($tenant->created_at)->format('Y-m-d\\TH:i')) : (optional($latestSubscription?->starts_at)->format('Y-m-d\\TH:i') ?? optional($tenant->created_at)->format('Y-m-d\\TH:i')) }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantSubscriptionErrorBag}->has('starts_at') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantSubscriptionErrorBag}->has('starts_at'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantSubscriptionErrorBag}->first('starts_at') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
                    End date is automatic for monthly plans and will be set to one month after the selected start date. Saving this also syncs the tenant's current assigned plan and sends a tenant admin notification when email delivery is available.
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <p data-submit-status class="hidden text-sm font-medium text-emerald-700">Updating subscription...</p>
                    <div class="flex justify-end gap-3">
                    <button type="button" data-modal-close class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" data-submit-button data-default-label="Update subscription" data-loading-label="Updating subscription..." class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Update subscription
                    </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endforeach

<div id="delete-tenant-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-red-600">Delete Tenant</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Remove this tenant?</h2>
            </div>
            <button type="button" data-delete-tenant-close class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close dialog">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <p class="mt-4 text-sm leading-6 text-slate-600">
            You are about to permanently delete <span class="font-semibold text-slate-900" data-delete-tenant-name></span> from the central system.
            This also removes the tenant database and related tenant records.
        </p>

        <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs leading-5 text-red-700">
            This action is permanent. The tenant workspace, tenant database, and related records cannot be recovered after deletion.
        </div>

        <form method="POST" action="" data-delete-tenant-modal-form class="mt-6 flex justify-end gap-3">
            @csrf
            @method('DELETE')
            <button type="button" data-delete-tenant-cancel class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" data-delete-tenant-confirm class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                Confirm delete
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-central-dashboard-root]');
        const deleteModal = document.getElementById('delete-tenant-modal');

        if (!root || !deleteModal) {
            return;
        }

        const managedModals = Array.from(document.querySelectorAll('[data-dashboard-modal]'));
        const initialModalId = root.dataset.openModal || '';
        const tenantNameTarget = deleteModal.querySelector('[data-delete-tenant-name]');
        const modalForm = deleteModal.querySelector('[data-delete-tenant-modal-form]');
        const confirmButton = deleteModal.querySelector('[data-delete-tenant-confirm]');
        const closeButtons = deleteModal.querySelectorAll('[data-delete-tenant-close], [data-delete-tenant-cancel]');
        const confirmButtonLabel = confirmButton ? confirmButton.textContent : 'Confirm delete';
        let activeAction = null;

        const showModal = (modalElement) => {
            modalElement.classList.remove('hidden');
            modalElement.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            // Hide access control buttons when modal opens
            document.querySelectorAll('.central-access-control-button').forEach(btn => {
                btn.style.display = 'none';
            });
        };

        const hideModal = (modalElement) => {
            modalElement.classList.add('hidden');
            modalElement.classList.remove('flex');

            if (!document.querySelector('[data-dashboard-modal].flex, #delete-tenant-modal.flex')) {
                document.body.classList.remove('overflow-hidden');
                // Show access control buttons when all modals are closed
                document.querySelectorAll('.central-access-control-button').forEach(btn => {
                    btn.style.display = '';
                });
            }
        };

        const openDeleteModal = (action, tenantName) => {
            activeAction = action;
            tenantNameTarget.textContent = tenantName;

            if (modalForm) {
                modalForm.setAttribute('action', action || '');
                // Ensure the form has a fresh CSRF token value in case the page token rotated
                const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (metaToken) {
                    let tokenInput = modalForm.querySelector('input[name="_token"]');
                    if (!tokenInput) {
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        modalForm.prepend(tokenInput);
                    }
                    tokenInput.value = metaToken;
                }
            }

            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.textContent = confirmButtonLabel;
                confirmButton.classList.remove('cursor-not-allowed', 'opacity-70');
            }

            showModal(deleteModal);

            if (confirmButton) {
                confirmButton.focus();
            }
        };

        const closeDeleteModal = () => {
            activeAction = null;

            if (modalForm) {
                modalForm.setAttribute('action', '');
            }

            hideModal(deleteModal);
        };

        root.querySelectorAll('[data-modal-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetModal = document.getElementById(button.dataset.modalTarget || '');

                if (targetModal) {
                    showModal(targetModal);
                }
            });
        });

        root.querySelectorAll('[data-delete-tenant-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                openDeleteModal(button.dataset.tenantAction || '', button.dataset.tenantName || 'this tenant');
            });
        });

        managedModals.forEach((modalElement) => {
            modalElement.querySelectorAll('[data-modal-close]').forEach((button) => {
                button.addEventListener('click', () => hideModal(modalElement));
            });

            modalElement.addEventListener('click', (event) => {
                if (event.target === modalElement) {
                    hideModal(modalElement);
                }
            });
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeDeleteModal);
        });

        if (modalForm) {
            modalForm.addEventListener('submit', (event) => {
                if (!activeAction) {
                    event.preventDefault();
                    return;
                }

                if (confirmButton) {
                    confirmButton.disabled = true;
                    confirmButton.textContent = 'Deleting...';
                    confirmButton.classList.add('cursor-not-allowed', 'opacity-70');
                }
            });
        }

        root.querySelectorAll('[data-modal-submit-form]').forEach((formElement) => {
            formElement.addEventListener('submit', () => {
                const submitButton = formElement.querySelector('[data-submit-button]');
                const statusLabel = formElement.querySelector('[data-submit-status]');
                const closeButton = formElement.querySelector('[data-modal-close]');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = submitButton.dataset.loadingLabel || 'Saving...';
                    submitButton.classList.add('cursor-not-allowed', 'opacity-80');
                }

                if (closeButton) {
                    closeButton.disabled = true;
                    closeButton.classList.add('cursor-not-allowed', 'opacity-60');
                }

                if (statusLabel) {
                    statusLabel.classList.remove('hidden');
                }
            });
        });

        root.querySelectorAll('[data-row-action-form]').forEach((formElement) => {
            formElement.addEventListener('submit', () => {
                const actionButton = formElement.querySelector('[data-row-action-button]');

                if (!actionButton) {
                    return;
                }

                actionButton.disabled = true;
                actionButton.textContent = actionButton.dataset.loadingLabel || 'Processing...';
                actionButton.classList.add('cursor-not-allowed', 'opacity-80');
            });
        });

        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (!deleteModal.classList.contains('hidden')) {
                closeDeleteModal();
            }

            managedModals.forEach((modalElement) => {
                if (!modalElement.classList.contains('hidden')) {
                    hideModal(modalElement);
                }
            });
        });

        if (initialModalId) {
            const initialModal = document.getElementById(initialModalId);

            if (initialModal) {
                showModal(initialModal);
            }
        }
    });
</script>
@endsection
