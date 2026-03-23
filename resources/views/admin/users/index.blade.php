@extends('layouts.app')

@section('title', 'User accounts')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">User accounts</h1>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Dashboard</a>
        <a href="{{ route('admin.users.pending') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Pending accounts</a>
        <a href="{{ route('admin.users.archived') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Archived</a>
    </div>
</div>

<p class="text-slate-600 mb-6">Approved tenant users can log in after confirmation. End users use the tenant app, while staff continue serving queues for their assigned office.</p>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Role</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Email</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Contact number</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Office</th>
                <th class="text-right px-4 py-3 text-sm font-medium text-slate-700">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ str_replace('_', ' ', $user->role) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->office?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.users.archive', $user) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-slate-500 text-center">No approved user accounts yet. <a href="{{ route('admin.users.pending') }}" class="text-emerald-600 hover:underline">View pending accounts</a></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
