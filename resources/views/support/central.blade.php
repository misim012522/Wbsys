@extends('layouts.app')

@section('title', 'Central Support Inbox')

@section('content')
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Central support</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Tenant support inbox</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Review support concerns and update questions sent by tenants. Reply from here so each tenant has a single conversation history.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span id="central-support-unread-badge" class="rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold text-sky-700 {{ $unreadCount > 0 ? '' : 'hidden' }}">
            {{ $unreadCount }} unread
        </span>
        <a href="{{ route('central.dashboard') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to central dashboard</a>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[0.95fr_1.4fr]">
    @if(($supportReady ?? true) === false)
        <div class="xl:col-span-2 rounded-3xl border border-amber-200 bg-amber-50 px-6 py-5 text-sm text-amber-900">
            Support chat is not ready yet because the support tables are still missing in the central database. Run `php artisan migrate` first, then refresh this page.
        </div>
    @endif

    <section class="space-y-5">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Send announcement</p>
            <form method="POST" action="{{ route('central.support.announcements.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="announcement_tenant_id" class="block text-sm font-medium text-slate-700">Tenant</label>
                    <select id="announcement_tenant_id" name="tenant_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="announcement_subject" class="block text-sm font-medium text-slate-700">Subject</label>
                    <input id="announcement_subject" name="subject" type="text" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20" placeholder="Example: Scheduled update tonight">
                </div>
                <div>
                    <label for="announcement_message" class="block text-sm font-medium text-slate-700">Announcement message</label>
                    <textarea id="announcement_message" name="message" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20" placeholder="Send an update or important announcement to a tenant."></textarea>
                </div>
                <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60" {{ ($supportReady ?? true) ? '' : 'disabled' }}>Send announcement</button>
            </form>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">All tenant threads</p>
        </div>
        <div id="central-support-thread-list" class="max-h-[42rem] overflow-y-auto p-4">
            @include('support.partials.central-thread-list', ['threads' => $threads, 'activeThread' => $activeThread])
        </div>
        </section>
    </section>

    <section class="flex min-h-[46rem] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div id="central-support-conversation" class="min-h-0 flex-1">
            @include('support.partials.central-conversation', ['activeThread' => $activeThread])
        </div>
        @if(($supportReady ?? true) && $activeThread)
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('central.support.messages.store', $activeThread) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="central_reply_message" class="block text-sm font-medium text-slate-700">Reply to tenant</label>
                        <textarea id="central_reply_message" name="message" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20" placeholder="Type your reply or update for the tenant...">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Send reply</button>
                </form>
            </div>
        @endif
    </section>
</div>

@if(($supportReady ?? true) && $activeThread)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const threadList = document.getElementById('central-support-thread-list');
        const conversation = document.getElementById('central-support-conversation');
        const unreadBadge = document.getElementById('central-support-unread-badge');
        const snapshotUrl = @json(route('central.support.snapshot', ['thread' => $activeThread->id]));

        if (!threadList || !conversation || !window.realtimeRefresh) {
            return;
        }

        window.realtimeRefresh.register('central-support-thread-list', snapshotUrl, (_element, data) => {
            if (data.thread_list_html) {
                threadList.innerHTML = data.thread_list_html;
            }

            if (data.conversation_html) {
                conversation.innerHTML = data.conversation_html;
            }

            if (unreadBadge) {
                const unreadCount = Number(data.unread_count || 0);
                unreadBadge.textContent = `${unreadCount} unread`;
                unreadBadge.classList.toggle('hidden', unreadCount < 1);
            }
        }, 5000);
    });
</script>
@endif
@endsection
