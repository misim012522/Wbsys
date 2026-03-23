<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');
    return;
}

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Support\TenantUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function loginTenantHost(): array
{
    return ['HTTP_HOST' => 'acme.localhost'];
}

function otherTenantHost(): array
{
    return ['HTTP_HOST' => 'registrar.localhost'];
}

test('guest landing page redirects to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});

test('central handler account logs into the central dashboard', function () {
    $user = User::factory()->create([
        'username' => 'central.handler',
        'role' => User::ROLE_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $this->post('/login', [
        'login' => 'central.handler',
        'password' => 'password',
    ])->assertRedirect(route('central.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('central logout redirects back to login', function () {
    $user = User::factory()->create([
        'username' => 'sysadmin',
        'role' => User::ROLE_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('tenant account login from the root host hands off to the tenant domain', function () {
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $response = $this->post('/login', [
        'login' => 'tenant.admin',
        'password' => 'Password123!',
    ]);

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('http://acme.localhost/auth/continue?token=');
});

test('tenant login page does not show create account in the header', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $this->withServerVariables(loginTenantHost())
        ->get('/login')
        ->assertOk()
        ->assertSee('Log in')
        ->assertDontSee('Create account');
});

test('tenant register page does not show login and create account in the header', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $this->withServerVariables(loginTenantHost())
        ->get('/tenant/register')
        ->assertRedirect(route('login'));
});

test('tenant workspace cannot access central pages', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $this->withServerVariables(loginTenantHost())
        ->get('/central')
        ->assertRedirect(route('login'));

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(loginTenantHost())
        ->get('/central/dashboard')
        ->assertRedirect(TenantUrl::dashboard($tenant, $admin));
});

test('central user can open a tenant workspace page without being forced into tenant admin routes', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $centralUser = User::factory()->create([
        'username' => 'sysadmin',
        'role' => User::ROLE_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $this->actingAs($centralUser)
        ->withServerVariables(loginTenantHost())
        ->get('/tenant')
        ->assertOk()
        ->assertSee('Tenant Workspace')
        ->assertSee('Central account detected')
        ->assertSee('Log out and switch account');
});

test('tenant user on the central host is redirected back to their tenant workspace', function () {
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withHeader('Host', 'central.localhost')
        ->get('/dashboard')
        ->assertRedirect(TenantUrl::dashboard($tenant, $admin));
});

test('tenant user on another tenant domain is redirected back to their own workspace', function () {
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withHeader('Host', 'registrar.localhost')
        ->withServerVariables(otherTenantHost())
        ->get('/admin')
        ->assertRedirect(TenantUrl::dashboard($tenant, $admin));
});

test('tenant urls use localhost subdomains when app url is 127.0.0.1', function () {
    config()->set('app.url', 'http://127.0.0.1:8000');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    expect(TenantUrl::login($tenant))->toBe('http://registrar.lvh.me:8000/login');
});

test('tenant account cannot log in from another tenant domain', function () {
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);

    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $response = $this->withHeader('Host', 'registrar.localhost')
        ->withServerVariables(otherTenantHost())
        ->post('/login', [
            'login' => 'admin',
            'password' => 'Password123!',
            'tenant_id' => Tenant::where('subdomain', 'registrar')->value('id'),
        ]);

    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});

test('tenant login on a tenant domain lands on the shared tenant dashboard route', function () {
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Cot Office',
        'slug' => 'cot-office',
        'plan_id' => $plan->id,
        'subdomain' => 'cot',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Cot Admin',
        'username' => 'cot.admin',
        'email' => 'admin@cot.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $response = $this->withHeader('Host', 'cot.localhost')
        ->post('/login', [
            'login' => 'cot.admin',
            'password' => 'Password123!',
        ]);

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('http://cot.localhost/auth/continue?token=');
});
