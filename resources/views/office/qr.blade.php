@extends('layouts.app')

@section('title', 'Office QR access - ' . $office->name)

@section('content')
@php
    $queueUrl = route('queue.office', ['slug' => $office->slug]);
    $qrImageUrl = route('office.qr.image');
    $viewer = auth()->user();
    $canViewActivity = $viewer?->hasPermission('office.activity.view');
    $canOpenDashboard = $viewer?->hasPermission('office.dashboard');
@endphp
<div class="mb-8 overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fffc_42%,_#eef6ff_100%)] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Office QR access</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $office->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Display or print this QR access point at your office. End users scan it to get a queue number or book an appointment, then you handle the live flow from the office staff workspace.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($canViewActivity)
                    <a href="{{ route('office.activity') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Activity</a>
                @endif
                @if($canOpenDashboard)
                    <a href="{{ route('office.dashboard') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Live operations</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="inline-block rounded-[1.5rem] border-2 border-slate-200 bg-white p-4 shadow-inner">
            <img id="qr-image" src="{{ $qrImageUrl }}" alt="QR code for {{ $office->name }}" class="h-64 w-64" width="256" height="256">
        </div>
        <p class="mt-5 text-lg font-semibold text-slate-900">{{ $office->name }}</p>
        <p class="mt-2 break-all font-mono text-sm text-slate-500 select-all" title="Click to select">{{ $queueUrl }}</p>

        <div class="mt-6 space-y-3 border-t border-slate-200 pt-6">
            <p class="text-sm font-medium text-slate-700">Share office access</p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ $queueUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h6M5 5v14h14v-6" /></svg>
                    Open public page
                </a>
                <a href="{{ $qrImageUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5-5 5 5M12 5v12" /></svg>
                    Open QR image
                </a>
                <button type="button" id="copy-link-btn" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                    Copy link
                </button>
                <a href="{{ $qrImageUrl }}?download=1" download class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 no-underline transition hover:bg-slate-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download QR image
                </a>
                <button type="button" id="share-btn" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                    Share
                </button>
            </div>
            <p id="copy-feedback" class="hidden text-sm text-emerald-600">Link copied to clipboard.</p>
        </div>
    </div>

    <div class="space-y-5">
        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/80 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">QR workflow</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">How office staff uses this</h2>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">1. Display the QR</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Place the QR where visitors can easily scan it from the counter, entrance, or waiting area.</p>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">2. Let visitors join</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Visitors can open the public office page to get a queue number or create an appointment without signing in.</p>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">3. Handle the live flow</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Use the office dashboard right after that to call the next queue, process appointments, and monitor activity.</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/80 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Connected links</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Office QR tools</h2>
            </div>

            <div class="space-y-3 p-5 text-sm text-slate-600">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Public office page</p>
                    <p class="mt-2 break-all font-mono text-xs text-slate-700">{{ $queueUrl }}</p>
                </div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">QR image endpoint</p>
                    <p class="mt-2 break-all font-mono text-xs text-slate-700">{{ $qrImageUrl }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var queueUrl = @json($queueUrl);
    var officeName = @json($office->name);
    var copyBtn = document.getElementById('copy-link-btn');
    var shareBtn = document.getElementById('share-btn');
    var feedback = document.getElementById('copy-feedback');

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(queueUrl).then(function () {
                    feedback.classList.remove('hidden');
                    setTimeout(function () { feedback.classList.add('hidden'); }, 3000);
                }).catch(function () { fallbackCopy(); });
            } else {
                fallbackCopy();
            }
        });
    }

    function fallbackCopy() {
        var ta = document.createElement('textarea');
        ta.value = queueUrl;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            feedback.classList.remove('hidden');
            setTimeout(function () { feedback.classList.add('hidden'); }, 3000);
        } catch (e) {}
        document.body.removeChild(ta);
    }

    if (shareBtn && navigator.share) {
        shareBtn.addEventListener('click', function () {
            navigator.share({
                title: officeName + ' - Queue and Appointments',
                text: 'Get a queue number or book an appointment at ' + officeName + '. Scan the QR or open this link.',
                url: queueUrl
            }).catch(function (e) { if (e.name !== 'AbortError') console.error(e); });
        });
    } else if (shareBtn) {
        shareBtn.style.display = 'none';
    }
})();
</script>
@endsection
