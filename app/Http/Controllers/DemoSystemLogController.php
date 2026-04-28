<?php

namespace App\Http\Controllers;

use App\Models\DemoSystemLog;
use Illuminate\Http\Request;

class DemoSystemLogController extends Controller
{
    /**
     * Display a listing of the logs.
     */
    public function index()
    {
        $logs = DemoSystemLog::orderBy('created_at', 'desc')->get();
        return view('tenant.system-logs', [
            'logs' => $logs,
            'title' => 'System Logs Demo',
            'description' => 'This page shows simulated system events recorded in the tenant database.'
        ]);
    }

    /**
     * Store a newly created log in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event' => 'required|string|max:255',
        ]);

        DemoSystemLog::create([
            'event' => $request->event,
            'user_name' => auth()->user()->name ?? 'System',
        ]);

        return back()->with('success', 'Event logged successfully!');
    }
}
