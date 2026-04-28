@extends('layouts.app')

@section('title', 'System FAQs')

@section('content')
<div class="p-6">
    <div class="mb-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-8 text-white">
            <h1 class="text-3xl font-bold">Frequently Asked Questions</h1>
            <p class="mt-2 text-purple-50 opacity-90">
                A simple FAQ collection to test automated database migrations and UI deployment.
            </p>
        </div>
        
        <div class="p-8">
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <h2 class="text-xl font-semibold text-slate-800">Add New FAQ</h2>
                    
                    <form action="{{ route('tenant.faqs.store') }}" method="POST" class="mt-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Question</label>
                                <input 
                                    type="text"
                                    name="question" 
                                    class="mt-2 block w-full rounded-xl border-0 py-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-purple-600 sm:text-sm"
                                    placeholder="Enter question..."
                                    required
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Answer</label>
                                <textarea 
                                    name="answer" 
                                    rows="4" 
                                    class="mt-2 block w-full rounded-xl border-0 py-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-purple-600 sm:text-sm"
                                    placeholder="Enter answer..."
                                    required
                                ></textarea>
                            </div>
                        </div>

                        <button type="submit" class="mt-6 w-full rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-500">
                            Save FAQ
                        </button>
                    </form>
                </div>
                
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-semibold text-slate-800">Available FAQs</h2>
                    <div class="mt-6 space-y-6">
                        @forelse($faqs as $faq)
                            <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-100">
                                <h3 class="text-lg font-bold text-slate-900">Q: {{ $faq->question }}</h3>
                                <div class="mt-3 border-t border-slate-50 pt-3">
                                    <p class="text-slate-600 leading-relaxed">{{ $faq->answer }}</p>
                                </div>
                                <p class="mt-4 text-[10px] uppercase tracking-widest text-slate-400 font-semibold">Added {{ $faq->created_at->format('M d, Y') }}</p>
                            </div>
                        @empty
                            <div class="py-10 text-center text-sm text-slate-400 italic font-medium">No FAQs available yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-center">
        <a href="{{ route('office.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
