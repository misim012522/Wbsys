@extends('layouts.app')

@section('title', 'OTA Update Demo — Batch 2 Verification')

@section('content')
@php
    $tenantName = $tenant->name ?? 'This Tenant';
    $tenantDb   = $tenant->database_name ?? config('database.connections.tenant.database', '—');
@endphp

<div class="max-w-4xl mx-auto py-8 px-4 space-y-8">

    {{-- ── Header ───────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-100">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">OTA Update Demo <span class="text-indigo-600">Batch 2</span></h1>
                <p class="text-sm text-slate-500">Verifying multi-feature isolation for <strong>{{ $tenantName }}</strong></p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Database</p>
            <p class="font-mono text-sm text-slate-700 bg-slate-100 px-2 py-1 rounded">{{ $tenantDb }}</p>
        </div>
    </div>

    {{-- ── Flash messages ───────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">

        {{-- ── Feature 1: Notes ─────────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800">Feature 1: Notes</h2>
                @if($notesTableExists)
                    <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold uppercase">Online</span>
                @else
                    <span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold uppercase">Missing Update</span>
                @endif
            </div>

            @if($notesTableExists)
                <div class="panel p-4 bg-white/60">
                    <form method="POST" action="{{ route('ota.demo.store') }}" class="space-y-3">
                        @csrf
                        <input type="text" name="title" required maxlength="120" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500" placeholder="Note Title...">
                        <button type="submit" class="w-full bg-slate-800 text-white rounded-lg py-2 text-xs font-bold hover:bg-slate-900 transition-colors">Add Note</button>
                    </form>
                    <div class="mt-4 space-y-2 max-h-48 overflow-y-auto pr-1">
                        @forelse($notes as $note)
                            <div class="text-xs p-2 bg-slate-50 rounded border border-slate-100 flex justify-between items-center">
                                <span class="truncate pr-2">{{ $note->title }}</span>
                                <form method="POST" action="{{ route('ota.demo.destroy', $note->id) }}"> @csrf @method('DELETE') <button class="text-red-400">×</button> </form>
                            </div>
                        @empty
                            <p class="text-[11px] text-slate-400 italic text-center py-4">No notes in {{ $tenantDb }}</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="panel p-8 text-center bg-slate-50 border-dashed border-2 border-slate-200">
                    <p class="text-xs text-slate-500">Run the update to enable Notes.</p>
                </div>
            @endif
        </div>

        {{-- ── Feature 2: Announcements ────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800">Feature 2: Announcements</h2>
                @if($annTableExists)
                    <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold uppercase">Online</span>
                @else
                    <span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold uppercase">Missing Update</span>
                @endif
            </div>

            @if($annTableExists)
                <div class="panel p-4 bg-white/60">
                    <form method="POST" action="{{ route('ota.demo.announcement.store') }}" class="space-y-3">
                        @csrf
                        <input type="text" name="content" required maxlength="255" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500" placeholder="Announcement text...">
                        <select name="priority" class="w-full rounded-lg border-slate-200 text-sm">
                            <option value="low">Low Priority</option>
                            <option value="medium" selected>Medium Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                        <button type="submit" class="w-full bg-indigo-600 text-white rounded-lg py-2 text-xs font-bold hover:bg-indigo-700 transition-colors">Post Announcement</button>
                    </form>
                    <div class="mt-4 space-y-2 max-h-48 overflow-y-auto pr-1">
                        @forelse($announcements as $ann)
                            <div class="text-xs p-2 rounded border flex justify-between items-center {{ $ann->priority === 'high' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-indigo-50 border-indigo-100 text-indigo-700' }}">
                                <span class="truncate pr-2">{{ $ann->content }}</span>
                                <span class="text-[9px] uppercase font-bold opacity-60">{{ $ann->priority }}</span>
                            </div>
                        @empty
                            <p class="text-[11px] text-slate-400 italic text-center py-4">No announcements in {{ $tenantDb }}</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="panel p-8 text-center bg-slate-50 border-dashed border-2 border-slate-200">
                    <p class="text-xs text-slate-500">Run the update to enable Announcements.</p>
                </div>
            @endif
        </div>

    </div>

    {{-- ── Final Test Instructions ────────────────────────────────────── --}}
    <div class="rounded-2xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-200">
        <h3 class="font-bold flex items-center gap-2 mb-4">
            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 8.001 8.001 0 0118 0z"/></svg>
            Final OTA Multi-Feature Test
        </h3>
        <ul class="text-xs space-y-3 text-slate-300 list-disc list-inside px-2">
            <li>Push this code. If you see <span class="text-amber-400 font-bold">"Missing Update"</span> above, it means your database is still on the old version.</li>
            <li>Click <strong>"Apply Update"</strong> in your Dashboard.</li>
            <li>Both features should instantly turn <span class="text-emerald-400 font-bold">"Online"</span>.</li>
            <li>This confirms that multiple new tables are created and managed separately within each tenant's database.</li>
        </ul>
    </div>

</div>
@endsection
