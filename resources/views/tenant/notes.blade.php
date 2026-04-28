@extends('layouts.app')

@section('title', 'Demo Notes')

@section('content')
<div class="p-6">
    <div class="mb-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="bg-gradient-to-r from-emerald-600 to-teal-500 p-8 text-white">
            <h1 class="text-3xl font-bold">Update Success Demo</h1>
            <p class="mt-2 text-emerald-50 opacity-90">
                Congratulations! The over-the-air update system worked perfectly. This screen, the controller logic, and the database table were all installed automatically.
            </p>
        </div>
        
        <div class="p-8">
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <h2 class="text-xl font-semibold text-slate-800">Add a Quick Note</h2>
                    <p class="mt-1 text-sm text-slate-500">Test the new migration by saving a row to the <code>demo_notes</code> table below.</p>
                    
                    <form action="{{ route('tenant.notes.store') }}" method="POST" class="mt-6">
                        @csrf
                        <div>
                            <textarea 
                                name="content" 
                                rows="3" 
                                class="block w-full rounded-xl border-0 py-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-sm sm:leading-6"
                                placeholder="Type something here..."
                                required
                            ></textarea>
                        </div>
                        <button type="submit" class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                            Save Note to Database
                        </button>
                    </form>
                </div>
                
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-semibold text-slate-800">Saved Notes</h2>
                    <div class="mt-6 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg border border-slate-100">
                                    <table class="min-w-full divide-y divide-slate-300 bg-white">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Note Content</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @forelse($notes as $note)
                                                <tr>
                                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-700 sm:pl-6 font-medium">{{ $note->content }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $note->created_at->format('M d, Y h:i A') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="py-10 text-center text-sm text-slate-400 italic">No notes found yet. Be the first to add one!</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-center">
        <a href="{{ route('office.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
