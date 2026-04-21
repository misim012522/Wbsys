@extends('layouts.app')

@section('title', 'Manage Offices')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Offices</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white/50/30 text-sm">Dashboard</a>
        <a href="{{ route('admin.offices.create') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Add office</a>
    </div>
</div>

<div class="mb-4 panel p-4 text-sm text-slate-500 shadow-sm">
    Manage the service points used for queueing.
</div>

<div class="panel shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-white/50/30 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Office</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Status</th>
                <th class="text-right px-4 py-3 text-sm font-medium text-slate-700">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offices as $office)
                <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800">{{ $office->name }}</p>
                        <p class="text-sm text-slate-500">{{ $office->slug }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $office->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $office->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.offices.edit', $office) }}" class="text-emerald-600 hover:underline text-sm">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
