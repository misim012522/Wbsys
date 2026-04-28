@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Activity logs',
    'description' => 'Review tenant admin and office staff activities for this tenant workspace.',
])

<form method="GET" action="{{ route('admin.activity') }}" class="flex flex-wrap gap-4 mb-6 items-end">
    <div>
        <label for="action" class="block text-sm font-medium text-slate-700 mb-1">Action</label>
        <select name="action" id="action" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 text-sm">
            <option value="">All actions</option>
            @foreach($actionOptions as $action)
                <option value="{{ $action }}" {{ $selectedAction === $action ? 'selected' : '' }}>{{ str_replace('_', ' ', $action) }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Actor role</label>
        <select name="role" id="role" class="rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 text-sm">
            <option value="">All</option>
            <option value="tenant_admin" {{ $selectedRole === 'tenant_admin' ? 'selected' : '' }}>Tenant Admin</option>
            <option value="office_staff" {{ $selectedRole === 'office_staff' ? 'selected' : '' }}>Office Staff</option>
        </select>
    </div>

    <div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200">Filter</button>
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="max-h-[600px] overflow-y-auto">
        <ul class="divide-y divide-slate-100">
            @forelse($activities as $log)
                <li class="px-4 py-3">
                    <p class="text-slate-800">{{ $log->description }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">{{ str_replace('_', ' ', $log->action) }}</span>
                        · {{ $log->user?->name ?? 'System' }}
                        · {{ str_replace('_', ' ', $log->user?->role ?? 'system') }}
                        @if($log->office)
                            · {{ $log->office->name }}
                        @endif
                        · {{ $log->created_at?->diffForHumans() }}
                    </p>
                </li>
            @empty
                <li class="px-4 py-12 text-center text-slate-500">No activity records found.</li>
            @endforelse
        </ul>
    </div>
</div>

@if($activities->hasPages())
    <div class="mt-6">{{ $activities->links() }}</div>
@endif

@include('admin._workspace-nav-footer')
@endsection
