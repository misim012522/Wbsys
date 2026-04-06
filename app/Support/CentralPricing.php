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
                'features' => ['queue', 'appointments', 'email_notifications'],
                'max_offices' => 1,
                'max_users_per_tenant' => 10,
                'tagline' => 'Best for small offices',
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 20,
                'price_yearly' => 200,
                'features' => ['queue', 'appointments', 'email_notifications', 'reports', 'multi_office'],
                'max_offices' => 3,
                'max_users_per_tenant' => 30,
                'tagline' => 'Balanced for growing teams',
            ],
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'price_monthly' => 50,
                'price_yearly' => 500,
                'features' => ['queue', 'appointments', 'email_notifications', 'reports', 'multi_office', 'priority_support', 'advanced_analytics'],
                'max_offices' => null,
                'max_users_per_tenant' => null,
                'tagline' => 'Built for larger operations',
            ],
        ];
    }
}
