@extends('layouts.app')

@section('title', 'Confirm approve user')

@section('content')
@include('admin._workspace-nav', [
    'title' => 'Confirm approval',
    'description' => 'Confirm this pending office staff account before granting workspace access.'
])

<div class="mx-auto max-w-xl">
    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Approve {{ $user->name }} ({{ $user->email }})</h2>
        <p class="mt-2 text-sm text-slate-600">Approving will allow this user to sign in to the workspace.</p>

        <form action="{{ route('admin.users.approve', $user) }}" method="POST" class="mt-4">
            @csrf
            <div class="flex gap-2">
                <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm text-white">Confirm approval</button>
                <a href="{{ route('admin.users.pending') }}" class="rounded-2xl border px-4 py-2 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
