@extends('layouts.app')

@section('title', 'Central Dashboard')

@section('content')
<div class="space-y-8" data-delete-tenant-root>
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
            Tenant credentials are sent by email during registration. Use this table to verify the correct tenant domain only, not as a central-app login shortcut for tenant accounts.
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
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tenants as $tenant)
                        <tr class="align-top">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $tenant->name }}</div>
                                <div class="text-xs text-slate-500">Slug: {{ $tenant->slug }}</div>
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $workspaceUrl = \App\Support\TenantUrl::workspace($tenant);
                                    $loginUrl = \App\Support\TenantUrl::login($tenant);
                                    $workspaceHost = parse_url($workspaceUrl, PHP_URL_HOST) ?: 'N/A';
                                @endphp
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
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] leading-5 text-slate-500">
                                        Credentials sent by email. Central users can verify or open the tenant domain, but tenant accounts should sign in from their own workspace link.
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $tenant->address ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $tenant->contact_number ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $tenant->email ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ optional($tenant->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $tenant->plan?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $tenant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <button
                                    type="button"
                                    data-delete-tenant-trigger
                                    data-tenant-name="{{ $tenant->name }}"
                                    data-tenant-action="{{ route('central.tenants.destroy', $tenant) }}"
                                    class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-50"
                                >
                                    Delete tenant
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">No tenants have been registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

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
        const root = document.querySelector('[data-delete-tenant-root]');
        const modal = document.getElementById('delete-tenant-modal');

        if (!root || !modal) {
            return;
        }

        const tenantNameTarget = modal.querySelector('[data-delete-tenant-name]');
        const modalForm = modal.querySelector('[data-delete-tenant-modal-form]');
        const confirmButton = modal.querySelector('[data-delete-tenant-confirm]');
        const closeButtons = modal.querySelectorAll('[data-delete-tenant-close], [data-delete-tenant-cancel]');
        const confirmButtonLabel = confirmButton ? confirmButton.textContent : 'Confirm delete';
        let activeAction = null;

        const openModal = (action, tenantName) => {
            activeAction = action;
            tenantNameTarget.textContent = tenantName;
            if (modalForm) {
                modalForm.setAttribute('action', action || '');
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.textContent = confirmButtonLabel;
                confirmButton.classList.remove('cursor-not-allowed', 'opacity-70');
            }
            confirmButton.focus();
        };

        const closeModal = () => {
            activeAction = null;
            if (modalForm) {
                modalForm.setAttribute('action', '');
            }
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        root.querySelectorAll('[data-delete-tenant-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal(button.dataset.tenantAction || '', button.dataset.tenantName || 'this tenant');
            });
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        if (modalForm) {
            modalForm.addEventListener('submit', (event) => {
                if (! activeAction) {
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

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
@endsection
