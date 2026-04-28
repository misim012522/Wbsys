@forelse($entries as $entry)
    <div class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md sm:flex sm:items-center sm:justify-between">
        <div class="flex-1">
            <p class="font-semibold text-slate-800">Queue #{{ $entry->queue_number }}</p>
            <p class="text-sm text-slate-500">
                @if($entry->guest_name)
                    {{ $entry->guest_name }}
                @elseif($entry->user)
                    {{ $entry->user->name }}
                @else
                    Guest
                @endif
            </p>
            <p class="text-xs text-slate-400">
                {{ $entry->created_at->format('H:i') }} -
                @if($entry->status === 'serving')
                    <span class="text-blue-600 font-medium">Now Serving</span>
                @elseif($entry->status === 'waiting')
                    <span class="text-amber-600 font-medium">Waiting</span>
                @else
                    <span class="text-slate-600">{{ ucfirst($entry->status) }}</span>
                @endif
            </p>
        </div>
        <div class="mt-3 text-left sm:mt-0 sm:text-right">
            @if($entry->service_type)
                <p class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-500">{{ $entry->service_type }}</p>
            @endif
        </div>
    </div>
@empty
    <div class="py-8 text-center text-slate-500">
        <p>No active queue entries</p>
    </div>
@endforelse
