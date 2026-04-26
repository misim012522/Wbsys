@extends('layouts.public')

@section('title', 'Pricing')
@section('public_full_width', '1')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 sm:py-12">
    <div class="mx-auto max-w-5xl">
        <h1 class="text-4xl font-bold">Pricing & Limits</h1>
        <p class="mt-3 text-slate-600">Transparent plan limits and features.</p>

        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach($plans as $plan)
                @php $count = $planCounts[$plan['slug']] ?? 0; @endphp
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold">{{ $plan['name'] }}</h2>
                    <p class="text-sm text-slate-500 mt-2">${{ number_format($plan['price_monthly'] ?? 0, 2) }}/month — ${{ number_format($plan['price_yearly'] ?? 0, 2) }}/year</p>
                    <p class="mt-2 text-sm text-slate-600"><strong>Tenants on this plan:</strong> {{ $count }}</p>
                    <ul class="mt-4 text-sm space-y-1 text-slate-700">
                        <li><strong>Max offices:</strong> {{ $plan['max_offices'] === null ? 'Unlimited' : $plan['max_offices'] }}</li>
                        
                        <li>
                            <strong>QR codes per office:</strong>
                            @php
                                $qr = $plan['qr_codes_per_office'] ?? ($plan->qr_codes_per_office ?? null);
                            @endphp
                            @if($qr === null || $qr === 'N/A')
                                Unlimited
                            @else
                                {{ $qr }}
                            @endif
                        </li>
                        @php $support = $plan['support_level'] ?? ($plan->support_level ?? null); $sla = $plan['sla_hours'] ?? ($plan->sla_hours ?? null); @endphp
                        @if($support)
                            <li><strong>Support:</strong> {{ ucfirst($support) }}{{ $sla ? ' — Response within '.$sla.' hours' : '' }}</li>
                        @endif
                        <li>
                            <strong>Daily service limit:</strong>
                            @php
                                $daily = $plan['daily_service_limit'] ?? ($plan->daily_service_limit ?? null);
                            @endphp
                            @if($daily === null || $daily === 'N/A')
                                Unlimited
                            @else
                                {{ $daily }}
                            @endif
                            {{-- descriptions removed as requested --}}
                        </li>
                        <li><strong>Features:</strong> {{ implode(', ', $plan['features'] ?? []) }}</li>
                    </ul>
                    {{-- Institutional licenses removed --}}
                </div>
            @endforeach
        </div>
        {{-- Institutional license list removed --}}
    </div>
</div>
@endsection
