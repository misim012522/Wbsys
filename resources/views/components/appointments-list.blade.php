@forelse($appointments as $appointment)
    <div class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md sm:flex sm:items-center sm:justify-between">
        <div class="flex-1">
            <p class="font-semibold text-slate-800">
                {{ $appointment->user ? $appointment->user->name : ($appointment->guest_name ?? 'Appointment') }}
            </p>
            <p class="text-sm text-slate-500">
                {{ $appointment->appointment_date->format('H:i') }}
                @if($appointment->purpose)
                    - {{ $appointment->purpose }}
                @endif
            </p>
            <p class="text-xs text-slate-400">
                @if($appointment->status === 'confirmed')
                    <span class="text-green-600 font-medium">Confirmed</span>
                @elseif($appointment->status === 'pending')
                    <span class="text-amber-600 font-medium">Pending</span>
                @elseif($appointment->status === 'completed')
                    <span class="text-blue-600 font-medium">Completed</span>
                @else
                    <span class="text-slate-600">{{ ucfirst($appointment->status) }}</span>
                @endif
            </p>
        </div>
        <div class="mt-3 text-left sm:mt-0 sm:text-right">
            @if($appointment->service_type)
                <p class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-500">{{ $appointment->service_type }}</p>
            @endif
        </div>
    </div>
@empty
    <div class="py-8 text-center text-slate-500">
        <p>No appointments scheduled for today</p>
    </div>
@endforelse
