@extends('layouts.app')

@section('title', 'Create your account')

@section('content')
<div class="auth-stage flex items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-sky-50">
    <div class="w-full max-w-[440px]">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/50 ring-1 ring-slate-100 sm:p-10">
            <div class="mb-6 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 rounded-lg">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <span class="text-xl font-bold tracking-tight">QueueLess</span>
                </a>
                <p class="mt-2 text-sm text-slate-500">Smart queue management</p>
            </div>

            <h1 class="text-2xl font-bold text-slate-800">Create your account</h1>
            <p class="mt-1 text-sm text-slate-500">
                For tenant staff.
                @if(isset($tenant) && $tenant)
                    This account will be created inside the <span class="font-medium text-slate-700">{{ $tenant->name }}</span> workspace.
                @else
                    Select your office to manage queues and QR codes.
                @endif
            </p>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Your name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="e.g. Maria Santos">
                    @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="e.g. maria.santos" autocomplete="username">
                    @error('username')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="you@company.com">
                    @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Contact number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="09XX XXX XXXX">
                    @error('phone')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Enter password">
                        @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Confirm password">
                    </div>
                </div>
                @if(isset($selectedOffice) && $selectedOffice)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Assigned office</label>
                        <input type="hidden" name="office_id" value="{{ $selectedOffice->id }}">
                        <div class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900">
                            {{ $selectedOffice->name }}
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">This is automatically assigned from the current workspace domain.</p>
                        @error('office_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div>
                        <label for="office_id" class="block text-sm font-medium text-slate-700 mb-1.5">Your office <span class="text-red-500">*</span></label>
                        <select name="office_id" id="office_id" required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">Select your office</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                            @endforeach
                        </select>
                        @if($offices->isEmpty())
                            <p class="mt-1.5 text-sm text-amber-600">No offices available yet. Ask your administrator to add offices first.</p>
                        @endif
                        @error('office_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                <button type="submit"
                    class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-700 hover:shadow-emerald-500/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:scale-[0.99]">
                    Create account
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account? <a href="{{ route('login') }}" class="font-medium text-emerald-600 hover:text-emerald-700 hover:underline">Log in</a>
            </p>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">QueueLess - Queue management</p>
    </div>
</div>
@endsection
