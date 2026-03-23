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
use App\Support\TenantDashboardProfile;
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

test('tenant home shows tenant workspace onboarding for administrators and staff', function () {
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
        ->assertSee('Open workspace login')
        ->assertSee('Tenant admin pages')
        ->assertSee('Office staff dashboard')
        ->assertSee('Public external users')
        ->assertDontSee('Create account');
});

test('tenant register page redirects back to login with guidance', function () {
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
        ->get('/tenant/register')
        ->assertRedirect(route('login'));
});

test('tenant dashboard renders for approved office staff users on their tenant domain', function () {
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

    $staff = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => now(),
    ]);

    $this->actingAs($staff)
        ->withServerVariables(tenantHost())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Workspace dashboard')
        ->assertSee('Open office dashboard')
        ->assertSee('acme.lvh.me');
});

test('legacy student accounts can still open the tenant dashboard entry page', function () {
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

    $legacyUser = User::factory()->create([
        'role' => User::ROLE_STUDENT,
        'tenant_id' => $tenant->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($legacyUser)
        ->withServerVariables(tenantHost())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Workspace dashboard')
        ->assertSee('Workspace home');
});

test('tenant dashboard renders for tenant administrators on their tenant domain', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Cot Office',
        'slug' => 'cot-office',
        'plan_id' => $plan->id,
        'subdomain' => 'cot',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Cot Admin',
        'username' => 'cot.admin',
        'email' => 'admin@cot.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(['HTTP_HOST' => 'cot.localhost'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Workspace dashboard')
        ->assertSee('Open admin dashboard')
        ->assertSee('cot.lvh.me');
});

test('tenant dashboards reflect tenant-specific labels and enabled modules', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
        'settings' => [
            'theme' => [
                'app_name' => 'Registrar Portal',
            ],
            'dashboard' => [
                'profile' => 'registrar',
            ],
            'customization' => [
                'labels' => [
                    'queue' => 'Applications',
                    'office' => 'Registrar',
                    'appointment' => 'Reservations',
                ],
                'guest_queue' => false,
                'appointments' => true,
            ],
        ],
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Admin',
        'username' => 'registrar.admin',
        'email' => 'admin@registrar.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(['HTTP_HOST' => 'registrar.localhost'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Applications today')
        ->assertSee('Reservations today')
        ->assertSee('Registrar Portal')
        ->assertSee('Registrar dashboard')
        ->assertSee('Application review')
        ->assertDontSee('QR code');
});

test('tenant dashboard profiles can be inferred from tenant names', function () {
    expect(TenantDashboardProfile::inferFromName('Registrar Office'))->toBe('registrar');
    expect(TenantDashboardProfile::inferFromName('Cashier Window'))->toBe('cashier');
    expect(TenantDashboardProfile::inferFromName('Guidance Center'))->toBe('guidance');
    expect(TenantDashboardProfile::inferFromName('Clinic Services'))->toBe('clinic');
    expect(TenantDashboardProfile::inferFromName('General Office'))->toBe('general');
});

test('tenant admin can update the dashboard profile from customization', function () {
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
        ->get(route('admin.customization.index'))
        ->assertOk()
        ->assertSee('Dashboard Profile')
        ->assertSee('Registrar');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->put(route('admin.customization.update'), [
            'primary_color' => '#2563eb',
            'logo_url' => '',
            'support_url' => '',
            'app_name' => 'Acme Office',
            'guest_queue' => 1,
            'appointments' => 1,
            'show_service_type' => 1,
            'show_purpose_field' => 1,
            'dashboard_profile' => 'cashier',
            'label_queue' => 'Queue',
            'label_office' => 'Office',
            'label_appointment' => 'Appointment',
        ])
        ->assertRedirect(route('admin.customization.index'))
        ->assertSessionHas('success', 'Customization saved.');

    $tenant->refresh();

    expect($tenant->getSetting('dashboard.profile'))->toBe('cashier');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Cashier dashboard')
        ->assertSee('Counter throughput');
});

test('tenant admin can approve a pending office staff account', function () {
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

    $staff = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => null,
        'email_verified_at' => null,
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->post(route('admin.users.approve', $staff))
        ->assertRedirect()
        ->assertSessionHas('success', "Office staff account for {$staff->name} has been confirmed. A confirmation email has been sent to {$staff->email}.");

    $staff->refresh();

    expect($staff->approved_at)->not->toBeNull();
    expect($staff->email_verified_at)->not->toBeNull();
});

test('office staff archive, recover, and delete messages use office staff wording', function () {
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

    $staff = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->post(route('admin.users.archive', $staff))
        ->assertRedirect(route('admin.users.archived'))
        ->assertSessionHas('success', "Office staff account for {$staff->name} has been archived.");

    $staff->refresh();

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->post(route('admin.users.recover', $staff))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', "Office staff account for {$staff->name} has been recovered.");

    $staff->refresh();
    $staff->update(['archived_at' => now()]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->delete(route('admin.users.destroy', $staff))
        ->assertRedirect(route('admin.users.archived'))
        ->assertSessionHas('success', "Office staff account for {$staff->name} has been permanently deleted.");
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

test('admin screens use office staff wording for managed accounts', function () {
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
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Office staff accounts')
        ->assertSee('Approved office staff accounts');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.users.pending'))
        ->assertOk()
        ->assertSee('Pending office staff accounts')
        ->assertSee('office workspace');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.users.archived'))
        ->assertOk()
        ->assertSee('Archived office staff accounts')
        ->assertSee('workspace access');
});

test('tenant self-registration post is disabled', function () {
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
        ])
        ->assertRedirect(route('login'));
});
