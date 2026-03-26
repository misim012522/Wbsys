@extends('layouts.app')

@section('title', 'Office QR access - ' . $office->name)

@section('content')
@php
    $queueUrl = url()->route('queue.office', ['slug' => $office->slug]);
    $qrImageUrl = route('office.qr.image');
@endphp
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Office QR access - {{ $office->name }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('office.activity') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Activity</a>
        <a href="{{ route('office.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Live operations</a>
    </div>
</div>

<p class="mb-6 text-slate-600">Display or print this QR access point at your office. End users scan it to get a queue number or book an appointment, then you handle the live flow from the office staff workspace.</p>

<div class="mx-auto max-w-sm rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
    <div class="inline-block rounded-lg border-2 border-slate-200 bg-white p-4">
        <img id="qr-image" src="{{ $qrImageUrl }}" alt="QR code for {{ $office->name }}" class="h-64 w-64" width="256" height="256">
    </div>
    <p class="mt-4 font-medium text-slate-800">{{ $office->name }}</p>
    <p class="mt-2 break-all font-mono text-sm text-slate-500 select-all" title="Click to select">{{ $queueUrl }}</p>

    <div class="mt-6 space-y-3 border-t border-slate-200 pt-6">
        <p class="text-sm font-medium text-slate-700">Share office access</p>
        <div class="flex flex-wrap justify-center gap-2">
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
