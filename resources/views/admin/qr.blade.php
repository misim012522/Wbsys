@extends('layouts.app')

@section('title', 'QR Codes')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">QR codes for scanning</h1>
    <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Dashboard</a>
</div>

<p class="text-slate-600 mb-6">Display or print these QR codes at each office. End users scan to get a queue number or book an appointment (no login required).</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    @foreach($offices as $office)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 text-center">
            <h2 class="text-lg font-semibold text-slate-800 mb-2">{{ $office->name }}</h2>
            <div class="inline-block p-4 bg-white rounded-lg border-2 border-slate-200">
                <img src="{{ route('admin.qr.image', $office) }}" alt="QR code for {{ $office->name }}" class="w-64 h-64" width="256" height="256">
            </div>
            <p class="mt-3 text-sm text-slate-500 font-mono break-all">{{ route('queue.office', ['slug' => $office->slug]) }}</p>
            <a href="{{ route('admin.serve', $office) }}" class="inline-block mt-4 text-sm text-emerald-600 hover:underline">Serve this office →</a>
        </div>
    @endforeach
</div>

@if($offices->isEmpty())
    <p class="text-slate-500">No active offices. <a href="{{ route('admin.offices.create') }}" class="text-emerald-600 hover:underline">Add an office</a> first.</p>
@endif
@endsection
