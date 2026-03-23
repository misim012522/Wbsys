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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function tenantHost(): array
{
    return ['HTTP_HOST' => 'acme.localhost'];
}

function provisionTenantWorkspace(Tenant $tenant): void
{
    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);
}

test('tenant home shows account-first onboarding for tenant end users', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    provisionTenantWorkspace($tenant);

    $this->withServerVariables(tenantHost())
        ->get('/tenant')
        ->assertOk()
        ->assertSee('Create account')
        ->assertSee('Log in')
        ->assertDontSee('Tenant offices')
        ->assertSee('workspace');
});

test('tenant end user can register and is created as pending student for the current tenant', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    provisionTenantWorkspace($tenant);

    $this->withServerVariables(tenantHost())
        ->post('/tenant/register', [
            'name' => 'End User',
            'username' => 'end.user',
            'email' => 'enduser@example.test',
            'phone' => '09123456789',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('registration.pending'));

    app(TenantDatabaseManager::class)->activate($tenant);

    $user = User::where('email', 'enduser@example.test')->first();

    expect($user)->not->toBeNull();
    expect($user->tenant_id)->toBe($tenant->id);
    expect($user->role)->toBe(User::ROLE_STUDENT);
    expect($user->approved_at)->toBeNull();
});

test('dashboard redirects approved student users to the tenant app dashboard', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    provisionTenantWorkspace($tenant);
    app(TenantDatabaseManager::class)->activate($tenant);

    $student = User::factory()->create([
        'role' => User::ROLE_STUDENT,
        'tenant_id' => $tenant->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($student)
        ->withServerVariables(tenantHost())
        ->get('/dashboard')
        ->assertRedirect(route('student.dashboard'));
});

test('tenant admin can approve a pending tenant end user', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
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

    app(TenantDatabaseManager::class)->activate($tenant);

    $student = User::factory()->create([
        'role' => User::ROLE_STUDENT,
        'tenant_id' => $tenant->id,
        'approved_at' => null,
        'email_verified_at' => null,
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->post(route('admin.users.approve', $student))
        ->assertRedirect();

    $student->refresh();

    expect($student->approved_at)->not->toBeNull();
    expect($student->email_verified_at)->not->toBeNull();
});

test('basic plan tenant cannot access reports', function () {
    $plan = Plan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'is_active' => true,
        'features' => ['queue', 'appointments', 'email_notifications'],
        'max_offices' => 1,
        'max_users_per_tenant' => 10,
    ]);

    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
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
        ->withServerVariables(tenantHost())
        ->get(route('admin.reports'))
        ->assertRedirect(route('admin.dashboard'));
});

test('tenant office management routes redirect back to the dashboard', function () {
    $plan = Plan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'is_active' => true,
        'features' => ['queue', 'appointments', 'email_notifications'],
        'max_offices' => 1,
        'max_users_per_tenant' => 10,
    ]);

    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
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
        ->withServerVariables(tenantHost())
        ->post(route('admin.offices.store'), [
            'name' => 'Cashier',
            'slug' => 'cashier',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('info');
});

test('tenant admin can change password from account settings', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
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
        ->withServerVariables(tenantHost())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Account settings')
        ->assertSee('Update password');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->put(route('admin.settings.update'), [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHas('success');

    $admin->refresh();

    expect(Hash::check('NewPassword123!', $admin->password))->toBeTrue();
});

test('tenant admin header hides central link and shows tenant administrator label', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar',
        'slug' => 'registrar',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'System Administrator',
        'username' => 'admin',
        'email' => 'admin@registrar.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(['HTTP_HOST' => 'registrar.localhost'])
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Registrar Administrator')
        ->assertDontSee('Central')
        ->assertDontSee('System Administrator');
});

test('basic plan tenant cannot register more users than allowed', function () {
    $plan = Plan::create([
        'name' => 'Basic',
        'slug' => 'basic',
        'is_active' => true,
        'features' => ['queue', 'appointments', 'email_notifications'],
        'max_offices' => 1,
        'max_users_per_tenant' => 1,
    ]);

    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    provisionTenantWorkspace($tenant);

    $this->withServerVariables(tenantHost())
        ->post('/tenant/register', [
            'name' => 'End User',
            'username' => 'end.user',
            'email' => 'enduser@example.test',
            'phone' => '09123456789',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertSessionHasErrors('email');
});
