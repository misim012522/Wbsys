@extends('layouts.public')

@section('title', 'Tenant Registration')
@section('public_full_width', '1')

@php
    $planThemes = [
        'basic' => [
            'badge' => 'bg-rose-500',
            'accent' => 'text-rose-500',
            'surface' => 'bg-white',
            'border' => 'peer-checked:border-rose-300',
            'button' => 'bg-rose-500 text-white',
        ],
        'pro' => [
            'badge' => 'bg-emerald-500',
            'accent' => 'text-emerald-600',
            'surface' => 'bg-white',
            'border' => 'peer-checked:border-emerald-300',
            'button' => 'bg-emerald-600 text-white',
        ],
        'ultimate' => [
            'badge' => 'bg-sky-500',
            'accent' => 'text-sky-600',
            'surface' => 'bg-white',
            'border' => 'peer-checked:border-sky-300',
            'button' => 'bg-sky-600 text-white',
        ],
    ];
@endphp

@section('content')
<div class="min-h-screen bg-white px-4 py-12 sm:px-6">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 transition hover:text-slate-900">
                <span aria-hidden="true">&#8592;</span>
                <span>Back to login</span>
            </a>
            <div class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">
                Central Registration
            </div>
        </div>

        <form method="POST" action="{{ route('central.register.store') }}" class="grid gap-8 xl:grid-cols-[1.05fr_0.95fr]">
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm md:p-8">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-600">Pricing</p>
                    <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900">Register your office with a plan that fits how you work.</h1>
                    <p class="mt-4 text-base leading-7 text-slate-500">
                        Choose a subscription first, then enter the tenant details. The system will capture the registration date automatically, create the tenant workspace, and email the login domain plus admin credentials.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    @foreach($plans as $plan)
                        @php
                            $theme = $planThemes[$plan->slug] ?? $planThemes['pro'];
                            $features = collect($plan->features)->take(4);
                            $isSelected = (string) old('plan_id') === (string) $plan->id || (! old('plan_id') && $plan->slug === 'pro');
                        @endphp

                        <label class="group block cursor-pointer">
                            <input
                                type="radio"
                                name="plan_id"
                                value="{{ $plan->id }}"
                                class="peer sr-only"
                                @checked($isSelected)
                                required
                            >

                            <div class="flex h-full flex-col rounded-2xl border border-slate-200 {{ $theme['surface'] }} p-5 shadow-sm transition duration-200 group-hover:-translate-y-1 {{ $theme['border'] }} peer-checked:ring-2 peer-checked:ring-emerald-200">
                                <div class="rounded-2xl {{ $theme['badge'] }} px-5 pb-8 pt-5 text-white">
                                    <p class="text-3xl font-medium">{{ $plan->name }}</p>
                                </div>

                                <div class="flex flex-1 flex-col px-2 pb-2 pt-6">
                                    <div class="flex items-end gap-2">
                                        <span class="text-5xl font-extrabold {{ $theme['accent'] }}">${{ number_format((float) $plan->price_monthly, 0) }}</span>
                                        <span class="pb-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Per month</span>
                                    </div>

                                    <ul class="mt-7 space-y-3 text-sm text-slate-600">
                                        @foreach($features as $feature)
                                            <li class="flex items-center gap-3">
                                                <span class="text-emerald-500">&#10003;</span>
                                                <span>{{ str($feature)->replace('_', ' ')->title() }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-8">
                                        <div class="inline-flex min-w-[10rem] items-center justify-center rounded-xl px-5 py-3 text-sm font-bold uppercase tracking-[0.2em] shadow-sm {{ $theme['button'] }}">
                                            Select Plan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('plan_id')
                    <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600 md:grid-cols-3">
                    <div>
                        <p class="font-semibold text-slate-900">Automatic setup</p>
                        <p class="mt-1">The database and tenant login domain are generated after successful registration.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Credentials by email</p>
                        <p class="mt-1">The tenant receives the workspace domain, username, email, and temporary password.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Real-time timestamp</p>
                        <p class="mt-1">Registration time is captured by the system and shown in the central dashboard.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-emerald-600">Tenant Details</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Complete the registration</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        Fill in the office information. We will use this to create the tenant workspace and display the record in the central dashboard.
                    </p>
                </div>

                <div class="mt-8 space-y-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="tenant_name" class="mb-2 block text-sm font-medium text-slate-700">Tenant name</label>
                            <input type="text" name="tenant_name" id="tenant_name" value="{{ old('tenant_name') }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            @error('tenant_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="mb-2 block text-sm font-medium text-slate-700">Address</label>
                            <textarea name="address" id="address" rows="3" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="contact_number" class="mb-2 block text-sm font-medium text-slate-700">Contact number</label>
                            <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            @error('contact_number')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-800">What happens next</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li>1. A tenant database is created for this office.</li>
                            <li>2. An `admin` account is generated automatically.</li>
                            <li>3. The tenant receives the login domain and temporary password by email.</li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            Create tenant workspace
                        </button>
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-900">Cancel</a>
                    </div>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
