@extends('layouts.app')

@section('title', 'Office staff accounts')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Office staff accounts',
    'description' => 'Review active office staff accounts that can already access the tenant workspace.',
    'actions' => [
        ['label' => 'Pending staff', 'href' => route('admin.users.pending'), 'variant' => 'primary'],
        ['label' => 'Archived staff', 'href' => route('admin.users.archived')],
    ],
])

<div class="mb-6 rounded-[1.5rem] border border-slate-200 bg-gradient-to-br from-white to-emerald-50/50 p-5 shadow-sm">
    <p class="text-slate-600">Approved office staff accounts can log in after confirmation and open their assigned office dashboard inside this tenant workspace.</p>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
        <div class="flex-1">
            <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search office staff</label>
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
                <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
            @endif
        </div>
    </div>
</form>

<div class="mb-4 flex flex-col gap-2 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
    <p>
        Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} approved office staff accounts.
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
                        <form action="{{ route('admin.users.archive', $user) }}" method="POST" class="inline" data-admin-action-form data-confirm-message="Archive this office staff account? They will lose workspace access until you recover them.">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50" data-idle-label="Archive" data-loading-label="Archiving...">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-slate-500 text-center">No approved office staff accounts yet. <a href="{{ route('admin.users.pending') }}" class="text-emerald-600 hover:underline">View pending staff accounts</a></td>
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
