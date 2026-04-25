<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\QueueEntry;
use App\Models\Tenant;
use App\Support\TenantUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    public function tenantSessionStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->tenant_id === null) {
            return response()->json(['active' => true]);
        }

        $tenant = Tenant::find($user->tenant_id);

        if (! $tenant || ! $tenant->is_active) {
            Auth::logout();
            $request->session()->forget('tenant_auth');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'active' => false,
                'deactivated' => true,
                'message' => 'Logging out due to deactivation.',
                'redirect_url' => TenantUrl::login(null, true),
            ], 423);
        }

        return response()->json([
            'active' => true,
            'tenant_id' => $tenant->id,
        ]);
    }

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

}
