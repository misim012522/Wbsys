@extends('layouts.app')

@section('title', 'Roles and Permissions')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Roles and permissions',
    'description' => 'Create tenant-specific roles, assign permissions visually, and control what staff can open in this workspace.',
])

@php
    $roleCount = $roles->count();
    $customRoleCount = $roles->whereNotNull('tenant_id')->count();
    $activeRoleCount = $roles->where('is_active', true)->count();
    $permissionGroupCount = $permissionGroups->count();
@endphp

@error('role')
    <div class="mb-6 rounded-[1.5rem] border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
        {{ $message }}
    </div>
@enderror

<div class="mb-8 grid gap-4 md:grid-cols-3">
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Workspace roles</p>
        <p class="mt-3 text-3xl font-bold text-slate-900">{{ $roleCount }}</p>
        <p class="mt-2 text-sm text-slate-500">Total roles currently available across built-in and tenant-specific access control.</p>
    </div>
    <div class="rounded-[1.75rem] border border-emerald-200 bg-gradient-to-br from-white to-emerald-50 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Custom roles</p>
        <p class="mt-3 text-3xl font-bold text-slate-900">{{ $customRoleCount }}</p>
        <p class="mt-2 text-sm text-slate-600">Tenant-defined roles that you can rename, reconfigure, enable, disable, or remove.</p>
    </div>
    <div class="rounded-[1.75rem] border border-sky-200 bg-gradient-to-br from-white to-sky-50 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Active roles</p>
        <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeRoleCount }}</p>
        <p class="mt-2 text-sm text-slate-600">Only active roles grant access to tenant routes and workspace actions.</p>
    </div>
</div>

<div class="mb-8 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm" data-role-filters>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Role explorer</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Search and narrow down tenant roles</h2>
            <p class="mt-2 text-sm text-slate-500">Quickly focus on active, disabled, built-in, or custom roles while editing access rules.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-role-filter="all" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm">All roles</button>
            <button type="button" data-role-filter="active" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Active</button>
            <button type="button" data-role-filter="disabled" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Disabled</button>
            <button type="button" data-role-filter="custom" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Custom</button>
            <button type="button" data-role-filter="built-in" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Built-in</button>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr),240px]">
        <div>
            <label for="role-search" class="mb-1.5 block text-sm font-medium text-slate-700">Search roles</label>
            <input id="role-search" type="text" placeholder="Search by role name, slug, or description" class="w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Permission groups</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $permissionGroupCount }}</p>
            <p class="mt-1 text-sm text-slate-500">Each group can collapse to reduce clutter during role editing.</p>
        </div>
    </div>
</div>

<div class="mb-8 grid gap-6 xl:grid-cols-[420px,minmax(0,1fr)]">
    <section class="rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_36%),linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] px-6 py-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Create role</p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Design a new staff access profile</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">Choose a clear role name, assign a unique slug, then switch on the permissions this role should own.</p>
        </div>

        <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-5 px-6 py-6">
            @csrf

            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4">
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Role name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Records Officer" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Role slug</label>
                        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="records_officer" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                        <p class="mt-2 text-xs text-slate-500">Use lowercase letters, numbers, dashes, or underscores only.</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                        <textarea name="description" rows="3" placeholder="Handles reports and records-related workflows." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                @foreach($permissionGroups as $group => $permissions)
                    <details class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white group" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ str($group)->replace('_', ' ')->title() }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $permissions->count() }} permissions</p>
                            </div>
                            <span class="text-slate-400 transition group-open:rotate-180">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                            </span>
                        </summary>
                        <div class="space-y-3 p-4">
                            @foreach($permissions as $permission)
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', []))) class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $permission->name }}</span>
                                        <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-slate-400">{{ $permission->slug }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                Create role
            </button>
        </form>
    </section>

    <section class="space-y-6">
        @foreach($roles as $role)
            <article
                class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm"
                data-role-card
                data-role-filter-state="{{ $role->is_active ? 'active' : 'disabled' }}"
                data-role-type="{{ $role->isProtected() ? 'built-in' : 'custom' }}"
                data-role-search="{{ Str::lower(trim($role->name.' '.$role->slug.' '.($role->description ?? ''))) }}"
            >
                <div class="border-b border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $role->name }}</h2>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $role->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $role->is_active ? 'Active' : 'Disabled' }}
                                </span>
                                @if($role->isProtected())
                                    <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">Built-in protected role</span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm text-slate-500">
                                <span class="font-medium text-slate-700">{{ $role->slug }}</span>
                                @if($role->description)
                                    <span class="mx-2 text-slate-300">&bull;</span>{{ $role->description }}
                                @endif
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[280px]">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Assigned users</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $role->assignedUsersCount() }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Permissions</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $role->permissions->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6 px-6 py-6">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 lg:grid-cols-[1.1fr,0.9fr]">
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Role details</p>
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Role name</label>
                                    <input type="text" name="name" value="{{ old('name', $role->name) }}" @disabled($role->isProtected()) class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 disabled:cursor-not-allowed disabled:bg-slate-100">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                                    <input type="text" name="description" value="{{ old('description', $role->description) }}" @disabled($role->isProtected()) class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 disabled:cursor-not-allowed disabled:bg-slate-100">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Role status</p>
                            <div class="mt-4 space-y-3 text-sm text-slate-600">
                                <p>This role is currently <span class="font-semibold {{ $role->is_active ? 'text-emerald-700' : 'text-slate-700' }}">{{ strtolower($role->is_active ? 'Active' : 'Disabled') }}</span>.</p>
                                @if($role->isProtected())
                                    <p>Built-in roles stay locked so the core tenant workspace can keep its expected access rules.</p>
                                @else
                                    <p>You can disable this role to instantly stop it from granting permissions, or delete it after all users are reassigned.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach($permissionGroups as $group => $permissions)
                            <details class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white group">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ str($group)->replace('_', ' ')->title() }}</p>
                                        <span class="text-xs text-slate-400">{{ $permissions->count() }} permissions</span>
                                    </div>
                                    <span class="text-slate-400 transition group-open:rotate-180">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                                    </span>
                                </summary>

                                <div class="grid gap-3 p-4 md:grid-cols-2">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition {{ $role->isProtected() ? '' : 'hover:border-emerald-200 hover:bg-emerald-50/60' }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains('id', $permission->id)) @disabled($role->isProtected()) class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:cursor-not-allowed">
                                            <span>
                                                <span class="block font-semibold text-slate-900">{{ $permission->name }}</span>
                                                <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-slate-400">{{ $permission->slug }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-2">
                        <button type="submit" @disabled($role->isProtected()) class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">
                            Save permissions
                        </button>

                        @unless($role->isProtected())
                            <form method="POST" action="{{ route('admin.roles.status', $role) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                                    {{ $role->is_active ? 'Disable role' : 'Enable role' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-2xl border border-red-300 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                                    Delete role
                                </button>
                            </form>
                        @endunless
                    </div>
                </form>
            </article>
        @endforeach
    </section>
</div>

<script>
(function () {
    var container = document.querySelector('[data-role-filters]');
    if (! container) {
        return;
    }

    var searchInput = container.querySelector('#role-search');
    var filterButtons = container.querySelectorAll('[data-role-filter]');
    var roleCards = document.querySelectorAll('[data-role-card]');
    var currentFilter = 'all';

    function setActiveButton(activeButton) {
        filterButtons.forEach(function (button) {
            button.className = 'rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50';
        });

        activeButton.className = 'rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm';
    }

    function applyFilters() {
        var query = (searchInput.value || '').toLowerCase().trim();

        roleCards.forEach(function (card) {
            var state = card.getAttribute('data-role-filter-state');
            var type = card.getAttribute('data-role-type');
            var haystack = card.getAttribute('data-role-search') || '';

            var matchesFilter = currentFilter === 'all'
                || currentFilter === state
                || currentFilter === type;

            var matchesSearch = query === '' || haystack.indexOf(query) !== -1;

            card.style.display = matchesFilter && matchesSearch ? '' : 'none';
        });
    }

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            currentFilter = button.getAttribute('data-role-filter') || 'all';
            setActiveButton(button);
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    applyFilters();
})();
</script>
@endsection
