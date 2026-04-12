@forelse($threads as $thread)
    <a href="{{ route('support.tenant.index', ['thread' => $thread->id]) }}" class="mb-3 block rounded-2xl border px-4 py-3 transition {{ $activeThread?->id === $thread->id ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $thread->subject }}</p>
                    @if($thread->isAnnouncement())
                        <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-700">Announcement</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">Opened {{ $thread->created_at?->diffForHumans() }}</p>
            </div>
            @if($thread->hasUnreadForTenant())
                <span class="rounded-full bg-sky-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">New</span>
            @endif
        </div>
    </a>
@empty
    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
        No support threads yet.
    </div>
@endforelse
