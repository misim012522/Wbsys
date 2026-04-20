@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $viewer = auth()->user();
    $guestQueueEnabled = $tenant?->getSetting('customization.guest_queue', true) ?? true;
    $supportUnreadCount = \App\Models\SupportThread::unreadCountForTenant($tenant?->id);
@endphp

<style>
    html {
        overflow: hidden;
        height: 100%;
    }
    body {
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .app-shell {
        padding: 0 !important;
        max-width: none !important;
        margin: 0 !important;
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .admin-layout {
        display: flex;
        height: 100%;
        width: 100vw;
        margin-left: calc(-50vw + 50%);
        position: relative;
        margin-top: 0;
    }
    .admin-sidebar {
        width: 16rem;
        background-color: rgb(248, 250, 252);
        border-right: 1px solid rgb(226, 232, 240);
        padding: 1.5rem;
        padding-left: 1.5rem;
        flex-shrink: 0;
        margin: 0;
    }
    .admin-sidebar nav {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .admin-sidebar a,
    .admin-sidebar button {
        margin-bottom: -1px;
    }
    .admin-sidebar a:first-child,
    .admin-sidebar button:last-child {
        border-radius: 0.5rem;
    }
    .admin-sidebar a:not(:first-child) {
        border-top: none;
    }
    .admin-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }
</style>

<div class="admin-layout">
    <!-- Fixed Sidebar Navigation -->
    <aside class="admin-sidebar">
        <nav>
            <a href="{{ route('admin.dashboard') }}" class="block w-full px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                Dashboard
            </a>
            @if($viewer?->hasPermission('users.manage'))
                <a href="{{ route('admin.users.index') }}" class="block w-full px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.users.index') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Office staff
                </a>
            @endif
            @if($viewer?->hasPermission('admin.office.serve') && $guestQueueEnabled)
                <a href="{{ route('admin.qr') }}" class="block w-full px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.qr*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    QR codes
                </a>
            @endif
            @if($viewer?->hasPermission('reports.view'))
                <a href="{{ route('admin.reports') }}" class="block w-full px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.reports') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Reports
                </a>
            @endif
            @if($viewer?->hasPermission('admin.customization.manage'))
                <a href="{{ route('admin.customization.index') }}" class="block w-full px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.customization.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Customization
                </a>
            @endif
            @if($viewer?->hasPermission('admin.settings.manage'))
                <a href="{{ route('admin.settings.edit') }}" class="block w-full px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.settings.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Admin settings
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="w-full" id="admin-logout-form">
                @csrf
                <button
                    type="button"
                    onclick="window.showToast.success('Logged out successfully. Redirecting...'); this.disabled = true; setTimeout(() => document.getElementById('admin-logout-form').submit(), 500);"
                    class="block w-full px-4 py-3 text-sm font-medium transition border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                >
                    Log out
                </button>
            </form>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-content">
        <div class="panel mb-6 overflow-visible">
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
        </div>

        <div class="space-y-6">
