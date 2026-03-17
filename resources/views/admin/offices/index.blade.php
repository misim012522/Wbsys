@extends('layouts.app')

@section('title', 'Manage Offices')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Manage Offices</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Dashboard</a>
        <a href="{{ route('admin.offices.create') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Add Office</a>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Slug</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Status</th>
                <th class="text-right px-4 py-3 text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offices as $office)
                <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $office->name }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $office->slug }}</td>
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
