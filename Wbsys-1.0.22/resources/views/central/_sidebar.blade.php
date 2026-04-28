@php
    $supportUnreadCount = \App\Models\SupportThread::unreadCountForCentral();
@endphp

<aside class="w-64 bg-slate-50 border-r border-slate-200 p-6 flex-shrink-0">
    <div class="mb-6">
        <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">
            Central Admin
        </span>
        <h1 class="mt-2 text-lg font-bold text-slate-900">Workspace</h1>
    </div>

    <nav>
        <a href="{{ route('central.dashboard') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition mb-2 {{ request()->routeIs('central.dashboard') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100' }}">
            Dashboard
        </a>
        <a href="{{ route('central.support.index') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition mb-2 {{ request()->routeIs('central.support.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100' }}">
            Support{{ $supportUnreadCount ? ' ('.$supportUnreadCount.')' : '' }}
        </a>
        <form method="POST" action="{{ route('logout') }}" class="w-full" id="central-logout-form">
            @csrf
            <button
                type="button"
                onclick="window.showToast?.success('Logged out successfully. Redirecting...'); this.disabled = true; setTimeout(() => document.getElementById('central-logout-form').submit(), 500);"
                class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition border border-slate-200 bg-white text-slate-700 hover:bg-slate-100"
            >
                Log out
            </button>
        </form>
    </nav>

    <div class="pt-8 flex flex-col items-center">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200/50 border border-slate-300/40">
            <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse ring-4 ring-emerald-500/10"></span>
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none">
                {{ str_starts_with($appVersion ?? '', 'v') ? $appVersion : 'v' . ($appVersion ?? '1.0.0') }}
            </span>
        </div>
        <p class="mt-2 text-[9px] font-medium text-slate-400/80 uppercase tracking-tighter">Queueless</p>
    </div>
</aside>
