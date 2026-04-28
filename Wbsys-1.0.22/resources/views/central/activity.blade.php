@extends('layouts.app')

@section('title', 'Central Activity Logs')

@section('content')
@include('central._workspace-nav')
        <h1 class="text-2xl font-bold text-slate-900">Central activity logs</h1>
        <p class="mt-2 text-sm text-slate-600">Central can view its own activities plus tenant admin and office staff activities from tenant workspaces.</p>

        <form method="GET" action="{{ route('central.activity') }}" class="mt-6 flex flex-wrap gap-4 items-end">
            <div>
                <label for="tenant_id" class="block text-sm font-medium text-slate-700 mb-1">Tenant</label>
                <select name="tenant_id" id="tenant_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">All tenants</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ $selectedTenantId === $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="action" class="block text-sm font-medium text-slate-700 mb-1">Action</label>
                <select name="action" id="action" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All actions</option>
                    @foreach($actionOptions as $action)
                        <option value="{{ $action }}" {{ $selectedAction === $action ? 'selected' : '' }}>{{ str_replace('_', ' ', $action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200">Filter</button>
            </div>
        </form>

        <div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @forelse($activities as $item)
                    <li class="px-4 py-3">
                        <p class="text-slate-800">{{ $item['description'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">
                            <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">{{ str_replace('_', ' ', $item['action']) }}</span>
                            · {{ $item['tenant_name'] ?? 'Central' }}
                            @if(!empty($item['office_name'])) · {{ $item['office_name'] }} @endif
                            · {{ $item['actor_name'] ?? 'System' }} ({{ str_replace('_', ' ', $item['actor_role'] ?? 'system') }})
                            · {{ optional($item['created_at'])->diffForHumans() }}
                        </p>
                    </li>
                @empty
                    <li class="px-4 py-12 text-center text-slate-500">No activity records found.</li>
                @endforelse
            </ul>
        </div>

        @if($activities->hasPages())
            <div class="mt-6">{{ $activities->links() }}</div>
        @endif
@include('central._workspace-nav-footer')
@endsection
