@extends('layouts.app')

@section('title', 'Tenant Settings')

@section('content')
@php
    $workspaceName = $tenant?->name ?? 'Tenant workspace';
    $workspaceHost = $tenant ? parse_url(\App\Support\TenantUrl::workspace($tenant), PHP_URL_HOST) : null;
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Tenant-only page</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-900">Workspace settings</h1>
        <p class="mt-3 text-sm text-slate-600">
            Manage your account details inside the dedicated tenant workspace for {{ $workspaceName }}.
            @if($workspaceHost)
                This page is tied to <span class="font-semibold text-slate-800">{{ $workspaceHost }}</span>.
            @endif
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Profile details</h2>
            <p class="mt-2 text-sm text-slate-500">Update the name, email, phone, and password used for this tenant account.</p>

            <form method="POST" action="{{ route('tenant.settings.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        @error('phone')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Change password</h3>
                    <p class="mt-1 text-sm text-slate-500">Leave these blank if you only want to update profile details.</p>

                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                            <input type="password" name="current_password" id="current_password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            @error('current_password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                            <input type="password" name="password" id="password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Back to dashboard</a>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save workspace settings</button>
                </div>
            </form>
        </section>

        <aside class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 p-6 text-white shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Workspace info</p>
            <h2 class="mt-3 text-xl font-semibold">{{ $workspaceName }}</h2>
            <dl class="mt-5 space-y-3 text-sm text-slate-200">
                <div>
                    <dt class="text-slate-400">Tenant domain</dt>
                    <dd class="font-medium text-white">{{ $workspaceHost ?? 'Unavailable' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Role</dt>
                    <dd class="font-medium text-white">{{ str($user->role)->replace('_', ' ')->title() }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Username</dt>
                    <dd class="font-medium text-white">{{ $user->username }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</div>
@endsection
