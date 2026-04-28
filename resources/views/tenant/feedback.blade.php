@extends('layouts.app')

@section('title', 'System Feedback')

@section('content')
<div class="p-6">
    <div class="mb-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-500 p-8 text-white">
            <h1 class="text-3xl font-bold">System Feedback</h1>
            <p class="mt-2 text-blue-50 opacity-90">
                Rate your experience with the new update system. Your feedback is stored directly in your tenant database.
            </p>
        </div>
        
        <div class="p-8">
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <h2 class="text-xl font-semibold text-slate-800">Submit Feedback</h2>
                    
                    <form action="{{ route('tenant.feedback.store') }}" method="POST" class="mt-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Rating</label>
                                <div class="mt-2 flex items-center gap-4">
                                    @foreach(range(1, 5) as $i)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating" value="{{ $i }}" class="peer hidden" required>
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-400 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white hover:border-blue-300">
                                                {{ $i }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Comments</label>
                                <textarea 
                                    name="comment" 
                                    rows="4" 
                                    class="mt-2 block w-full rounded-xl border-0 py-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm"
                                    placeholder="Tell us what you think..."
                                ></textarea>
                            </div>
                        </div>

                        <button type="submit" class="mt-6 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                            Submit Feedback
                        </button>
                    </form>
                </div>
                
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-semibold text-slate-800">Recent Feedback</h2>
                    <div class="mt-6 space-y-4">
                        @forelse($feedbacks as $item)
                            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex">
                                            @foreach(range(1, 5) as $star)
                                                <svg class="h-4 w-4 {{ $star <= $item->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endforeach
                                        </div>
                                        <span class="text-xs font-semibold text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $item->comment ?: 'No comment provided.' }}</p>
                            </div>
                        @empty
                            <div class="py-10 text-center text-sm text-slate-400 italic">No feedback received yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-center">
        <a href="{{ route('office.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 flex items-center gap-2">
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
