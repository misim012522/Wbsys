@extends('layouts.app')

@section('title', 'Support and Updates')

@section('content')
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Support and updates</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Tenant support chat</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
            Send support concerns or update questions to the central team. Replies from central will appear in the same thread.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span id="tenant-support-unread-badge" class="rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold text-sky-700 {{ $unreadCount > 0 ? '' : 'hidden' }}">
            {{ $unreadCount }} unread
        </span>
        <a href="{{ route('dashboard') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to dashboard</a>
        <a href="{{ route('tenant.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Workspace settings</a>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[0.95fr_1.35fr]">
    @if(($supportReady ?? true) === false)
        <div class="xl:col-span-2 rounded-3xl border border-amber-200 bg-amber-50 px-6 py-5 text-sm text-amber-900">
            Support chat is not ready yet because the support tables are still missing in the central database. Run `php artisan migrate` first, then refresh this page.
        </div>
    @endif

    <section class="space-y-5">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Start new thread</p>
            <form method="POST" action="{{ route('support.tenant.threads.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700">Subject</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Example: Need help with tenant setup">
                    @error('subject')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="message" class="block text-sm font-medium text-slate-700">First message</label>
                    <textarea id="message" name="message" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Describe your concern or the update you need.">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60" {{ ($supportReady ?? true) ? '' : 'disabled' }}>Create support thread</button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Your threads</p>
            </div>
            <div id="tenant-support-thread-list" class="max-h-[36rem] overflow-y-auto p-4">
                @include('support.partials.tenant-thread-list', ['threads' => $threads, 'activeThread' => $activeThread])
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div id="tenant-support-conversation">
            @include('support.partials.tenant-conversation', ['activeThread' => $activeThread])
        </div>
        @if(($supportReady ?? true) && $activeThread)
            <div class="border-t border-slate-200 px-6 py-5">
                <form method="POST" action="{{ route('support.tenant.messages.store', $activeThread) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="reply_message" class="block text-sm font-medium text-slate-700">Reply</label>
                        <textarea id="reply_message" name="message" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Type your message to central support...">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Send message</button>
                </form>
            </div>
        @endif
    </section>
</div>

@if(($supportReady ?? true) && $activeThread)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const threadList = document.getElementById('tenant-support-thread-list');
        const conversation = document.getElementById('tenant-support-conversation');
        const unreadBadge = document.getElementById('tenant-support-unread-badge');
        const snapshotUrl = @json(route('support.tenant.snapshot', ['thread' => $activeThread->id]));

        if (!threadList || !conversation || !window.realtimeRefresh) {
            return;
        }

        window.realtimeRefresh.register('tenant-support-thread-list', snapshotUrl, (_element, data) => {
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
