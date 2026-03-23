<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');
    return;
}

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Notifications\TenantCredentialsNotification;
use App\Services\TenantDatabaseManager;
use App\Support\TenantUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('central app landing redirects guests to login', function () {
    $this->get('/central')
        ->assertRedirect(route('login'));
});

test('central register page shows the tenant registration form', function () {
    $plan = Plan::firstOrCreate(['slug' => 'basic'], ['name' => 'Basic', 'price_monthly' => 19, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 29, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'ultimate'], ['name' => 'Ultimate', 'price_monthly' => 39, 'is_active' => true]);

    $this->get(route('central.register'))
        ->assertOk()
        ->assertSee('Register your office with a plan that fits how you work.')
        ->assertSee($plan->name)
        ->assertSee('Pro')
        ->assertSee('Ultimate')
        ->assertSee('$19')
        ->assertSee('$29')
        ->assertSee('$39');
});

test('central dashboard shows the tenant table', function () {
    Plan::firstOrCreate(['slug' => 'basic'], ['name' => 'Basic', 'price_monthly' => 19, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 29, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'ultimate'], ['name' => 'Ultimate', 'price_monthly' => 39, 'is_active' => true]);

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => Plan::where('slug', 'pro')->value('id'),
        'subdomain' => 'registrar',
        'database_name' => 'tenant_registrar_office',
        'address' => 'Main Campus, Building A',
        'email' => 'registrar@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    $this->actingAs($developer)
        ->get(route('central.dashboard'))
        ->assertOk()
        ->assertSee('Central dashboard')
        ->assertSee('Tenants')
        ->assertSee('Plans')
        ->assertSee('Subscriptions')
        ->assertSee('Tenant Name')
        ->assertSee('Tenant Domain')
        ->assertSee('Tenant credentials are sent by email during registration.')
        ->assertSee(\App\Support\TenantUrl::workspace($tenant), false)
        ->assertSee(\App\Support\TenantUrl::login($tenant), false)
        ->assertSee('registrar.lvh.me')
        ->assertSee('Central users can verify or open the tenant domain')
        ->assertDontSee('Register tenant');
});

test('tenant registration creates tenant database, tenant admin, and emails credentials', function () {
    Carbon::setTestNow('2026-03-19 10:15:00');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 29, 'is_active' => true]);
    Notification::fake();

    $databasePath = database_path('tenants/tenant_registrar_office.sqlite');
    if (File::exists($databasePath)) {
        File::delete($databasePath);
    }

    $this->post(route('central.register.store'), [
            'tenant_name' => 'Registrar Office',
            'plan_id' => $plan->id,
            'address' => 'Main Campus, Building A',
            'email' => 'registrar@example.test',
            'contact_number' => '09123456789',
        ])->assertRedirect(route('login'));

    $tenant = Tenant::where('name', 'Registrar Office')->first();

    expect($tenant)->not->toBeNull();
    expect($tenant->email)->toBe('registrar@example.test');
    expect($tenant->contact_number)->toBe('09123456789');
    expect($tenant->database_name)->toBe('tenant_registrar_office');
    expect($tenant->created_at?->format('Y-m-d H:i:s'))->toBe('2026-03-19 10:15:00');

    $subscription = TenantSubscription::where('tenant_id', $tenant->id)->first();

    expect($subscription)->not->toBeNull();
    expect($subscription->plan_id)->toBe($plan->id);
    expect($subscription->status)->toBe(TenantSubscription::STATUS_ACTIVE);
    expect($subscription->starts_at?->format('Y-m-d H:i:s'))->toBe('2026-03-19 10:15:00');

    app(TenantDatabaseManager::class)->activate($tenant);

    $admin = User::on('tenant')->where('tenant_id', $tenant->id)->first();

    expect($admin)->not->toBeNull();
    expect($admin->username)->toBe('admin');
    expect($admin->email)->toBe('registrar@example.test');

    Notification::assertSentTo($admin, TenantCredentialsNotification::class);

    Carbon::setTestNow();
});

test('different tenants generate different workspace hosts', function () {
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 29, 'is_active' => true]);

    $registrar = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_registrar_office',
        'is_active' => true,
    ]);

    $cashier = Tenant::create([
        'name' => 'Cashier Office',
        'slug' => 'cashier-office',
        'plan_id' => $plan->id,
        'subdomain' => 'cashier',
        'database_name' => 'tenant_cashier_office',
        'is_active' => true,
    ]);

    expect(TenantUrl::login($registrar))->toBe('http://registrar.localhost/login');
    expect(TenantUrl::login($cashier))->toBe('http://cashier.localhost/login');
    expect(TenantUrl::login($registrar))->not->toBe(TenantUrl::login($cashier));
});

test('central admin can delete a tenant from the dashboard route', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 29, 'is_active' => true]);

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_registrar_office',
        'is_active' => true,
    ]);

    $this->actingAs($developer)
        ->delete(route('central.tenants.destroy', $tenant))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    expect(Tenant::find($tenant->id))->toBeNull();
});
