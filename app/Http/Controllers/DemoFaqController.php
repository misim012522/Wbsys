<?php

namespace App\Http\Controllers;

use App\Models\DemoFaq;
use Illuminate\Http\Request;

class DemoFaqController extends Controller
{
    /**
     * Display a listing of the FAQs.
     */
    public function index()
    {
        $faqs = DemoFaq::orderBy('created_at', 'desc')->get();
        return view('tenant.faqs', [
            'faqs' => $faqs,
            'title' => 'FAQ System Demo',
            'description' => 'This demonstrates a Q&A style feature that can be added to your system during an update.'
        ]);
    }

    /**
     * Store a newly created FAQ in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        DemoFaq::create([
            'question' => $request->question,
            'answer' => $request->answer,
        ]);

        return back()->with('success', 'FAQ added successfully!');
    }
}
