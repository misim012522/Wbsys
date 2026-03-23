@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Account settings</h1>
        <p class="mt-2 text-sm text-slate-600">Update your admin password for this workspace.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                <input
                    type="password"
                    name="current_password"
                    id="current_password"
                    required
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                >
                @error('current_password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                >
                @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    required
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                >
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Back</a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Update password</button>
            </div>
        </form>
    </div>
</div>
@endsection
