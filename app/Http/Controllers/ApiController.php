<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Get queue count for an office
     */
    public function queueCount(Request $request, Office $office): JsonResponse
    {
        // Authorization check
        $this->authorize('view', $office);

        $count = QueueEntry::where('office_id', $office->id)
            ->where('status', 'active')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get today's appointments count
     */
    public function appointmentsToday(Request $request, Office $office): JsonResponse
    {
        // Authorization check
        $this->authorize('view', $office);

        $count = Appointment::where('office_id', $office->id)
            ->whereDate('appointment_date', today())
            ->where('status', '!=', 'cancelled')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get completed count for today
     */
    public function completedToday(Request $request, Office $office): JsonResponse
    {
        // Authorization check
        $this->authorize('view', $office);

        $count = QueueEntry::where('office_id', $office->id)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get queue list as HTML
     */
    public function queueList(Request $request, Office $office): JsonResponse
    {
        // Authorization check
        $this->authorize('view', $office);

        $entries = QueueEntry::where('office_id', $office->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get();

        $html = view('components.queue-list', ['entries' => $entries])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Get appointments list as HTML
     */
    public function appointmentsList(Request $request, Office $office): JsonResponse
    {
        // Authorization check
        $this->authorize('view', $office);

        $appointments = Appointment::where('office_id', $office->id)
            ->whereDate('appointment_date', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date', 'asc')
            ->take(10)
            ->get();

        $html = view('components.appointments-list', ['appointments' => $appointments])->render();

        return response()->json(['html' => $html]);
    }
}
