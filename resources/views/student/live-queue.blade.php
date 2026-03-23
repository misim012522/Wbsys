@extends('layouts.app')

@section('title', 'Live Queue')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('student.offices') }}" class="text-sm text-slate-600 hover:text-slate-800">&lt; Back to offices</a>
    </div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ $office->name }} - Live Queue</h1>
    <p class="text-slate-600 mb-8">Current queue for today. Refresh to see updates.</p>

    @if($current)
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mb-6">
            <p class="text-sm text-emerald-700 font-medium">Now serving</p>
            <p class="text-4xl font-bold text-emerald-800">#{{ $current->queue_number }}</p>
        </div>
    @else
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 mb-6">
            <p class="text-slate-600">No one currently being served.</p>
        </div>
    @endif

    <h2 class="text-lg font-semibold text-slate-800 mb-3">Waiting in line</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @foreach($entries as $e)
                <li class="px-4 py-3 flex items-center justify-between">
                    <span class="font-semibold text-slate-800">#{{ $e->queue_number }}</span>
                    <span class="text-sm text-slate-500">{{ $e->status }}</span>
                </li>
            @endforeach
        </ul>
        @if($entries->isEmpty())
            <p class="px-4 py-8 text-slate-500 text-center">Queue is empty.</p>
        @endif
    </div>
</div>
@endsection
