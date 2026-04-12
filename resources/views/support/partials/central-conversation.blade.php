@if($activeThread)
    @php
        $activeStatusClasses = match($activeThread->status) {
            \App\Models\SupportThread::STATUS_RESOLVED => 'bg-emerald-50 text-emerald-700',
            \App\Models\SupportThread::STATUS_IN_PROGRESS => 'bg-sky-50 text-sky-700',
            default => 'bg-amber-50 text-amber-700',
        };
    @endphp
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Active tenant conversation</p>
                        @if($activeThread->isAnnouncement())
                            <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-700">Announcement</span>
                        @endif
                    </div>
                    <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $activeThread->subject }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $activeThread->tenant?->name ?? 'Unknown tenant' }}</p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $activeStatusClasses }}">
                    {{ str($activeThread->status)->replace('_', ' ')->title() }}
                </span>
            </div>
        </div>

        <div class="h-[30rem] overflow-y-auto bg-slate-50/60 px-6 py-5">
            <div class="space-y-4">
                @foreach($activeThread->messages as $message)
                    @php
                        $isCentralSender = $message->sender_type === \App\Models\SupportMessage::SENDER_CENTRAL;
                    @endphp
                    <div class="flex {{ $isCentralSender ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-3xl px-4 py-3 shadow-sm {{ $isCentralSender ? 'bg-sky-600 text-white' : 'border border-slate-200 bg-white text-slate-900' }}">
                            <div class="flex flex-wrap items-center gap-2 text-xs {{ $isCentralSender ? 'text-sky-100' : 'text-slate-500' }}">
                                <span class="font-semibold">{{ $message->sender_name }}</span>
                                @if($message->sender_role)
                                    <span>{{ $message->sender_role }}</span>
                                @endif
                                <span>{{ $message->created_at?->format('M d, Y h:i A') }}</span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $message->message }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="flex min-h-[28rem] items-center justify-center px-6 py-10 text-center">
        <div>
            <h2 class="text-xl font-bold text-slate-900">No active tenant thread</h2>
            <p class="mt-2 text-sm text-slate-500">Tenant support conversations will appear here once a tenant starts a thread.</p>
        </div>
    </div>
@endif
