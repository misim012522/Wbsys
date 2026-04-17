@php
    $showAuthenticatedHeader = ($forceAuthenticatedHeader ?? false)
        || (auth()->check() && ! (request()->routeIs('login') && app()->bound('current_tenant')));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'QueueLess') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { --font-sans: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        :root { --tenant-primary: {{ $tenantTheme['primary_color'] }}; }
        .tenant-primary { color: var(--tenant-primary); }
        .tenant-primary-bg { background-color: var(--tenant-primary); }
        .tenant-primary-bg:hover { filter: brightness(0.95); }
    </style>
</head>
<body
    class="min-h-screen overflow-x-clip bg-slate-100 text-slate-900 font-sans antialiased"
    @if(session('success')) data-success-message="{{ session('success') }}" @endif
    @if(session('error')) data-error-message="{{ session('error') }}" @endif
    @if(session('info')) data-info-message="{{ session('info') }}" @endif
    @if(session('status')) data-status-message="{{ session('status') }}" @endif
    @if(auth()->check() && app()->bound('current_tenant') && ! request()->routeIs('login')) data-tenant-session-monitor-url="{{ route('api.session.tenant-status') }}" @endif
>
    <div id="toast-container"></div>

    <header class="border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur">
        <nav class="app-header-shell">
            @php
                $brandName = $tenantTheme['app_name'];
                $brandLogo = $tenantTheme['logo_url'];
                $tenantWorkspace = app()->bound('current_tenant') ? app('current_tenant') : auth()->user()?->tenant;
            @endphp

            @if($showAuthenticatedHeader)
                <a href="{{ \App\Support\TenantUrl::forUserDashboard(auth()->user()) }}" class="text-xl font-bold tenant-primary flex items-center gap-2">
                    @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="h-9 w-auto shrink-0">@endif
                    {{ $brandName }}
                </a>
            @else
                <a href="{{ app()->bound('current_tenant') ? route('tenant.home') : route('home') }}" class="text-xl font-bold tenant-primary flex items-center gap-2">
                    @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="h-9 w-auto shrink-0">@endif
                    {{ $brandName }}
                </a>
            @endif

            <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-4">
                @if($showAuthenticatedHeader)
                    @unless($tenantWorkspace && auth()->user()->isAdmin())
                        <a href="{{ \App\Support\TenantUrl::forUserDashboard(auth()->user()) }}" class="text-sm text-slate-600 hover:text-slate-900">
                            {{ auth()->user()->isCentralUser() ? 'Central' : (auth()->user()->isAdmin() ? 'Admin' : (auth()->user()->isOfficeStaff() ? 'Office' : 'My workspace')) }}
                        </a>
                    @endunless

                    <span class="text-sm text-slate-500">
                        @if($tenantWorkspace && auth()->user()->isAdmin())
                            {{ $tenantWorkspace->name }} Administrator
                        @else
                            {{ auth()->user()->name }}
                        @endif
                    </span>

                    @if(! auth()->user()->isCentralUser())
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.settings.edit') : route('tenant.settings.edit') }}" class="text-sm text-slate-500 hover:text-slate-700">
                            {{ auth()->user()->isAdmin() ? 'Admin settings' : 'Workspace settings' }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline" id="logout-form">
                        @csrf
                        <button
                            type="button"
                            class="text-sm text-slate-600 hover:text-slate-900"
                            onclick="window.showToast.success('Logged out successfully. Redirecting...'); this.disabled = true; setTimeout(() => document.getElementById('logout-form').submit(), 500);"
                        >
                            Log out
                        </button>
                    </form>
                @else
                    @if(app()->bound('current_tenant') && ! request()->routeIs('login'))
                        <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900">Log in</a>
                        <a href="{{ route('tenant.home') }}" class="rounded-full px-4 py-2 text-sm font-medium text-white tenant-primary-bg">Tenant workspace</a>
                    @endif
                @endif
            </div>
        </nav>
    </header>

    <div style="display: none;">
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded-lg text-sm">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-2 rounded-lg text-sm">{{ session('info') }}</div>
        @endif
        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 rounded-lg text-sm">{{ session('status') }}</div>
        @endif
    </div>

    <div class="app-shell">
        @yield('content')
    </div>

    @include('support.partials.tenant-floating-widget')
</body>
</html>
