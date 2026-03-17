@forelse($appointments as $appointment)
    <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between hover:shadow-md transition-shadow">
        <div class="flex-1">
            <p class="font-semibold text-slate-800">
                {{ $appointment->user ? $appointment->user->name : ($appointment->guest_name ?? 'Appointment') }}
            </p>
            <p class="text-sm text-slate-500">
                {{ $appointment->appointment_date->format('H:i') }}
                @if($appointment->purpose)
                    — {{ $appointment->purpose }}
                @endif
            </p>
            <p class="text-xs text-slate-400">
                @if($appointment->status === 'confirmed')
                    <span class="text-green-600 font-medium">✓ Confirmed</span>
                @elseif($appointment->status === 'pending')
                    <span class="text-amber-600 font-medium">⏳ Pending</span>
                @elseif($appointment->status === 'completed')
                    <span class="text-blue-600 font-medium">✓ Completed</span>
                @else
                    <span class="text-slate-600">{{ ucfirst($appointment->status) }}</span>
                @endif
            </p>
        </div>
        <div class="text-right">
            @if($appointment->service_type)
                <p class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ $appointment->service_type }}</p>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-8 text-slate-500">
        <p>No appointments scheduled for today</p>
    </div>
@endforelse
