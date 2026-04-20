@extends('layouts.public')

@section('title', 'Tenant Registration')
@section('public_full_width', '1')

@php
    $planThemes = [
        'basic' => [
            'accent' => 'text-rose-500',
            'border' => 'peer-checked:border-rose-300',
            'ring' => 'peer-checked:ring-rose-100',
            'soft' => 'bg-rose-50 text-rose-700',
            'dot' => 'bg-rose-500',
        ],
        'pro' => [
            'accent' => 'text-emerald-600',
            'border' => 'peer-checked:border-emerald-300',
            'ring' => 'peer-checked:ring-emerald-100',
            'soft' => 'bg-emerald-50 text-emerald-700',
            'dot' => 'bg-emerald-500',
        ],
        'ultimate' => [
            'accent' => 'text-sky-600',
            'border' => 'peer-checked:border-sky-300',
            'ring' => 'peer-checked:ring-sky-100',
            'soft' => 'bg-sky-50 text-sky-700',
            'dot' => 'bg-sky-500',
        ],
    ];
@endphp

@push('styles')
<style>
    @media (prefers-reduced-motion: no-preference) {
        .register-reveal {
            opacity: 0;
            transform: translateY(22px);
            animation: registerFadeUp 720ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: var(--reveal-delay, 0ms);
        }

        .register-reveal-soft {
            opacity: 0;
            transform: translateY(14px);
            animation: registerFadeUp 620ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: var(--reveal-delay, 0ms);
        }
    }

    @keyframes registerFadeUp {
        from {
            opacity: 0;
            transform: translateY(22px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 sm:py-12">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ \App\Support\TenantUrl::login(null, true) }}" class="register-reveal-soft inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm backdrop-blur transition hover:-translate-x-0.5 hover:text-slate-900" style="--reveal-delay: 40ms;">
                <span aria-hidden="true">&#8592;</span>
                <span>Back to login</span>
            </a>
            <div class="register-reveal-soft rounded-full border border-emerald-200/70 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700 shadow-sm backdrop-blur" style="--reveal-delay: 110ms;">
                Central Registration
            </div>
        </div>

        <form method="POST" action="{{ \App\Support\TenantUrl::centralRegister() }}" class="grid gap-8 xl:grid-cols-[1.05fr_0.95fr]">
            @csrf

            <section class="register-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm md:p-8" style="--reveal-delay: 140ms;">
                <div class="relative max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-emerald-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Pricing
                    </div>
                    <h1 class="mt-5 max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Register your office with a plan that fits how you work.</h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                        Pick a plan, enter the office details, and create the workspace.
                    </p>
                </div>

                <div class="relative mt-10 grid gap-4 lg:grid-cols-3">
                    @foreach($plans as $plan)
                        @php
                            $theme = $planThemes[$plan->slug] ?? $planThemes['pro'];
                            $features = collect($plan->features)->take(4);
                            $isSelected = (string) old('plan_id') === (string) $plan->id || (! old('plan_id') && $plan->slug === 'pro');
                            $planSummary = match ($plan->slug) {
                                'basic' => 'Essential queueing tools for smaller offices and straightforward daily operations.',
                                'ultimate' => 'The fullest setup for high-volume offices that need broader tenant capabilities.',
                                default => 'Balanced features and pricing for growing offices that need room to scale.',
                            };
                        @endphp

                        <label class="group register-reveal-soft block cursor-pointer" style="--reveal-delay: {{ 220 + ($loop->index * 90) }}ms;">
                            <input
                                type="radio"
                                name="plan_id"
                                value="{{ $plan->id }}"
                                class="peer sr-only"
                                @checked($isSelected)
                                required
                                data-plan-name="{{ $plan->name }}"
                                data-plan-price="${{ number_format((float) $plan->price_monthly, 0) }}"
                                data-plan-summary="{{ $planSummary }}"
                                data-plan-features="{{ $features->map(fn ($feature) => str($feature)->replace('_', ' ')->title())->implode(', ') }}"
                            >

                            <div class="relative flex h-full flex-col rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm transition duration-200 group-hover:-translate-y-1 group-hover:shadow-md {{ $theme['border'] }} {{ $theme['ring'] }} peer-checked:ring-4">
                                @if($plan->slug === 'pro')
                                    <div class="absolute right-4 top-4 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700">
                                        Popular
                                    </div>
                                @endif

                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Plan</p>
                                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $plan->name }}</p>
                                    </div>
                                    <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full {{ $theme['soft'] }}">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $theme['dot'] }}"></span>
                                    </span>
                                </div>

                                <div class="flex flex-1 flex-col pt-8">
                                    <div class="flex items-end gap-2">
                                        <span class="text-5xl font-extrabold {{ $theme['accent'] }}">${{ number_format((float) $plan->price_monthly, 0) }}</span>
                                        <span class="pb-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Per month</span>
                                    </div>

                                    <p class="mt-4 text-sm leading-6 text-slate-500">{{ $planSummary }}</p>

                                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                                        @foreach($features as $feature)
                                            <li class="flex items-center gap-3">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500">&#10003;</span>
                                                <span>{{ str($feature)->replace('_', ' ')->title() }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-8">
                                        <div class="inline-flex min-w-[10rem] items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition peer-checked:border-slate-900 peer-checked:bg-slate-900 peer-checked:text-white">
                                            {{ $isSelected ? 'Selected Plan' : 'Select Plan' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('plan_id')
                    <p class="relative mt-4 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section class="register-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm md:p-8" style="--reveal-delay: 260ms;">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                        Tenant Details
                    </div>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Complete the registration</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Office details only.</p>
                </div>

                <div class="mt-8 space-y-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="tenant_name" class="mb-2 block text-sm font-medium text-slate-700">Tenant name</label>
                            <input type="text" name="tenant_name" id="tenant_name" value="{{ old('tenant_name') }}" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('tenant_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="tenant_admin_username" class="mb-2 block text-sm font-medium text-slate-700">Admin username</label>
                            <input type="text" name="tenant_admin_username" id="tenant_admin_username" value="{{ old('tenant_admin_username') }}" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('tenant_admin_username')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="mb-2 block text-sm font-medium text-slate-700">Address</label>
                            <textarea name="address" id="address" rows="4" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="contact_number" class="mb-2 block text-sm font-medium text-slate-700">Contact number</label>
                            <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('contact_number')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div id="selected-plan-summary" class="register-reveal-soft rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5 shadow-sm" style="--reveal-delay: 380ms;">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Chosen Plan</p>
                                <h3 id="selected-plan-name" class="mt-2 text-2xl font-bold text-slate-900">Pro</h3>
                            </div>
                            <div class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                                <span id="selected-plan-price">$20</span> / month
                            </div>
                        </div>
                        <p id="selected-plan-summary-text" class="mt-4 text-sm leading-6 text-slate-600">
                            Balanced features and pricing for growing offices that need room to scale.
                        </p>
                        <p id="selected-plan-features" class="mt-4 text-sm text-slate-700">Queue, Email Notifications</p>
                    </div>

                    <div class="register-reveal-soft rounded-[1.5rem] border border-slate-200 bg-slate-900 px-5 py-5 text-white shadow-sm" style="--reveal-delay: 460ms;">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold">Ready to create the tenant workspace?</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-4">
                                <button type="submit" id="create-tenant-submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-6 py-3.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-slate-900" data-idle-label="Create tenant workspace" data-loading-label="Creating workspace...">
                                    Create tenant workspace
                                </button>
                                <a href="{{ \App\Support\TenantUrl::login(null, true) }}" id="create-tenant-cancel" class="text-sm font-medium text-slate-300 transition hover:text-white">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>
</div>

<script>
(function () {
    var planInputs = document.querySelectorAll('input[name="plan_id"]');
    var nameNode = document.getElementById('selected-plan-name');
    var priceNode = document.getElementById('selected-plan-price');
    var summaryNode = document.getElementById('selected-plan-summary-text');
    var featuresNode = document.getElementById('selected-plan-features');
    var form = document.querySelector('form[action="{{ \App\Support\TenantUrl::centralRegister() }}"]');
    var submitButton = document.getElementById('create-tenant-submit');
    var cancelLink = document.getElementById('create-tenant-cancel');

    function syncSelectedPlan() {
        var selected = document.querySelector('input[name="plan_id"]:checked');
        if (! selected || ! nameNode || ! priceNode || ! summaryNode || ! featuresNode) {
            return;
        }

        nameNode.textContent = selected.getAttribute('data-plan-name') || '';
        priceNode.textContent = selected.getAttribute('data-plan-price') || '';
        summaryNode.textContent = selected.getAttribute('data-plan-summary') || '';
        featuresNode.textContent = selected.getAttribute('data-plan-features') || '';
    }

    planInputs.forEach(function (input) {
        input.addEventListener('change', syncSelectedPlan);
    });

    syncSelectedPlan();

    if (form && submitButton) {
        form.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-70', 'cursor-not-allowed');
            submitButton.textContent = submitButton.getAttribute('data-loading-label') || 'Creating workspace...';

            if (cancelLink) {
                cancelLink.classList.add('pointer-events-none', 'opacity-50');
            }
        });
    }
})();
</script>
@endsection
