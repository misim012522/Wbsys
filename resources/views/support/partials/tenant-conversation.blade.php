@if($activeThread)
    @php
        $activeStatusClasses = match($activeThread->status) {
            \App\Models\SupportThread::STATUS_RESOLVED => 'bg-emerald-50 text-emerald-700',
            \App\Models\SupportThread::STATUS_IN_PROGRESS => 'bg-sky-50 text-sky-700',
            default => 'bg-amber-50 text-amber-700',
        };
    @endphp
    <div class="border-b border-slate-200 px-6 py-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Active thread</p>
                    @if($activeThread->isAnnouncement())
                        <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-700">Announcement</span>
                    @endif
                </div>
                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $activeThread->subject }}</h2>
                <p class="mt-1 text-sm text-slate-500">Central team receives tenant messages here.</p>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $activeStatusClasses }}">
                {{ str($activeThread->status)->replace('_', ' ')->title() }}
            </span>
        </div>
    </div>

    <div class="max-h-[34rem] overflow-y-auto bg-slate-50/60 px-6 py-5">
        <div class="space-y-4">
            @foreach($activeThread->messages as $message)
                @php
                    $isTenantSender = $message->sender_type === \App\Models\SupportMessage::SENDER_TENANT;
                @endphp
                <div class="flex {{ $isTenantSender ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-3xl px-4 py-3 shadow-sm {{ $isTenantSender ? 'bg-emerald-600 text-white' : 'border border-slate-200 bg-white text-slate-900' }}">
                        <div class="flex flex-wrap items-center gap-2 text-xs {{ $isTenantSender ? 'text-emerald-100' : 'text-slate-500' }}">
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
@else
    <div class="flex min-h-[28rem] items-center justify-center px-6 py-10 text-center">
        <div>
            <h2 class="text-xl font-bold text-slate-900">No active support thread</h2>
            <p class="mt-2 text-sm text-slate-500">Create a new thread on the left to start chatting with central support.</p>
        </div>
    </div>
@endif
