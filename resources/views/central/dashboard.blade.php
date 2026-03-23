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
            View all registered tenants and their subscription details from the central system.
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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Tenant Name</th>
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
                                <form method="POST" action="{{ route('central.tenants.destroy', $tenant) }}" data-delete-tenant-form>
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        data-delete-tenant-trigger
                                        data-tenant-name="{{ $tenant->name }}"
                                        class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-50"
                                    >
                                        Delete tenant
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">No tenants have been registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="delete-tenant-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4">
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

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-delete-tenant-cancel class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" data-delete-tenant-confirm class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                Delete tenant
            </button>
        </div>
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
        const confirmButton = modal.querySelector('[data-delete-tenant-confirm]');
        const closeButtons = modal.querySelectorAll('[data-delete-tenant-close], [data-delete-tenant-cancel]');
        let activeForm = null;

        const openModal = (form, tenantName) => {
            activeForm = form;
            tenantNameTarget.textContent = tenantName;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            confirmButton.focus();
        };

        const closeModal = () => {
            activeForm = null;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        root.querySelectorAll('[data-delete-tenant-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal(button.closest('form'), button.dataset.tenantName || 'this tenant');
            });
        });

        confirmButton.addEventListener('click', () => {
            if (activeForm) {
                activeForm.submit();
            }
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

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
