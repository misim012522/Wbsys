@extends('layouts.app')

@section('title', 'Support and Updates')

@section('content')
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600">Support and updates</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Chat with central support</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
            Open a thread, send a message, and continue the conversation with the central team in one place.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span id="tenant-support-unread-badge" class="rounded-full bg-cyan-100 px-4 py-2 text-sm font-semibold text-cyan-700 {{ $unreadCount > 0 ? '' : 'hidden' }}">
            {{ $unreadCount }} unread
        </span>
        <a href="{{ route('dashboard') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to dashboard</a>
        <a href="{{ route('tenant.settings.edit') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Workspace settings</a>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
    @if(($supportReady ?? true) === false)
        <div class="xl:col-span-2 rounded-3xl border border-amber-200 bg-amber-50 px-6 py-5 text-sm text-amber-900">
            Support chat is not ready yet because the support tables are still missing in the central database. Run `php artisan migrate` first, then refresh this page.
        </div>
    @endif

    <section class="overflow-hidden rounded-[2rem] border border-cyan-100 bg-white shadow-[0_20px_60px_rgba(14,116,144,0.08)]">
        <div class="bg-gradient-to-r from-cyan-500 via-sky-500 to-teal-400 px-6 py-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-50/80">New conversation</p>
            <h2 class="mt-2 text-xl font-semibold">Message the central team</h2>
            <p class="mt-2 text-sm leading-6 text-cyan-50/90">
                Start a support thread for follow-ups, requests, or update questions.
            </p>
        </div>

        <div class="space-y-6 p-6">
            <form method="POST" action="{{ route('support.tenant.threads.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700">Subject</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-400/20" placeholder="Example: Need help with tenant setup">
                    @error('subject')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="message" class="block text-sm font-medium text-slate-700">First message</label>
                    <textarea id="message" name="message" rows="4" class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-400/20" placeholder="Describe your concern or the update you need.">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60" {{ ($supportReady ?? true) ? '' : 'disabled' }}>Create support thread</button>
            </form>

            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/80">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Your threads</p>
                </div>
                <div id="tenant-support-thread-list" class="max-h-[34rem] overflow-y-auto p-4">
                    @include('support.partials.tenant-thread-list', ['threads' => $threads, 'activeThread' => $activeThread])
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
        <div id="tenant-support-conversation">
            @include('support.partials.tenant-conversation', ['activeThread' => $activeThread])
        </div>
        @if(($supportReady ?? true) && $activeThread)
            <div class="border-t border-slate-200 bg-white px-4 py-4 sm:px-6">
                <form method="POST" action="{{ route('support.tenant.messages.store', $activeThread) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="reply_message" class="sr-only">Reply</label>
                        <textarea id="reply_message" name="message" rows="2" class="w-full resize-none rounded-[1.6rem] border border-slate-200 bg-slate-50 px-5 py-3 text-sm text-slate-900 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-400/20" placeholder="Write a message to central...">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="inline-flex h-12 shrink-0 items-center justify-center rounded-full bg-cyan-500 px-5 text-sm font-semibold text-white transition hover:bg-cyan-600">Send</button>
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
        const scrollMessagesToBottom = () => {
            const viewport = conversation.querySelector('[data-chat-scroll]');

            if (viewport) {
                viewport.scrollTop = viewport.scrollHeight;
            }
        };
        const snapshotUrl = @json(route('support.tenant.snapshot', ['thread' => $activeThread->id]));

        if (!threadList || !conversation || !window.realtimeRefresh) {
            return;
        }

        scrollMessagesToBottom();

        window.realtimeRefresh.register('tenant-support-thread-list', snapshotUrl, (_element, data) => {
            if (data.thread_list_html) {
                threadList.innerHTML = data.thread_list_html;
            }

            if (data.conversation_html) {
                conversation.innerHTML = data.conversation_html;
                scrollMessagesToBottom();
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
