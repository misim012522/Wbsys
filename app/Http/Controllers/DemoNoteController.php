<?php

namespace App\Http\Controllers;

use App\Models\DemoNote;
use Illuminate\Http\Request;

class DemoNoteController extends Controller
{
    /**
     * Display a listing of the notes.
     */
    public function index()
    {
        $notes = DemoNote::orderBy('created_at', 'desc')->get();
        return view('tenant.notes', [
            'notes' => $notes,
            'title' => 'Update Success Demo',
            'description' => 'If you are seeing this page and the table below, it means the system successfully ran the migrations and installed the new controller/view during the update process.'
        ]);
    }

    /**
     * Store a newly created note in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:255',
        ]);

        DemoNote::create([
            'content' => $request->content,
        ]);

        return back()->with('success', 'Note added successfully! This proves the database table is working.');
    }
}
