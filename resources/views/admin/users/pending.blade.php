@extends('layouts.app')

@section('title', 'Pending office staff accounts')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Pending office staff accounts',
    'description' => 'Confirm newly registered office staff so they can sign in to the correct tenant workspace.',
    'actions' => [
        ['label' => 'All office staff', 'href' => route('admin.users.index')],
        ['label' => 'Archived staff', 'href' => route('admin.users.archived')],
    ],
])

@if(session('info'))
    <p class="mb-4 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm">{{ session('info') }}</p>
@endif
@error('user')
    <p class="mb-4 px-4 py-2 rounded-lg bg-red-100 text-red-800 text-sm">{{ $message }}</p>
@enderror

<div class="mb-6 rounded-[1.5rem] border border-slate-200 bg-gradient-to-br from-white to-amber-50/60 p-5 shadow-sm">
    <p class="text-slate-600">These office staff accounts are waiting for approval. When you confirm an account, the user will receive an email letting them know they can sign in to their office workspace.</p>
</div>

<form method="GET" action="{{ route('admin.users.pending') }}" class="mb-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
        <div class="flex-1">
            <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search pending staff</label>
            <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name, username, or email" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="w-full lg:w-56">
            <label for="office_id" class="mb-1 block text-sm font-medium text-slate-700">Office</label>
            <select name="office_id" id="office_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                <option value="0">All offices</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}" @selected($officeId === $office->id)>{{ $office->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Apply filters</button>
            @if($search !== '' || $officeId > 0)
                <a href="{{ route('admin.users.pending') }}" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
            @endif
        </div>
    </div>
</form>

<div class="mb-4 flex flex-col gap-2 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
    <p>
        Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} pending office staff accounts.
    </p>
    @if($search !== '' || $officeId > 0)
        <p>Filtered results for the current search.</p>
    @endif
</div>

<div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Name</th>
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Role</th>
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
                    <td class="px-4 py-3 text-slate-600">{{ $user->roleLabel() }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->username }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->office?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $user->created_at->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.users.approve', $user) }}" method="POST" class="inline" data-admin-action-form data-confirm-message="Confirm this office staff account and allow workspace access?">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700" data-idle-label="Confirm" data-loading-label="Confirming...">Confirm</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-slate-500 text-center">No pending office staff accounts. New registrations will appear here.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endif

@if(session('success'))
<div id="success-popup" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="success-popup-title">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 p-6 sm:p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 id="success-popup-title" class="text-xl font-bold text-slate-800 mb-2">Office staff account confirmed</h2>
        <p class="text-slate-600 mb-6">{{ session('success') }}</p>
        <button type="button" onclick="document.getElementById('success-popup').remove()" class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
            OK
        </button>
    </div>
</div>
<script>
(function() {
    document.querySelectorAll('[data-admin-action-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm-message');
            if (message && ! window.confirm(message)) {
                event.preventDefault();
                return;
            }

            var button = form.querySelector('button[type="submit"]');
            if (! button) {
                return;
            }

            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');

            var loadingLabel = button.getAttribute('data-loading-label');
            if (loadingLabel) {
                button.textContent = loadingLabel;
            }
        });
    });

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
