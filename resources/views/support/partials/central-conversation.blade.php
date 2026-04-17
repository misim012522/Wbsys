@if($activeThread)
    @php
        $activeStatusClasses = match($activeThread->status) {
            \App\Models\SupportThread::STATUS_RESOLVED => 'bg-emerald-50 text-emerald-700',
            \App\Models\SupportThread::STATUS_IN_PROGRESS => 'bg-sky-50 text-sky-700',
            default => 'bg-amber-50 text-amber-700',
        };
    @endphp
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b border-slate-200 px-2 py-1.5">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-slate-900">{{ $activeThread->subject }}</h2>
                    <p class="text-xs text-slate-500">{{ $activeThread->tenant?->name ?? 'Unknown tenant' }}</p>
                </div>
                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-medium {{ $activeStatusClasses }}">
                    {{ str($activeThread->status)->replace('_', ' ')->title() }}
                </span>
            </div>
        </div>

        <div class="scroll-region h-[22rem] overflow-y-auto bg-slate-50 px-2 py-2">
            <div class="space-y-1.5">
                @foreach($activeThread->messages as $message)
                    @php
                        $isCentralSender = $message->sender_type === \App\Models\SupportMessage::SENDER_CENTRAL;
                    @endphp
                    <div class="flex {{ $isCentralSender ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[78%]">
                            @if(!$isCentralSender)
                                <p class="text-[10px] font-medium text-slate-600 mb-0.5">{{ $message->sender_name }}</p>
                            @endif
                            <div class="rounded-lg px-3 py-2 shadow-sm {{ $isCentralSender ? 'bg-blue-500 text-white' : 'bg-white border border-slate-200 text-slate-900' }}">
                                <p class="whitespace-pre-line text-xs leading-4">{{ $message->message }}</p>
                            </div>
                            <p class="text-[9px] {{ $isCentralSender ? 'text-right' : 'text-left' }} text-slate-400 mt-0.5">{{ $message->created_at?->format('h:i A') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="flex min-h-[12rem] items-center justify-center px-2 py-2 text-center">
        <div>
            <p class="text-xs text-slate-400">No conversation selected</p>
        </div>
    </div>
@endif
