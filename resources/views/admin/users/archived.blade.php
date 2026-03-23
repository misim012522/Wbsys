@extends('layouts.app')

@section('title', 'Archived office staff accounts')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Archived office staff accounts</h1>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Office staff accounts</a>
    </div>
</div>

@if(session('success'))
    <p class="mb-4 px-4 py-2 rounded-lg bg-emerald-100 text-emerald-800 text-sm">{{ session('success') }}</p>
@endif
@if(session('info'))
    <p class="mb-4 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm">{{ session('info') }}</p>
@endif
@error('user')
    <p class="mb-4 px-4 py-2 rounded-lg bg-red-100 text-red-800 text-sm">{{ $message }}</p>
@enderror

<p class="text-slate-600 mb-6">Archived office staff accounts cannot log in. Recover an account to restore workspace access, or delete permanently to remove it from the system.</p>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Role</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Username</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Email</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Office</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Archived at</th>
                <th class="text-right px-4 py-3 text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ str_replace('_', ' ', $user->role) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->username }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->office?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $user->archived_at?->format('M j, Y g:i A') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.users.recover', $user) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Recover</button>
                        </form>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Permanently delete this account? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Delete permanently</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-slate-500 text-center">No archived office staff accounts. <a href="{{ route('admin.users.index') }}" class="text-emerald-600 hover:underline">Back to office staff accounts</a></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
