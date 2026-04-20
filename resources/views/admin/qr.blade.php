@extends('layouts.app')

@section('title', 'QR Code')

@section('content')
@php
    $queueUrl = $office ? \App\Support\TenantUrl::forPath($office->tenant, route('queue.office', ['slug' => $office->slug], false)) : null;
@endphp
@include('admin._workspace-nav', [
    'title' => 'QR codes',
    'description' => 'Generate QR links per office staff. End users who scan will be assigned to that staff queue.',
    'actions' => [],
])

<div class="mb-6 rounded-[1.5rem] border border-slate-200 bg-gradient-to-br from-white to-emerald-50/50 p-5 shadow-sm">
    <p class="text-slate-600">Each card below is a complete package for one office staff member: QR image, signed queue URL target, and matching open action.</p>
    <p class="mt-2 text-sm text-slate-500">The displayed URL and QR image now use the same backend source so they always correspond.</p>
</div>

@if($office)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @forelse($staffQrCards as $card)
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">{{ $card['name'] }}</h2>
                <p class="text-sm text-slate-500">{{ $card['email'] }}</p>
                <div class="mt-4 text-center">
                    <div class="inline-block rounded-[1.25rem] border-2 border-slate-200 bg-white p-3 shadow-inner">
                        <img src="{{ $card['qr_image_url'] }}" alt="QR code for {{ $card['name'] }}" class="h-48 w-48" width="192" height="192">
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">QR target URL</p>
                    <button
                        type="button"
                        data-copy-url="{{ $card['queue_url'] }}"
                        class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Copy URL
                    </button>
                </div>
                <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="font-mono text-xs text-slate-600" title="{{ $card['queue_url'] }}">{{ \Illuminate\Support\Str::limit($card['queue_url'], 92) }}</p>
                </div>
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs font-medium text-slate-500 hover:text-slate-700">Show full URL</summary>
                    <p class="mt-2 break-all rounded-lg bg-slate-50 p-2 font-mono text-[11px] text-slate-500">{{ $card['queue_url'] }}</p>
                </details>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $card['queue_url'] }}" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Open staff queue page</a>
                    <a href="{{ $card['qr_image_url'] }}" target="_blank" rel="noreferrer" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Open QR image</a>
                </div>
            </div>
        @empty
            <div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 lg:col-span-2">
                No office staff found for this workspace. Approve or assign office staff first to generate staff-specific QR codes.
            </div>
        @endforelse
    </div>

    <div class="mt-6 rounded-[1.25rem] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Staff-specific QR URLs are signed. If a URL is modified, it becomes invalid automatically.
    </div>
@else
    <p class="text-slate-500">No active workspace is available yet.</p>
@endif

<script>
(() => {
    const buttons = document.querySelectorAll('[data-copy-url]');

    const copyText = async (text) => {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    };

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const original = button.textContent;
            try {
                await copyText(button.dataset.copyUrl || '');
                button.textContent = 'Copied';
            } catch (error) {
                button.textContent = 'Failed';
            }

            setTimeout(() => {
                button.textContent = original;
            }, 1200);
        });
    });
})();
</script>
@include('admin._workspace-nav-footer')
@endsection
