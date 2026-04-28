@extends('layouts.app')

@section('title', 'OTA Update Demo — ' . ($tenant->name ?? 'Tenant'))

@section('content')
@php
    $tenantName = $tenant->name ?? 'This Tenant';
    $tenantSlug = $tenant->slug ?? 'unknown';
    $tenantDb   = $tenant->database_name ?? config('database.connections.tenant.database', '—');
@endphp

<div class="max-w-3xl mx-auto py-8 px-4 space-y-6">

    {{-- ── Header ───────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100">
            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-900">OTA Update Demo</h1>
            <p class="text-sm text-slate-500">Testing migration isolation for <span class="font-semibold text-indigo-600">{{ $tenantName }}</span></p>
        </div>
    </div>

    {{-- ── Flash messages ───────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- ── Tenant Info Card ─────────────────────────────────────────────── --}}
    <div class="panel p-5 space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tenant Info</p>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-slate-500">Name</p>
                <p class="font-semibold text-slate-800">{{ $tenantName }}</p>
            </div>
            <div>
                <p class="text-slate-500">Slug</p>
                <p class="font-mono text-slate-800">{{ $tenantSlug }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-slate-500">Database</p>
                <p class="font-mono text-slate-800">{{ $tenantDb }}</p>
            </div>
        </div>
    </div>

    {{-- ── Migration Status ─────────────────────────────────────────────── --}}
    <div class="panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Migration Status</p>
        @if($tableExists)
            <div class="flex items-center gap-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3">
                <span class="text-2xl">✅</span>
                <div>
                    <p class="font-semibold text-emerald-800">Table <code class="font-mono">ota_test_notes</code> exists!</p>
                    <p class="text-sm text-emerald-600 mt-0.5">OTA migration ran successfully on <strong>{{ $tenantDb }}</strong> only.</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
                <span class="text-2xl">⏳</span>
                <div>
                    <p class="font-semibold text-amber-800">Table <code class="font-mono">ota_test_notes</code> does NOT exist yet.</p>
                    <p class="text-sm text-amber-600 mt-0.5">
                        Go to your <a href="{{ route('admin.dashboard') }}" class="underline font-medium">Admin Dashboard</a>
                        and click <strong>"Apply Update"</strong> to run the OTA migration on this tenant's database.
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- ── Add Note Form (only when table exists) ──────────────────────── --}}
    @if($tableExists)
    <div class="panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Add a Test Note</p>
        <form method="POST" action="{{ route('ota.demo.store') }}" class="space-y-3">
            @csrf
            <div>
                <label for="ota-title" class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                <input id="ota-title" type="text" name="title" required maxlength="120"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none"
                       placeholder="e.g. Hello from Tenant {{ $tenantSlug }}">
                @error('title')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="ota-body" class="block text-sm font-medium text-slate-700 mb-1">Body <span class="text-slate-400">(optional)</span></label>
                <textarea id="ota-body" name="body" rows="2" maxlength="500"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none resize-none"
                          placeholder="A note saved only on this tenant's database…"></textarea>
            </div>
            <button type="submit" id="ota-save-btn"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Save Note to {{ $tenantDb }}
            </button>
        </form>
    </div>

    {{-- ── Notes List ───────────────────────────────────────────────────── --}}
    <div class="panel p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Saved Notes ({{ $notes->count() }}) — in <span class="font-mono text-indigo-600">{{ $tenantDb }}</span>
            </p>
        </div>

        @forelse($notes as $note)
            <div class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 mb-2">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 truncate">{{ $note->title }}</p>
                    @if($note->body)
                        <p class="text-sm text-slate-600 mt-0.5">{{ $note->body }}</p>
                    @endif
                    <p class="text-xs text-slate-400 mt-1">
                        By {{ $note->created_by ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }}
                    </p>
                </div>
                <form method="POST" action="{{ route('ota.demo.destroy', $note->id) }}"
                      onsubmit="return confirm('Delete this note?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="text-red-400 hover:text-red-600 transition-colors text-xs font-medium shrink-0 mt-1">
                        Delete
                    </button>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-400 italic">No notes yet. Add one above to verify data isolation.</p>
        @endforelse
    </div>
    @endif

    {{-- ── How to Test Instructions ─────────────────────────────────────── --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-800 space-y-2">
        <p class="font-semibold text-blue-900">🧪 How to test tenant OTA isolation</p>
        <ol class="list-decimal list-inside space-y-1.5 text-blue-800">
            <li>Open this page — you should see <strong>"Table does NOT exist"</strong>.</li>
            <li>Go to <strong>Admin Dashboard → Apply Update</strong> for THIS tenant.</li>
            <li>Refresh this page — table should now exist, and the note form appears.</li>
            <li>Add a note. It is saved only in <code class="font-mono">{{ $tenantDb }}</code>.</li>
            <li>Log in as a <strong>different tenant</strong> and visit their <code>/ota-demo</code>.</li>
            <li>Before they apply their update, they still see <strong>"Table does NOT exist"</strong>.</li>
            <li>After they apply, their table is created in their own database — their notes are separate.</li>
        </ol>
    </div>

</div>
@endsection
