<?php

namespace App\Support;

class CentralPricing
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function plans(): array
    {
        return [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'features' => ['queue', 'email_notifications'],
                'max_offices' => 1,
                'qr_codes_per_office' => 10,
                'daily_service_limit' => 100,
                'qr_description' => 'Up to 10 QR codes per office for simple check-in flows.',
                'daily_service_description' => 'Suitable for low-volume offices (≈100 services/day).',
                'tagline' => 'Best for small offices',
                'support_level' => 'standard',
                'sla_hours' => 48,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 20,
                'price_yearly' => 200,
                'features' => ['queue', 'email_notifications', 'reports', 'multi_office'],
                'max_offices' => 3,
                'qr_codes_per_office' => 100,
                'daily_service_limit' => 1000,
                'qr_description' => 'Multiple QR codes per office for departments or counters.',
                'daily_service_description' => 'Handles medium traffic offices (≈1,000 services/day).',
                'tagline' => 'Balanced for growing teams',
                'support_level' => 'priority',
                'sla_hours' => 24,
            ],
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'price_monthly' => 50,
                'price_yearly' => 500,
                'features' => ['queue', 'email_notifications', 'reports', 'multi_office', 'priority_support', 'advanced_analytics'],
                'max_offices' => null,
                'qr_codes_per_office' => null,
                'daily_service_limit' => null,
                'qr_description' => 'Unlimited QR codes to support any number of counters or campaigns.',
                'daily_service_description' => 'Designed for high-volume operations with no artificial daily cap.',
                'tagline' => 'Built for larger operations',
                'support_level' => 'enterprise',
                'sla_hours' => 4,
            ],
        ];
    }
}
