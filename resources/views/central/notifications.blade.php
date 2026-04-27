@extends('layouts.app')

@section('title', 'Central Notifications')

@section('content')
@include('central._workspace-nav')
        <h1 class="text-2xl font-bold text-slate-900">Central notifications</h1>
        <p class="mt-2 text-sm text-slate-600">Combined support and system notifications for central operations.</p>

        <div class="mt-4">
            <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">{{ $unreadCount }} unread</span>
        </div>

        <div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($notifications as $item)
                    <li class="px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium text-slate-800">{{ $item['title'] }}</p>
                            @if($item['is_unread'])
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Unread</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ $item['message'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional($item['created_at'])->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-4 py-12 text-center text-slate-500">No notifications found.</li>
                @endforelse
            </ul>
        </div>

        @if($notifications->hasPages())
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
@include('central._workspace-nav-footer')
@endsection
