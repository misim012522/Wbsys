@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $viewer = auth()->user();
    $guestQueueEnabled = $tenant?->getSetting('customization.guest_queue', true) ?? true;
@endphp

<div class="mb-6 overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
    <div class="p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Tenant admin workspace</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
                @if(!empty($description))
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">{{ $description }}</p>
                @endif
            </div>

            @if(!empty($actions ?? []))
                <div class="flex flex-wrap gap-2">
                    @foreach($actions as $action)
                        <a
                            href="{{ $action['href'] }}"
                            class="{{ ($action['variant'] ?? 'secondary') === 'primary'
                                ? 'rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800'
                                : 'rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50' }}"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="border-t border-slate-200 bg-slate-50/60 px-6 py-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Dashboard</a>
            <a href="{{ route('admin.profile') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.profile') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Workspace info</a>
            @if($viewer?->hasPermission('users.manage'))
                <a href="{{ route('admin.users.pending') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.users.pending') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Pending staff</a>
                <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.users.index') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Office staff</a>
                <a href="{{ route('admin.users.archived') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.users.archived') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Archived staff</a>
            @endif
            @if($viewer?->hasPermission('office.serve') && $guestQueueEnabled)
                <a href="{{ route('admin.qr') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.qr*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">QR codes</a>
            @endif
            @if($viewer?->hasPermission('reports.view'))
                <a href="{{ route('admin.reports') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.reports') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Reports</a>
            @endif
            @if($viewer?->isTenantAdmin())
                <a href="{{ route('admin.roles.index') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.roles.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Roles & permissions</a>
                <a href="{{ route('admin.customization.index') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.customization.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Customization</a>
                <a href="{{ route('admin.settings.edit') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Admin settings</a>
            @endif
        </div>
    </div>
</div>
