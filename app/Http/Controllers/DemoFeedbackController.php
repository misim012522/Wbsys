<?php

namespace App\Http\Controllers;

use App\Models\DemoFeedback;
use Illuminate\Http\Request;

class DemoFeedbackController extends Controller
{
    /**
     * Display a listing of the feedbacks.
     */
    public function index()
    {
        $feedbacks = DemoFeedback::orderBy('created_at', 'desc')->get();
        return view('tenant.feedback', [
            'feedbacks' => $feedbacks,
            'title' => 'System Feedback Demo',
            'description' => 'This is another feature installed via OTA update. It demonstrates that we can add complex features with ratings and lists on the fly.'
        ]);
    }

    /**
     * Store a newly created feedback in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        DemoFeedback::create([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Thank you for your feedback! Data saved successfully.');
    }
}
