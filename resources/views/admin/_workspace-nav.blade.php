@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
    $workspaceHost = $tenant ? parse_url(\App\Support\TenantUrl::workspace($tenant), PHP_URL_HOST) : null;
@endphp

<div class="mb-6 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-xl shadow-slate-200/50">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(135deg,_#f8fffc_0%,_#ffffff_48%,_#f8fafc_100%)] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-600">Tenant admin workspace</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
                @if(!empty($description))
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">{{ $description }}</p>
                @endif
                @if($workspaceHost)
                    <p class="mt-3 inline-flex items-center rounded-full border border-emerald-200 bg-white/80 px-4 py-2 text-sm text-slate-500 shadow-sm">
                        Active domain:
                        <span class="ml-2 font-semibold text-slate-800">{{ $workspaceHost }}</span>
                    </p>
                @endif
            </div>

            @if(!empty($actions ?? []))
                <div class="flex flex-wrap gap-2">
                    @foreach($actions as $action)
                        <a
                            href="{{ $action['href'] }}"
                            class="{{ ($action['variant'] ?? 'secondary') === 'primary'
                                ? 'rounded-full bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800'
                                : 'rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50' }}"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="border-t border-slate-200 bg-slate-50/80 px-6 py-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Dashboard</a>
            <a href="{{ route('admin.profile') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.profile') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Workspace info</a>
            <a href="{{ route('admin.users.pending') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.users.pending') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Pending staff</a>
            <a href="{{ route('admin.users.index') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.users.index') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Office staff</a>
            <a href="{{ route('admin.users.archived') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.users.archived') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Archived staff</a>
            <a href="{{ route('admin.qr') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.qr*') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">QR codes</a>
            <a href="{{ route('admin.reports') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.reports') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Reports</a>
            <a href="{{ route('admin.customization.index') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.customization.*') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Customization</a>
            <a href="{{ route('admin.settings.edit') }}" class="rounded-full px-4 py-2.5 text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Admin settings</a>
        </div>
    </div>
</div>
