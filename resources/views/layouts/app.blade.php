@php
    $showAuthenticatedHeader = ($forceAuthenticatedHeader ?? false)
        || (auth()->check() && ! (request()->routeIs('login') && app()->bound('current_tenant')));
    // Use the injected appVersion from AppServiceProvider, fallback only if not set
    $appVersion = $appVersion ?? config('app.version', '1.0.0');
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

        /* Tenant-wide accent overrides so customization primary color applies to highlights/buttons */
        .tenant-themed {
            --tenant-primary-strong: color-mix(in srgb, var(--tenant-primary) 90%, black);
            --tenant-primary-soft-20: color-mix(in srgb, var(--tenant-primary) 20%, white);
            --tenant-primary-soft-12: color-mix(in srgb, var(--tenant-primary) 12%, white);
            --tenant-primary-soft-8: color-mix(in srgb, var(--tenant-primary) 8%, white);
            --tenant-primary-bubble: color-mix(in srgb, var(--tenant-primary) 8%, white);
            --tenant-primary-ring-20: color-mix(in srgb, var(--tenant-primary) 20%, transparent);
            --tenant-primary-ring-10: color-mix(in srgb, var(--tenant-primary) 10%, transparent);
            --tenant-primary-shadow-25: color-mix(in srgb, var(--tenant-primary) 25%, transparent);
            --tenant-primary-shadow-30: color-mix(in srgb, var(--tenant-primary) 30%, transparent);
        }

        .tenant-themed .bg-emerald-500,
        .tenant-themed .bg-emerald-600,
        .tenant-themed .bg-emerald-700,
        .tenant-themed .bg-slate-900,
        .tenant-themed a.bg-emerald-500,
        .tenant-themed a.bg-emerald-600,
        .tenant-themed a.bg-emerald-700,
        .tenant-themed a.bg-slate-900,
        .tenant-themed button.bg-emerald-500,
        .tenant-themed button.bg-emerald-600,
        .tenant-themed button.bg-emerald-700,
        .tenant-themed button.bg-slate-900 {
            background-color: var(--tenant-primary) !important;
        }

        .tenant-themed .bg-emerald-50,
        .tenant-themed .bg-emerald-100,
        .tenant-themed .bg-emerald-200,
        .tenant-themed .panel:not(.bg-slate-900),
        .tenant-themed .stat-card {
            background-color: var(--tenant-primary-bubble) !important;
            border-color: color-mix(in srgb, var(--tenant-primary) 15%, transparent) !important;
            box-shadow: 0 4px 6px -1px var(--tenant-primary-shadow-25), 0 2px 4px -2px var(--tenant-primary-shadow-25) !important;
        }

        .tenant-themed .bg-emerald-50,
        .tenant-themed .bg-emerald-100,
        .tenant-themed .bg-emerald-200 {
            background-color: var(--tenant-primary-soft-12) !important;
        }

        .tenant-themed .border-emerald-200,
        .tenant-themed .border-emerald-300,
        .tenant-themed .border-emerald-500,
        .tenant-themed .border-emerald-600,
        .tenant-themed .border-slate-900,
        .tenant-themed a.border-emerald-500,
        .tenant-themed a.border-emerald-600,
        .tenant-themed a.border-slate-900,
        .tenant-themed button.border-emerald-500,
        .tenant-themed button.border-emerald-600,
        .tenant-themed button.border-slate-900 {
            border-color: var(--tenant-primary) !important;
        }

        .tenant-themed .text-emerald-500,
        .tenant-themed .text-emerald-600,
        .tenant-themed .text-emerald-700,
        .tenant-themed .text-emerald-800 {
            color: var(--tenant-primary) !important;
        }

        .tenant-themed .ring-emerald-100,
        .tenant-themed .ring-emerald-200,
        .tenant-themed .ring-emerald-300,
        .tenant-themed .ring-emerald-400,
        .tenant-themed .ring-emerald-500,
        .tenant-themed .ring-emerald-600 {
            --tw-ring-color: var(--tenant-primary-ring-20) !important;
        }

        .tenant-themed .ring-emerald-100\/50,
        .tenant-themed .ring-emerald-200\/50,
        .tenant-themed .ring-emerald-300\/50,
        .tenant-themed .ring-emerald-400\/50,
        .tenant-themed .ring-emerald-500\/50,
        .tenant-themed .ring-emerald-600\/50 {
            --tw-ring-color: var(--tenant-primary-ring-10) !important;
        }

        .tenant-themed .from-emerald-50,
        .tenant-themed .from-emerald-100,
        .tenant-themed .from-emerald-200,
        .tenant-themed .from-emerald-300,
        .tenant-themed .from-emerald-400,
        .tenant-themed .from-emerald-500,
        .tenant-themed .from-emerald-600,
        .tenant-themed .from-emerald-700,
        .tenant-themed .from-emerald-800,
        .tenant-themed .from-emerald-900 {
            --tw-gradient-from: var(--tenant-primary-soft-12) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: color-mix(in srgb, var(--tenant-primary-soft-12) 0%, transparent) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }

        .tenant-themed .via-emerald-50,
        .tenant-themed .via-emerald-100,
        .tenant-themed .via-emerald-200,
        .tenant-themed .via-emerald-300,
        .tenant-themed .via-emerald-400,
        .tenant-themed .via-emerald-500,
        .tenant-themed .via-emerald-600,
        .tenant-themed .via-emerald-700,
        .tenant-themed .via-emerald-800,
        .tenant-themed .via-emerald-900 {
            --tw-gradient-via: var(--tenant-primary-soft-20) var(--tw-gradient-via-position) !important;
            --tw-gradient-to: color-mix(in srgb, var(--tenant-primary-soft-20) 0%, transparent) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-via), var(--tw-gradient-to) !important;
        }

        .tenant-themed .to-emerald-50,
        .tenant-themed .to-emerald-100,
        .tenant-themed .to-emerald-200,
        .tenant-themed .to-emerald-300,
        .tenant-themed .to-emerald-400,
        .tenant-themed .to-emerald-500,
        .tenant-themed .to-emerald-600,
        .tenant-themed .to-emerald-700,
        .tenant-themed .to-emerald-800,
        .tenant-themed .to-emerald-900 {
            --tw-gradient-to: var(--tenant-primary-soft-20) var(--tw-gradient-to-position) !important;
        }

        .tenant-themed .to-emerald-50\/50,
        .tenant-themed .to-emerald-100\/50,
        .tenant-themed .to-emerald-200\/50,
        .tenant-themed .to-emerald-300\/50,
        .tenant-themed .to-emerald-400\/50,
        .tenant-themed .to-emerald-500\/50,
        .tenant-themed .to-emerald-600\/50,
        .tenant-themed .to-emerald-700\/50,
        .tenant-themed .to-emerald-800\/50,
        .tenant-themed .to-emerald-900\/50 {
            --tw-gradient-to: var(--tenant-primary-soft-8) var(--tw-gradient-to-position) !important;
        }

        .tenant-themed .focus\:ring-emerald-500:focus,
        .tenant-themed .focus\:ring-emerald-500:focus-visible,
        .tenant-themed .focus\:ring-emerald-500\/20:focus,
        .tenant-themed .focus\:ring-emerald-500\/20:focus-visible,
        .tenant-themed .focus\:ring-emerald-500\/10:focus,
        .tenant-themed .focus\:ring-emerald-500\/10:focus-visible {
            --tw-ring-color: var(--tenant-primary-ring-20) !important;
        }

        .tenant-themed .focus\:border-emerald-500:focus,
        .tenant-themed .focus\:border-emerald-500:focus-visible {
            border-color: var(--tenant-primary) !important;
        }

        .tenant-themed .hover\:bg-emerald-700:hover,
        .tenant-themed .hover\:bg-emerald-600:hover,
        .tenant-themed .hover\:bg-slate-800:hover,
        .tenant-themed .hover\:bg-slate-900:hover {
            background-color: var(--tenant-primary-strong) !important;
        }

        .tenant-themed .hover\:text-emerald-700:hover,
        .tenant-themed .hover\:text-emerald-600:hover {
            color: var(--tenant-primary-strong) !important;
        }

        .tenant-themed .shadow-emerald-500\/25 {
            --tw-shadow-color: var(--tenant-primary-shadow-25) !important;
        }

        .tenant-themed .shadow-emerald-500\/30 {
            --tw-shadow-color: var(--tenant-primary-shadow-30) !important;
        }
    </style>
</head>
<body
    class="min-h-screen overflow-x-clip bg-slate-100 text-slate-900 font-sans antialiased {{ app()->bound('current_tenant') ? 'tenant-themed' : '' }}"
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
                <div class="flex items-center gap-4">
                    <a href="{{ \App\Support\TenantUrl::forUserDashboard(auth()->user()) }}" class="text-xl font-bold tenant-primary flex items-center gap-2">
                        @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="h-9 w-auto shrink-0">@endif
                        {{ $brandName }}
                    </a>
                    <div class="flex flex-col border-l border-slate-200 pl-4">
                        <span class="text-sm font-semibold text-slate-700 leading-tight">
                            {{ $tenantWorkspace ? $tenantWorkspace->name : 'Central' }}
                        </span>
                        <span class="text-xs text-slate-500 leading-tight">
                            @if($tenantWorkspace && auth()->user()->isAdmin())
                                Administrator
                            @else
                                {{ auth()->user()->name }}
                            @endif
                        </span>
                    </div>
                </div>
            @else
                <a href="{{ app()->bound('current_tenant') ? route('tenant.home') : route('home') }}" class="text-xl font-bold tenant-primary flex items-center gap-2">
                    @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="h-9 w-auto shrink-0">@endif
                    {{ $brandName }}
                </a>
            @endif

            <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-4">
                @if($showAuthenticatedHeader)
                    {{-- Navigation and other actions can go here if needed --}}
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
