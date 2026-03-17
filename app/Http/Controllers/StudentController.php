<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\OfficeSchedule;
use App\Models\QueueEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $myQueues = $user->queueEntries()
            ->with('office')
            ->where('queue_date', '>=', today())
            ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->orderBy('queue_date')
            ->orderBy('queue_number')
            ->get();

        $myAppointments = $user->appointments()
            ->with('office')
            ->where('appointment_date', '>=', today())
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return view('student.dashboard', compact('myQueues', 'myAppointments'));
    }

    public function offices()
    {
        $offices = Office::where('is_active', true)->with('schedules')->get();
        return view('student.offices', compact('offices'));
    }

    public function getQueueNumber(Office $office)
    {
        if (! $office->is_active) {
            return back()->with('error', 'This office is not accepting queue numbers.');
        }

        $today = today();
        $count = $office->queueEntries()
            ->where('queue_date', $today)
            ->count();
        $nextNumber = $count + 1;

        if ($nextNumber > $office->max_daily_queue) {
            return back()->with('error', 'Daily queue limit reached for this office.');
        }

        $entry = QueueEntry::create([
            'office_id' => $office->id,
            'user_id' => auth()->id(),
            'queue_number' => $nextNumber,
            'queue_date' => $today,
            'status' => QueueEntry::STATUS_WAITING,
            'reference_code' => strtoupper(Str::random(8)),
        ]);

        return redirect()->route('student.queue-tracker', $entry->reference_code)
            ->with('success', "You are #{$nextNumber} in line.");
    }

    public function queueTracker(string $referenceCode)
    {
        $entry = QueueEntry::with('office')
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        if ($entry->user_id !== auth()->id()) {
            abort(403);
        }

        $position = $entry->office->queueEntries()
            ->where('queue_date', $entry->queue_date)
            ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->where('queue_number', '<=', $entry->queue_number)
            ->count();

        $ahead = $position - 1;

        return view('student.queue-tracker', compact('entry', 'position', 'ahead'));
    }

    public function bookAppointment(Request $request, Office $office)
    {
        if (! $office->is_active) {
            return back()->with('error', 'This office is not accepting appointments.');
        }

        $validated = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required'],
            'purpose' => ['nullable', 'string', 'max:500'],
        ]);

        $date = $validated['appointment_date'];
        $time = $validated['appointment_time'];

        $dayOfWeek = (int) date('w', strtotime($date));
        $schedule = OfficeSchedule::where('office_id', $office->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $schedule) {
            return back()->with('error', 'Office is closed on the selected date.');
        }

        $existing = Appointment::where('office_id', $office->id)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->exists();

        if ($existing) {
            return back()->with('error', 'That slot is already taken.');
        }

        $appointment = Appointment::create([
            'office_id' => $office->id,
            'user_id' => auth()->id(),
            'appointment_date' => $date,
            'appointment_time' => $time,
            'status' => Appointment::STATUS_PENDING,
            'purpose' => $validated['purpose'] ?? null,
            'reference_code' => strtoupper(Str::random(8)),
        ]);

        return redirect()->route('student.dashboard')->with('success', 'Appointment requested. You will be notified when confirmed.');
    }

    public function showBookAppointment(Office $office)
    {
        $office->load('schedules');
        return view('student.book-appointment', compact('office'));
    }

    public function liveQueue(Office $office)
    {
        $entries = $office->queueEntries()
            ->where('queue_date', today())
            ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])
            ->orderBy('queue_number')
            ->with('user:id,name')
            ->get();

        $current = $entries->whereIn('status', [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SERVING])->first();

        return view('student.live-queue', compact('office', 'entries', 'current'));
    }
}
