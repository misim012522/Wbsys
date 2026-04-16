@forelse($threads as $thread)
    <a href="{{ route('support.tenant.index', ['thread' => $thread->id]) }}" class="mb-3 block rounded-[1.5rem] border px-4 py-3 transition {{ $activeThread?->id === $thread->id ? 'border-cyan-200 bg-cyan-50/80 shadow-sm' : 'border-slate-200 bg-white hover:border-cyan-100 hover:bg-slate-50' }}">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-sky-500 text-sm font-semibold text-white">
                C
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 pr-3">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $thread->subject }}</p>
                    @if($thread->isAnnouncement())
                        <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-700">Announcement</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $thread->isAnnouncement() ? 'Support announcement' : 'Support chat' }} - {{ $thread->created_at?->diffForHumans() }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($thread->hasUnreadForTenant())
                        <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-cyan-700">New</span>
                    @endif
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                        {{ str($thread->status)->replace('_', ' ') }}
                    </span>
                </div>
            </div>
        </div>
    </a>
@empty
    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
        No support threads yet.
    </div>
@endforelse
