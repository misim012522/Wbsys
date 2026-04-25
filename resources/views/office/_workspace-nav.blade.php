@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $viewer = auth()->user();
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
    .office-layout {
        display: flex;
        height: 100%;
        width: 100vw;
        margin-left: calc(-50vw + 50%);
        position: relative;
        margin-top: 0;
    }
    .office-sidebar {
        width: 16rem;
        background-color: rgb(248, 250, 252);
        border-right: 1px solid rgb(226, 232, 240);
        padding: 1.5rem;
        flex-shrink: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
    }
    .office-sidebar nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .office-sidebar a:hover,
    .office-sidebar button:hover {
        background-color: rgb(241, 245, 249);
        border-color: rgb(203, 213, 225);
    }
    .office-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }
</style>

<div class="office-layout">
    <aside class="office-sidebar">
        <nav>
            @if($viewer?->hasPermission('office.dashboard'))
                <a href="{{ route('office.dashboard') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('office.dashboard') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                    My queue
                </a>
            @endif
            @if($viewer?->hasPermission('office.qr'))
                <a href="{{ route('office.qr') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('office.qr*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                    QR access
                </a>
            @endif
            @if($viewer?->hasPermission('reports.view'))
                <a href="{{ route('office.reports') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('office.reports*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                    Reports
                </a>
            @endif
            @if($viewer?->hasPermission('office.activity.view'))
                <a href="{{ route('office.activity') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('office.activity*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                    Activity
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="w-full" id="office-logout-form">
                @csrf
                <button
                    type="button"
                    onclick="window.showToast.success('Logged out successfully. Redirecting...'); this.disabled = true; setTimeout(() => document.getElementById('office-logout-form').submit(), 500);"
                    class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30"
                >
                    Log out
                </button>
            </form>
        </nav>

        <div class="mt-auto pt-8 flex flex-col items-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200/50 border border-slate-300/40">
                <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse ring-4 ring-emerald-500/10"></span>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none">
                    {{ str_starts_with($appVersion ?? '', 'v') ? $appVersion : 'v' . ($appVersion ?? '1.0.0') }}
                </span>
            </div>
            <p class="mt-2 text-[9px] font-medium text-slate-400/80 uppercase tracking-tighter">Queueless</p>
        </div>
    </aside>

    <div class="office-content" data-live-refresh-root="workspace">
        <div class="panel mb-6 overflow-visible">
            <div class="panel-section">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Office staff workspace</p>
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
                                        : 'rounded-lg border border-slate-300 bg-white/50/50 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-white/50/30' }}"
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
