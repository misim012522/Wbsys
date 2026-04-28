<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver-public-flow', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');

    return;
}

use App\Models\Office;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function publicFlowTenantHost(): array
{
    return ['HTTP_HOST' => 'acme.localhost'];
}

function provisionPublicFlowTenant(array $settings = []): Tenant
{
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);

    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
        'settings' => $settings,
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);
    app(TenantDatabaseManager::class)->activate($tenant);

    return $tenant;
}

test('public queue request is blocked when guest queue is disabled', function () {
    $tenant = provisionPublicFlowTenant([
        'customization' => [
            'guest_queue' => false,
        ],
    ]);

    $office = Office::query()->where('tenant_id', $tenant->id)->firstOrFail();

    $this->withServerVariables(publicFlowTenantHost())
        ->from(route('queue.office', ['slug' => $office->slug]))
        ->post(route('queue.get', ['slug' => $office->slug]), [
            'guest_name' => 'Queue Guest',
            'guest_email' => 'guest@example.test',
        ])
        ->assertRedirect(route('queue.office', ['slug' => $office->slug]))
        ->assertSessionHas('error', 'This office is not accepting queue numbers right now.');
});
