@php
    $widgetReady = ($tenantSupportWidget['ready'] ?? false) === true;
    $widgetThreads = $tenantSupportWidget['threads'] ?? collect();
    $activeThread = $tenantSupportWidget['activeThread'] ?? null;
    $widgetUnreadCount = $tenantSupportWidget['unreadCount'] ?? 0;
    $widgetOpen = ($tenantSupportWidget['open'] ?? false) === true;
@endphp

@if($tenantSupportWidget['enabled'] ?? false)
    <div
        id="tenant-chat-widget"
        data-default-open="{{ $widgetOpen ? 'true' : 'false' }}"
        class="fixed bottom-5 right-5 z-50"
    >
        <div
            id="tenant-chat-panel"
            class="hidden absolute bottom-20 right-0 flex h-[min(42rem,calc(100vh-7.5rem))] w-[min(92vw,24rem)] flex-col overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.24)]"
        >
            <div class="bg-gradient-to-r from-cyan-500 via-sky-500 to-teal-400 px-5 py-4 text-white">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-50/80">Support chat</p>
                        <h2 class="mt-1 text-lg font-semibold">Message support</h2>
                        <p class="mt-1 text-xs leading-5 text-cyan-50/90">Quick support chat for your tenant workspace.</p>
                    </div>
                    <button
                        type="button"
                        id="tenant-chat-close"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/25 bg-white/10 text-sm font-semibold text-white transition hover:bg-white/20"
                        aria-label="Close chat"
                    >
                        X
                    </button>
                </div>
            </div>

            @if(! $widgetReady)
                <div class="px-5 py-6 text-sm leading-6 text-slate-600">
                    Support chat is not ready yet. Run the support migrations first, then refresh this page.
                </div>
            @else
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="scroll-region-x flex gap-2 overflow-x-auto pb-1">
                        @forelse($widgetThreads->take(6) as $thread)
                            <a
                                href="{{ request()->fullUrlWithQuery(['support_thread' => $thread->id, 'support_open' => 1]) }}"
                                class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $activeThread?->id === $thread->id ? 'border-cyan-200 bg-cyan-50 text-cyan-700' : 'border-slate-200 bg-white text-slate-600 hover:border-cyan-100 hover:text-slate-900' }}"
                            >
                                {{ \Illuminate\Support\Str::limit($thread->subject, 18) }}
                            </a>
                        @empty
                            <span class="text-xs text-slate-500">No threads yet</span>
                        @endforelse
                    </div>
                </div>

                @if($activeThread)
                    <div class="scroll-region min-h-0 flex-1 overflow-y-auto bg-[linear-gradient(180deg,#f8fafc_0%,#ffffff_35%,#ecfeff_100%)] px-4 py-4">
                        <div class="space-y-3">
                            @foreach($activeThread->messages as $message)
                                @php($isTenantSender = $message->sender_type === \App\Models\SupportMessage::SENDER_TENANT)
                                <div class="flex items-end gap-2 {{ $isTenantSender ? 'justify-end' : 'justify-start' }}">
                                    @unless($isTenantSender)
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-[11px] font-semibold text-cyan-700">C</div>
                                    @endunless
                                    <div class="max-w-[82%]">
                                        <div class="rounded-[1.3rem] px-3.5 py-2.5 text-sm shadow-sm {{ $isTenantSender ? 'rounded-br-md bg-cyan-500 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-900' }}">
                                            <p class="whitespace-pre-line leading-6">{{ $message->message }}</p>
                                        </div>
                                        <div class="mt-1 text-[11px] {{ $isTenantSender ? 'text-right text-slate-400' : 'text-slate-500' }}">
                                            {{ $message->created_at?->format('h:i A') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-white px-4 py-3">
                        <form method="POST" action="{{ route('support.tenant.messages.store', $activeThread) }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-2">
                            @csrf
                            <input type="hidden" name="_support_widget" value="1">
                            <textarea name="message" rows="2" class="min-w-0 w-full resize-none rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-400/20" placeholder="Write a message to central..."></textarea>
                            <button type="submit" class="inline-flex h-11 shrink-0 items-center justify-center rounded-full bg-cyan-500 px-4 text-sm font-semibold text-white transition hover:bg-cyan-600">Send</button>
                        </form>
                    </div>
                @else
                    <div class="px-4 py-4">
                        <form method="POST" action="{{ route('support.tenant.threads.store') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="_support_widget" value="1">
                            <input name="subject" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-400/20" placeholder="Subject">
                            <textarea name="message" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-400/20" placeholder="Start your first message to central..."></textarea>
                            <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Start chat</button>
                        </form>
                    </div>
                @endif
            @endif
        </div>

        <button
            type="button"
            id="tenant-chat-toggle"
            class="relative ml-auto inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 via-sky-500 to-teal-400 text-white shadow-[0_18px_40px_rgba(14,165,233,0.45)] transition hover:scale-[1.03] focus:outline-none focus:ring-4 focus:ring-cyan-300/50"
            aria-expanded="{{ $widgetOpen ? 'true' : 'false' }}"
            aria-controls="tenant-chat-panel"
            aria-label="Open support chat"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
            </svg>
            @if($widgetUnreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-h-6 min-w-6 items-center justify-center rounded-full border-2 border-white bg-slate-900 px-1.5 text-[11px] font-bold text-white">
                    {{ $widgetUnreadCount }}
                </span>
            @endif
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const widget = document.getElementById('tenant-chat-widget');
            const panel = document.getElementById('tenant-chat-panel');
            const toggle = document.getElementById('tenant-chat-toggle');
            const closeButton = document.getElementById('tenant-chat-close');

            if (!widget || !panel || !toggle) {
                return;
            }

            const setOpen = (open) => {
                panel.classList.toggle('hidden', !open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            setOpen(widget.dataset.defaultOpen === 'true');

            toggle.addEventListener('click', () => {
                setOpen(panel.classList.contains('hidden'));
            });

            closeButton?.addEventListener('click', () => setOpen(false));
        });
    </script>
@endif
