<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_unlimited_for_null_plan_limits()
    {
        $plan = [
            'name' => 'Test Plan',
            'slug' => 'test',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'features' => [],
            'max_offices' => null,
            'qr_codes_per_office' => null,
            'daily_service_limit' => null,
            'qr_description' => 'Unlimited QR codes',
            'daily_service_description' => 'No daily cap',
        ];

        $response = $this->view('central.pricing', ['plans' => [$plan], 'planCounts' => [], 'institutional' => collect()]);

        $response->assertSee('Max offices:');
        $response->assertSee('Unlimited');
        $response->assertSee('QR codes per office:');
        $response->assertSee('Unlimited QR codes');
        $response->assertSee('Daily service limit:');
        $response->assertSee('No daily cap');
    }
}
