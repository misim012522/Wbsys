@forelse($entries as $entry)
    <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between hover:shadow-md transition-shadow">
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
                {{ $entry->created_at->format('H:i') }} — 
                @if($entry->status === 'serving')
                    <span class="text-blue-600 font-medium">Now Serving</span>
                @elseif($entry->status === 'waiting')
                    <span class="text-amber-600 font-medium">Waiting</span>
                @else
                    <span class="text-slate-600">{{ ucfirst($entry->status) }}</span>
                @endif
            </p>
        </div>
        <div class="text-right">
            @if($entry->service_type)
                <p class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ $entry->service_type }}</p>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-8 text-slate-500">
        <p>No active queue entries</p>
    </div>
@endforelse
