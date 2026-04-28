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
    .central-layout {
        display: flex;
        height: 100%;
        width: 100vw;
        margin-left: calc(-50vw + 50%);
        position: relative;
        margin-top: 0;
    }
    .central-sidebar {
        width: 16rem;
        background-color: rgb(248, 250, 252);
        border-right: 1px solid rgb(226, 232, 240);
        padding: 1.5rem;
        padding-left: 1.5rem;
        flex-shrink: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
    }
    .central-sidebar nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .central-sidebar a:hover,
    .central-sidebar button:hover {
        background-color: rgb(241, 245, 249);
        border-color: rgb(203, 213, 225);
    }
    .central-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }
</style>

<div class="central-layout">
    <aside class="central-sidebar">
        <nav>
            <a href="{{ route('central.dashboard') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('central.dashboard') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                Dashboard
            </a>
            <a href="{{ route('central.activity') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('central.activity') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                Activity logs
            </a>
            <a href="{{ route('central.notifications') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('central.notifications') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                Notifications
            </a>
            <a href="{{ route('central.support.index') }}" class="block w-full rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs('central.support.*') ? 'border border-slate-900 bg-slate-900 text-white' : 'border border-slate-200 bg-white/50/50 text-slate-700 hover:bg-white/50/30' }}">
                Support
            </a>
            <form method="POST" action="{{ route('logout') }}" class="w-full" id="central-logout-form">
                @csrf
                <button
                    type="button"
                    onclick="window.showToast?.success('Logged out successfully. Redirecting...'); this.disabled = true; setTimeout(() => document.getElementById('central-logout-form').submit(), 500);"
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

    <div class="central-content">
