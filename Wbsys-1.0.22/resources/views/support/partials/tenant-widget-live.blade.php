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
                    class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $activeThread?->id === $thread->id ? 'tenant-primary border bg-white' : 'border-slate-200 bg-white text-slate-600 hover:text-slate-900' }}"
                    @if($activeThread?->id === $thread->id)
                        style="border-color: color-mix(in srgb, var(--tenant-primary) 35%, white); background: color-mix(in srgb, var(--tenant-primary) 10%, white);"
                    @endif
                >
                    {{ \Illuminate\Support\Str::limit($thread->subject, 18) }}
                </a>
            @empty
                <span class="text-xs text-slate-500">No threads yet</span>
            @endforelse
        </div>
    </div>

    @if($activeThread)
        <div data-widget-chat-scroll class="scroll-region min-h-0 flex-1 overflow-y-auto px-4 py-4" style="background: linear-gradient(180deg, #f8fafc 0%, #ffffff 35%, color-mix(in srgb, var(--tenant-primary) 12%, white) 100%);">
            <div class="space-y-3">
                @foreach($activeThread->messages as $message)
                    @php($isTenantSender = $message->sender_type === \App\Models\SupportMessage::SENDER_TENANT)
                    <div class="flex items-end gap-2 {{ $isTenantSender ? 'justify-end' : 'justify-start' }}">
                        @unless($isTenantSender)
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold tenant-primary" style="background: color-mix(in srgb, var(--tenant-primary) 18%, white);">C</div>
                        @endunless
                        <div class="max-w-[82%]">
                            <div class="rounded-[1.3rem] px-3.5 py-2.5 text-sm shadow-sm {{ $isTenantSender ? 'rounded-br-md text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-900' }}" @if($isTenantSender) style="background-color: var(--tenant-primary);" @endif>
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
            <form id="tenant-widget-reply-form" method="POST" action="{{ route('support.tenant.messages.store', $activeThread) }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-2">
                @csrf
                <input type="hidden" name="_support_widget" value="1">
                <textarea name="message" rows="2" class="min-w-0 w-full resize-none rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2" style="--tw-ring-color: color-mix(in srgb, var(--tenant-primary) 20%, transparent); border-color: color-mix(in srgb, var(--tenant-primary) 25%, #e2e8f0);" placeholder="Write a message to central..."></textarea>
                <button type="submit" class="tenant-primary-bg inline-flex h-11 shrink-0 items-center justify-center rounded-full px-4 text-sm font-semibold text-white transition">Send</button>
            </form>
        </div>
    @else
        <div class="px-4 py-4">
            <form id="tenant-widget-create-thread-form" method="POST" action="{{ route('support.tenant.threads.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="_support_widget" value="1">
                <input name="subject" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2" style="--tw-ring-color: color-mix(in srgb, var(--tenant-primary) 20%, transparent); border-color: color-mix(in srgb, var(--tenant-primary) 25%, #e2e8f0);" placeholder="Subject">
                <textarea name="message" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2" style="--tw-ring-color: color-mix(in srgb, var(--tenant-primary) 20%, transparent); border-color: color-mix(in srgb, var(--tenant-primary) 25%, #e2e8f0);" placeholder="Start your first message to central..."></textarea>
                <button type="submit" class="tenant-primary-bg w-full rounded-2xl px-4 py-3 text-sm font-semibold text-white transition">Start chat</button>
            </form>
        </div>
    @endif
@endif
