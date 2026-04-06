@extends('layouts.app')

@section('title', 'Archived office staff accounts')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Archived office staff accounts',
    'description' => 'Recover archived office staff accounts or permanently remove them from this tenant workspace.',
    'actions' => [
        ['label' => 'Office staff', 'href' => route('admin.users.index'), 'variant' => 'primary'],
    ],
])

@if(session('success'))
    <p class="mb-4 px-4 py-2 rounded-lg bg-emerald-100 text-emerald-800 text-sm">{{ session('success') }}</p>
@endif
@if(session('info'))
    <p class="mb-4 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm">{{ session('info') }}</p>
@endif
@error('user')
    <p class="mb-4 px-4 py-2 rounded-lg bg-red-100 text-red-800 text-sm">{{ $message }}</p>
@enderror

<div class="mb-6 rounded-[1.5rem] border border-slate-200 bg-gradient-to-br from-white to-rose-50/50 p-5 shadow-sm">
    <p class="text-slate-600">Archived office staff accounts cannot log in. Recover an account to restore workspace access, or delete permanently to remove it from the system.</p>
</div>

<form method="GET" action="{{ route('admin.users.archived') }}" class="mb-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
        <div class="flex-1">
            <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search archived staff</label>
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
                <a href="{{ route('admin.users.archived') }}" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
            @endif
        </div>
    </div>
</form>

<div class="mb-4 flex flex-col gap-2 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
    <p>
        Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} archived office staff accounts.
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
                <th class="text-left px-4 py-3 text-sm font-medium text-slate-700">Archived at</th>
                <th class="text-right px-4 py-3 text-sm font-medium text-slate-700">Actions</th>
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
                    <td class="px-4 py-3 text-slate-600 text-sm">{{ $user->archived_at?->format('M j, Y g:i A') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.users.recover', $user) }}" method="POST" class="inline" data-admin-action-form data-confirm-message="Recover this office staff account and restore workspace access?">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700" data-idle-label="Recover" data-loading-label="Recovering...">Recover</button>
                        </form>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline ml-2" data-admin-action-form data-confirm-message="Permanently delete this office staff account? This cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700" data-idle-label="Delete permanently" data-loading-label="Deleting...">Delete permanently</button>
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

@if($users->hasPages())
    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endif

<script>
(function () {
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
})();
</script>
@endsection
