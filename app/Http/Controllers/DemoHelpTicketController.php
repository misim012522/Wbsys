<?php

namespace App\Http\Controllers;

use App\Models\DemoHelpTicket;
use Illuminate\Http\Request;

class DemoHelpTicketController extends Controller
{
    public function index()
    {
        $tickets = DemoHelpTicket::latest()->get();
        return view('tenant.help-tickets', compact('tickets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        DemoHelpTicket::create([
            'subject' => $request->subject,
            'description' => $request->description,
            'user_name' => auth()->user()->name ?? 'Anonymous',
            'status' => 'open',
        ]);

        return back()->with('success', 'Ticket submitted successfully!');
    }
}
