@extends('layouts.app')

@section('title', 'QR code — ' . $office->name)

@section('content')
@php
    $queueUrl = url()->route('queue.office', ['slug' => $office->slug]);
    $qrImageUrl = route('office.qr.image');
@endphp
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">QR code — {{ $office->name }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('office.activity') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Activity</a>
        <a href="{{ route('office.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">My queue</a>
    </div>
</div>

<p class="text-slate-600 mb-6">Display or print this QR code at your office. End users scan it to get a queue number or book an appointment (no login required).</p>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center max-w-sm mx-auto">
    <div class="inline-block p-4 bg-white rounded-lg border-2 border-slate-200">
        <img id="qr-image" src="{{ $qrImageUrl }}" alt="QR code for {{ $office->name }}" class="w-64 h-64" width="256" height="256">
    </div>
    <p class="mt-4 font-medium text-slate-800">{{ $office->name }}</p>
    <p class="mt-2 text-sm text-slate-500 font-mono break-all select-all" title="Click to select">{{ $queueUrl }}</p>

    <div class="mt-6 pt-6 border-t border-slate-200 space-y-3">
        <p class="text-sm font-medium text-slate-700">Share your QR</p>
        <div class="flex flex-wrap gap-2 justify-center">
            <button type="button" id="copy-link-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                Copy link
            </button>
            <a href="{{ $qrImageUrl }}?download=1" download class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-medium transition no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Download QR
            </a>
            <button type="button" id="share-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                Share
            </button>
        </div>
        <p id="copy-feedback" class="text-sm text-emerald-600 hidden">Link copied to clipboard.</p>
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
                title: officeName + ' — Queue & Appointments',
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
