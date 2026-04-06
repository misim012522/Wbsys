@extends('layouts.app')

@section('title', 'Central Dashboard')

@section('content')
<div class="space-y-8" data-central-dashboard-root data-open-modal="{{ session('open_modal', '') }}">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
            Central App
        </span>
        <h1 class="mt-4 text-3xl font-bold text-slate-900">Central dashboard</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">
            View all registered tenants, confirm each tenant domain, and monitor subscription details from the central system.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Tenants</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $tenantCount }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ $activeTenantCount }} active</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Plans</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $planCount }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">Available for onboarding</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Subscriptions</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $subscriptionCount }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">Tracked centrally</p>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Tenant credentials are sent by email during registration. Use this dashboard to manage activation, tenant details, subscription details, and central email notifications without logging in as the tenant.
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Tenant Name</th>
                        <th class="px-4 py-3 font-medium">Tenant Domain</th>
                        <th class="px-4 py-3 font-medium">Address</th>
                        <th class="px-4 py-3 font-medium">Contact Number</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Created At</th>
                        <th class="px-4 py-3 font-medium">Subscription Plan</th>
                        <th class="px-4 py-3 font-medium">Usage Summary</th>
                        <th class="px-4 py-3 font-medium">Last Activity</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="w-[16rem] px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
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
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $tenant->name }}</div>
                                <div class="text-xs text-slate-500">Slug: {{ $tenant->slug }}</div>
                                <div class="mt-3 space-y-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Main tenant account</p>
                                    @if($tenantAdmin)
                                        <div class="text-sm font-medium text-slate-800">{{ $tenantAdmin->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $tenantAdmin->username }}</div>
                                        <div class="text-xs text-slate-500">{{ $tenantAdmin->email }}</div>
                                    @else
                                        <div class="text-xs text-slate-500">No tenant admin account found.</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="space-y-2">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Tenant domain</p>
                                        <div class="break-all text-sm font-semibold text-slate-900">
                                            {{ $workspaceHost }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Workspace URL</p>
                                        <a href="{{ $workspaceUrl }}" class="break-all text-sm font-medium text-emerald-700 hover:text-emerald-800 hover:underline">
                                            {{ $workspaceUrl }}
                                        </a>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Login URL</p>
                                        <a href="{{ $loginUrl }}" class="break-all text-xs text-slate-600 hover:text-slate-900 hover:underline">
                                            {{ $loginUrl }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $tenant->address ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $tenant->contact_number ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">
                                <div>{{ $tenant->email ?: 'N/A' }}</div>
                                <div class="mt-1 text-xs text-slate-400">Registration contact</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ optional($tenant->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">
                                <div class="font-medium text-slate-900">{{ $tenant->plan?->name ?? 'N/A' }}</div>
                                @if($latestSubscription)
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ str($latestSubscription->status)->replace('_', ' ')->title() }}
                                        @if($latestSubscription->ends_at)
                                            until {{ $latestSubscription->ends_at->format('M d, Y') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <div class="space-y-1 text-xs">
                                    <div><span class="font-semibold text-slate-800">{{ $tenantInsight['office_staff_count'] }}</span> office staff</div>
                                    <div><span class="font-semibold text-slate-800">{{ $tenantInsight['office_count'] }}</span> offices</div>
                                    <div><span class="font-semibold text-slate-800">{{ $tenantInsight['today_queue_count'] }}</span> queues today</div>
                                    <div><span class="font-semibold text-slate-800">{{ $tenantInsight['today_appointment_count'] }}</span> appointments today</div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <div class="max-w-[14rem] text-sm text-slate-700">{{ $tenantInsight['last_activity_label'] }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $tenant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-full min-w-[14rem] space-y-4">
                                    <div class="space-y-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Manage</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                data-modal-target="tenant-edit-modal-{{ $tenant->id }}"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Edit tenant
                                            </button>

                                            <button
                                                type="button"
                                                data-modal-target="tenant-subscription-modal-{{ $tenant->id }}"
                                                class="rounded-lg border border-emerald-200 px-3 py-2 text-center text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                            >
                                                Edit subscription
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Access</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <form method="POST" action="{{ route('central.tenants.workspace-access', $tenant) }}" data-row-action-form>
                                                @csrf
                                                <button type="submit" data-row-action-button data-default-label="Send access email" data-loading-label="Sending email..." class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                                    Send access email
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('central.tenants.reset-password', $tenant) }}" data-row-action-form>
                                                @csrf
                                                <button type="submit" data-row-action-button data-default-label="Reset temp password" data-loading-label="Resetting..." class="w-full rounded-lg border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-50">
                                                    Reset temp password
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="space-y-2 border-t border-slate-200 pt-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status & danger</p>
                                        <form method="POST" action="{{ route('central.tenants.activation', $tenant) }}" data-row-action-form>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" data-row-action-button data-default-label="{{ $tenant->is_active ? 'Deactivate tenant' : 'Activate tenant' }}" data-loading-label="{{ $tenant->is_active ? 'Deactivating...' : 'Activating...' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                {{ $tenant->is_active ? 'Deactivate tenant' : 'Activate tenant' }}
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            data-delete-tenant-trigger
                                            data-tenant-name="{{ $tenant->name }}"
                                            data-tenant-action="{{ route('central.tenants.destroy', $tenant) }}"
                                            class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-50"
                                        >
                                            Delete tenant
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10">
                                <div class="mx-auto max-w-2xl rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9ZM8 9h8M8 12h8M8 15h5" />
                                        </svg>
                                    </div>
                                    <h3 class="mt-4 text-xl font-bold text-slate-900">No tenants registered yet</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        The central workspace is ready. Once a tenant completes registration, their workspace domain, subscription plan,
                                        tenant admin account, and activity summary will appear here for monitoring.
                                    </p>
                                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                        <a href="{{ route('central.register') }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            Register first tenant
                                        </a>
                                        <span class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-500">
                                            Usage summaries and recent activity will appear after onboarding
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
        $tenantSubscriptionErrorBag = 'tenantSubscription_'.$tenant->id;
        $isTenantUpdateModalOpen = session('open_modal') === 'tenant-edit-modal-'.$tenant->id;
        $isTenantSubscriptionModalOpen = session('open_modal') === 'tenant-subscription-modal-'.$tenant->id;
    @endphp
    <div id="tenant-edit-modal-{{ $tenant->id }}" data-dashboard-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
        <div class="w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Step 2</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Update tenant details</h2>
                    <p class="mt-2 text-sm text-slate-500">Edit the tenant profile, contact details, and workspace host settings from the central app.</p>
                </div>
                <button type="button" data-modal-close class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close dialog">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('central.tenants.update', $tenant) }}" class="mt-6" data-modal-submit-form>
                @csrf
                @method('PATCH')
                @if($errors->{$tenantUpdateErrorBag}->any())
                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Please review the tenant details below and fix the highlighted fields.
                    </div>
                @endif
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Tenant name</label>
                        <input type="text" name="name" value="{{ $isTenantUpdateModalOpen ? old('name', $tenant->name) : $tenant->name }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('name') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('name'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('name') }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Address</label>
                        <textarea name="address" rows="3" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('address') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">{{ $isTenantUpdateModalOpen ? old('address', $tenant->address) : $tenant->address }}</textarea>
                        @if($errors->{$tenantUpdateErrorBag}->has('address'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('address') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Contact number</label>
                        <input type="text" name="contact_number" value="{{ $isTenantUpdateModalOpen ? old('contact_number', $tenant->contact_number) : $tenant->contact_number }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantUpdateErrorBag}->has('contact_number') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantUpdateErrorBag}->has('contact_number'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantUpdateErrorBag}->first('contact_number') }}</p>
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

    <div id="tenant-subscription-modal-{{ $tenant->id }}" data-dashboard-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
        <div class="w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Step 3</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Update subscription</h2>
                    <p class="mt-2 text-sm text-slate-500">Change the tenant plan, lifecycle status, and effective dates from the central dashboard.</p>
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
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Ends at</label>
                        <input type="datetime-local" name="ends_at" value="{{ $isTenantSubscriptionModalOpen ? old('ends_at', optional($latestSubscription?->ends_at)->format('Y-m-d\\TH:i')) : optional($latestSubscription?->ends_at)->format('Y-m-d\\TH:i') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 {{ $errors->{$tenantSubscriptionErrorBag}->has('ends_at') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500/20' }}">
                        @if($errors->{$tenantSubscriptionErrorBag}->has('ends_at'))
                            <p class="mt-1 text-xs text-red-600">{{ $errors->{$tenantSubscriptionErrorBag}->first('ends_at') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
                    Saving this also syncs the tenant's current assigned plan and sends a tenant admin notification when email delivery is available.
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
        };

        const hideModal = (modalElement) => {
            modalElement.classList.add('hidden');
            modalElement.classList.remove('flex');

            if (!document.querySelector('[data-dashboard-modal].flex, #delete-tenant-modal.flex')) {
                document.body.classList.remove('overflow-hidden');
            }
        };

        const openDeleteModal = (action, tenantName) => {
            activeAction = action;
            tenantNameTarget.textContent = tenantName;

            if (modalForm) {
                modalForm.setAttribute('action', action || '');
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
