@extends('layouts.app')

@section('title', 'System Activity Logs')

@section('content')
<div class="p-6">
    <div class="mb-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="bg-gradient-to-r from-slate-700 to-slate-900 p-8 text-white">
            <h1 class="text-3xl font-bold">System Activity Logs</h1>
            <p class="mt-2 text-slate-300 opacity-90">
                Track historical activity recorded within this tenant.
            </p>
        </div>
        
        <div class="p-8">
            <div class="grid gap-8 lg:grid-cols-4">
                <div class="lg:col-span-1">
                    <h2 class="text-xl font-semibold text-slate-800">Log New Event</h2>
                    
                    <form action="{{ route('tenant.system-logs.store') }}" method="POST" class="mt-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Event Description</label>
                                <input 
                                    type="text"
                                    name="event" 
                                    class="mt-2 block w-full rounded-xl border-0 py-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-slate-600 sm:text-sm"
                                    placeholder="What happened?"
                                    required
                                >
                            </div>
                        </div>

                        <button type="submit" class="mt-6 w-full rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                            Create Log Entry
                        </button>
                    </form>
                </div>
                
                <div class="lg:col-span-3">
                    <h2 class="text-xl font-semibold text-slate-800">Activity History</h2>
                    <div class="mt-6 flow-root">
                        <div class="inline-block min-w-full py-2 align-middle">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-slate-300">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Event</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Performed By</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Date/Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        @forelse($logs as $log)
                                            <tr>
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ $log->event }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                                        {{ $log->user_name }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-xs">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="py-10 text-center text-sm text-slate-400 italic">No activity logs found.</td>
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
    
    <div class="flex justify-center">
        <a href="{{ route('office.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
