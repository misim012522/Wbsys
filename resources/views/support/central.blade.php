@extends('layouts.app')

@section('title', 'Central Support Inbox')

@section('content')
<div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Central support</p>
        <h1 class="mt-1 text-lg font-bold text-slate-900">Tenant support inbox</h1>
        <p class="mt-2 max-w-3xl text-xs leading-5 text-slate-600">
            Review support concerns and update questions sent by tenants. Reply from here so each tenant has a single conversation history.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span id="central-support-unread-badge" class="rounded-full bg-sky-100 px-3 py-1 text-[11px] font-semibold text-sky-700 {{ $unreadCount > 0 ? '' : 'hidden' }}">
            {{ $unreadCount }} unread
        </span>
        <a href="{{ route('central.dashboard') }}" class="rounded-full border border-slate-300 bg-white px-3 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50">Back to central dashboard</a>
    </div>
</div>

<div class="grid gap-3 xl:grid-cols-[0.95fr_1.4fr]">
    @if(($supportReady ?? true) === false)
        <div class="xl:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
            Support chat is not ready yet because the support tables are still missing in the central database. Run `php artisan migrate` first, then refresh this page.
        </div>
    @endif

    <section class="min-w-0">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-3 py-2">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">All tenant threads</p>
        </div>
        <div id="central-support-thread-list" class="scroll-region max-h-[38rem] overflow-y-auto p-2">
            @include('support.partials.central-thread-list', ['threads' => $threads, 'activeThread' => $activeThread])
        </div>
        </section>
    </section>

    <section class="min-w-0 flex min-h-[24rem] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:min-h-[32rem]">
        <div id="central-support-conversation" class="min-h-0 flex-1">
            @include('support.partials.central-conversation', ['activeThread' => $activeThread])
        </div>
        @if(($supportReady ?? true) && $activeThread)
            <div class="px-3 py-3">
                <form id="central-reply-form" method="POST" action="{{ route('central.support.messages.store', $activeThread) }}" class="space-y-2">
                    @csrf
                    <div>
                        <label for="central_reply_message" class="block text-xs font-medium text-slate-700">Reply to tenant</label>
                        <textarea id="central_reply_message" name="message" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 px-2 py-1 text-xs text-slate-900 focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20" placeholder="Type your reply or update for the tenant...">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="rounded-lg bg-sky-600 px-3 py-1 text-[11px] font-semibold text-white hover:bg-sky-700">Send reply</button>
                </form>
            </div>
        @endif
    </section>
</div>

@if(($supportReady ?? true) && $activeThread)
<script>
    // Save scroll position before any navigation
    window.addEventListener('beforeunload', () => {
        const conversation = document.getElementById('central-support-conversation');
        const scrollContainer = conversation?.querySelector('.scroll-region');
        if (scrollContainer) {
            sessionStorage.setItem('central-support-scroll-position', scrollContainer.scrollTop);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const threadList = document.getElementById('central-support-thread-list');
        const conversation = document.getElementById('central-support-conversation');
        const unreadBadge = document.getElementById('central-support-unread-badge');
        const replyForm = document.getElementById('central-reply-form');
        const messageInput = document.getElementById('central_reply_message');
        const snapshotUrl = @json(route('central.support.snapshot', ['thread' => $activeThread->id]));

        if (!threadList || !conversation || !window.realtimeRefresh) {
            return;
        }

        // Restore scroll position after page load
        const restoreScroll = () => {
            const savedScrollTop = sessionStorage.getItem('central-support-scroll-position');
            if (savedScrollTop !== null) {
                const scrollContainer = conversation.querySelector('.scroll-region');
                if (scrollContainer) {
                    const targetScroll = parseInt(savedScrollTop);
                    scrollContainer.scrollTop = targetScroll;
                    setTimeout(() => {
                        scrollContainer.scrollTop = targetScroll;
                    }, 100);
                }
                sessionStorage.removeItem('central-support-scroll-position');
            }
        };
        
        restoreScroll();

        // Handle form submission with AJAX to prevent page reload
        if (replyForm) {
            replyForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const scrollContainer = conversation.querySelector('.scroll-region');
                const currentScrollTop = scrollContainer?.scrollTop || 0;
                const message = messageInput.value.trim();
                
                if (!message) return;

                try {
                    const formData = new FormData(replyForm);
                    const response = await fetch(replyForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        messageInput.value = '';
                        // Keep scroll position steady
                        if (scrollContainer) {
                            scrollContainer.scrollTop = currentScrollTop;
                        }
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                }
            });
        }

        window.realtimeRefresh.register('central-support-thread-list', snapshotUrl, (_element, data) => {
            if (data.thread_list_html) {
                threadList.innerHTML = data.thread_list_html;
            }

            if (data.conversation_html) {
                const scrollContainer = conversation.querySelector('.scroll-region');
                const currentScrollTop = scrollContainer ? scrollContainer.scrollTop : 0;
                
                conversation.innerHTML = data.conversation_html;
                
                const newScrollContainer = conversation.querySelector('.scroll-region');
                if (newScrollContainer) {
                    newScrollContainer.scrollTop = currentScrollTop;
                }
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
