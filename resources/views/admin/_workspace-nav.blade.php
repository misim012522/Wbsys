@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $viewer = auth()->user();
    $guestQueueEnabled = $tenant?->getSetting('customization.guest_queue', true) ?? true;
    $supportUnreadCount = \App\Models\SupportThread::unreadCountForTenant($tenant?->id);
@endphp

<div class="panel mb-6 overflow-hidden">
    <div class="panel-section">
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

    <div class="border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:px-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Dashboard</a>
            @if($viewer?->hasPermission('users.manage'))
                <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.users.index') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Office staff</a>
            @endif
            @if($viewer?->hasPermission('admin.office.serve') && $guestQueueEnabled)
                <a href="{{ route('admin.qr') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.qr*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">QR codes</a>
            @endif
            @if($viewer?->hasPermission('reports.view'))
                <a href="{{ route('admin.reports') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.reports') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Reports</a>
            @endif
            @if($viewer?->hasPermission('admin.rbac.manage'))
                <a href="{{ route('admin.rbac.edit') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.rbac.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Access control</a>
            @endif
            @if($viewer?->hasPermission('admin.customization.manage'))
                <a href="{{ route('admin.customization.index') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.customization.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Customization</a>
            @endif
            @if($viewer?->hasPermission('admin.settings.manage'))
                <a href="{{ route('admin.settings.edit') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Admin settings</a>
            @endif
        </div>
    </div>
</div>
