<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'QueueLess')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>:root { --font-sans: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }</style>
    @stack('styles')
</head>
<body
    class="min-h-screen bg-slate-50 text-slate-900 font-sans antialiased"
    @if(session('success')) data-success-message="{{ session('success') }}" @endif
    @if(session('error')) data-error-message="{{ session('error') }}" @endif
    @if(session('info')) data-info-message="{{ session('info') }}" @endif
    @if(session('status')) data-status-message="{{ session('status') }}" @endif
>
    <div id="toast-container"></div>

    @hasSection('public_full_width')
        @yield('content')
    @else
        <div class="max-w-lg mx-auto px-4 py-8">
            <a href="{{ url('/') }}" class="text-sm text-slate-500 hover:text-slate-700">&#8592; {{ isset($custom) ? $custom['app_name'] : config('app.name') }}</a>
            <div style="display: none;">
                @if (session('success'))
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
                @endif
            </div>
            @yield('content')
        </div>
    @endif
</body>
</html>
