@if($activeThread)
    @php
        $activeStatusClasses = match($activeThread->status) {
            \App\Models\SupportThread::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-700',
            \App\Models\SupportThread::STATUS_IN_PROGRESS => 'bg-cyan-100 text-cyan-700',
            default => 'bg-amber-100 text-amber-700',
        };
    @endphp
    <div class="bg-gradient-to-r from-cyan-500 via-sky-500 to-teal-400 px-5 py-5 text-white sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full border border-white/50 bg-white/20 text-lg font-semibold">
                    {{ strtoupper(substr($activeThread->tenant?->name ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-50/80">Central support</p>
                        @if($activeThread->isAnnouncement())
                            <span class="rounded-full border border-white/30 bg-white/15 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-white">Announcement</span>
                        @endif
                    </div>
                    <h2 class="mt-1 text-lg font-semibold sm:text-xl">{{ $activeThread->subject }}</h2>
                    <p class="text-sm text-cyan-50/90">Direct conversation with the central team.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $activeStatusClasses }}">
                    {{ str($activeThread->status)->replace('_', ' ')->title() }}
                </span>
            </div>
        </div>
    </div>

    <div data-chat-scroll class="max-h-[38rem] overflow-y-auto bg-[linear-gradient(180deg,#f8fafc_0%,#ffffff_22%,#ecfeff_100%)] px-4 py-5 sm:px-6">
        <div class="mx-auto flex max-w-4xl flex-col gap-4">
            <div class="flex justify-center">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                    Started {{ $activeThread->created_at?->format('M d, Y') }}
                </span>
            </div>

            @foreach($activeThread->messages as $message)
                @php
                    $isTenantSender = $message->sender_type === \App\Models\SupportMessage::SENDER_TENANT;
                @endphp

                <div class="flex items-end gap-2 {{ $isTenantSender ? 'justify-end' : 'justify-start' }}">
                    @unless($isTenantSender)
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-semibold text-cyan-700">
                            C
                        </div>
                    @endunless

                    <div class="max-w-[86%]">
                        <div class="rounded-[1.65rem] px-4 py-3 shadow-sm {{ $isTenantSender ? 'rounded-br-md bg-cyan-500 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-900' }}">
                            <p class="whitespace-pre-line text-sm leading-7">{{ $message->message }}</p>
                        </div>

                        <div class="mt-1 flex flex-wrap items-center gap-2 px-1 text-[11px] {{ $isTenantSender ? 'justify-end text-slate-400' : 'justify-start text-slate-500' }}">
                            <span class="font-semibold">{{ $message->sender_name }}</span>
                            @if($message->sender_role)
                                <span>{{ $message->sender_role }}</span>
                            @endif
                            <span>{{ $message->created_at?->format('h:i A') }}</span>
                        </div>
                    </div>

                    @if($isTenantSender)
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                            You
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="flex min-h-[34rem] items-center justify-center bg-[radial-gradient(circle_at_top,#cffafe,transparent_38%),linear-gradient(180deg,#f8fafc_0%,#ffffff_100%)] px-6 py-10 text-center">
        <div>
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-cyan-100 text-xl font-semibold text-cyan-700">C</div>
            <h2 class="mt-5 text-xl font-bold text-slate-900">No active support thread</h2>
            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Create a new thread on the left to start chatting with central support.</p>
        </div>
    </div>
@endif
