@forelse($threads as $thread)
    <a href="{{ route('central.support.index', ['thread' => $thread->id]) }}" class="mb-3 block rounded-2xl border px-4 py-3 transition {{ $activeThread?->id === $thread->id ? 'border-sky-200 bg-sky-50/70' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $thread->subject }}</p>
                    @if($thread->isAnnouncement())
                        <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-700">Announcement</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ $thread->tenant?->name ?? 'Unknown tenant' }}</p>
                <p class="mt-1 text-xs text-slate-400">Last message {{ optional($thread->last_message_at ?? $thread->created_at)->diffForHumans() }}</p>
            </div>
            <div class="flex flex-col items-end gap-2">
                @if($thread->hasUnreadForCentral())
                    <span class="rounded-full bg-sky-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">Unread</span>
                @endif
                <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ match($thread->status) {
                    \App\Models\SupportThread::STATUS_RESOLVED => 'bg-emerald-50 text-emerald-700',
                    \App\Models\SupportThread::STATUS_IN_PROGRESS => 'bg-sky-50 text-sky-700',
                    default => 'bg-amber-50 text-amber-700',
                } }}">
                    {{ str($thread->status)->replace('_', ' ')->title() }}
                </span>
            </div>
        </div>
    </a>
@empty
    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
        No tenant support threads yet.
    </div>
@endforelse
