@extends('layouts.app')

@section('title', 'QR Code')

@section('content')
@php
    $queueUrl = $office ? \App\Support\TenantUrl::forPath($office->tenant, route('queue.office', ['slug' => $office->slug], false)) : null;
@endphp
@include('admin._workspace-nav', [
    'title' => 'QR codes',
    'description' => 'Display or print the tenant QR entry point so end users can join the queue or book an appointment without signing in.',
    'actions' => [],
])

<div class="mb-6 rounded-[1.5rem] border border-slate-200 bg-gradient-to-br from-white to-emerald-50/50 p-5 shadow-sm">
    <p class="text-slate-600">Display or print this QR code for your workspace. End users can scan it to get a queue number or book an appointment without logging in.</p>
    <p class="mt-2 text-sm text-slate-500">Office staff should use this QR together with their office dashboard for live queue handling.</p>
</div>

@if($office)
    <div class="grid grid-cols-1 gap-8">
        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-8 text-center shadow-sm">
            <h2 class="mb-2 text-xl font-semibold text-slate-800">{{ $office->name }}</h2>
            <div class="inline-block rounded-[1.5rem] border-2 border-slate-200 bg-white p-4 shadow-inner">
                <img src="{{ route('admin.qr.image') }}" alt="QR code for {{ $office->name }}" class="w-64 h-64" width="256" height="256">
            </div>
            <p class="mt-4 text-sm font-mono text-slate-500 break-all">{{ $queueUrl }}</p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ $queueUrl }}" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Open public page</a>
                <a href="{{ route('admin.qr.image') }}" target="_blank" rel="noreferrer" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Open QR image</a>
            </div>
        </div>
    </div>
@else
    <p class="text-slate-500">No active workspace is available yet.</p>
@endif
@endsection
