@extends('layouts.app')

@section('title', 'Pending accounts')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Pending accounts</h1>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Staff accounts</a>
        <a href="{{ route('admin.users.archived') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Archived</a>
    </div>
</div>

@if(session('info'))
    <p class="mb-4 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm">{{ session('info') }}</p>
@endif
@error('user')
    <p class="mb-4 px-4 py-2 rounded-lg bg-red-100 text-red-800 text-sm">{{ $message }}</p>
@enderror

<p class="text-slate-600 mb-6">These users have registered and are waiting for your approval. When you confirm an account, they will receive an email with a link to log in.</p>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Username</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Email</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Office</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Registered</th>
                <th class="text-right px-4 py-3 text-sm font-medium text-slate-700">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->username }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->office?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $user->created_at->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.users.approve', $user) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Confirm</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-slate-500 text-center">No pending accounts. New registrations will appear here.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(session('success'))
<div id="success-popup" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="success-popup-title">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 p-6 sm:p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 id="success-popup-title" class="text-xl font-bold text-slate-800 mb-2">Account confirmed</h2>
        <p class="text-slate-600 mb-6">{{ session('success') }}</p>
        <button type="button" onclick="document.getElementById('success-popup').remove()" class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
            OK
        </button>
    </div>
</div>
<script>
(function() {
    var popup = document.getElementById('success-popup');
    if (popup) {
        popup.addEventListener('click', function(e) {
            if (e.target === popup) popup.remove();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') popup.remove();
        });
    }
})();
</script>
@endif
@endsection
