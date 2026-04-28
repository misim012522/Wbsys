<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');

    return;
}

use App\Models\Plan;
use App\Models\AppVersion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Support\ReservedUsernames;
use App\Support\TenantDashboardProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
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

test('tenant dashboard shows update banner when a newer GitHub release exists', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'app_version' => 'v1.0.0',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    AppVersion::query()->create([
        'version' => '1.0.1',
        'release_notes' => 'New release available',
        'released_at' => now(),
        'is_forced' => false,
        'download_url' => 'https://example.com/releases/v1.0.1.zip',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('System update available')
        ->assertSee('New version 1.0.1 is available')
        ->assertSee('https://example.com/releases/v1.0.1.zip');
});

test('tenant update status endpoint returns the latest release metadata when an update is available', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'app_version' => 'v1.0.0',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    AppVersion::query()->create([
        'version' => '1.0.2',
        'release_notes' => 'Second release available',
        'released_at' => now(),
        'is_forced' => false,
        'download_url' => 'https://example.com/releases/v1.0.2.zip',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->getJson(route('tenant.update.status'))
        ->assertOk()
        ->assertJson([
            'latest_version' => '1.0.2',
            'current_version' => '1.0.0',
            'update_available' => true,
            'download_url' => 'https://example.com/releases/v1.0.2.zip',
            'is_forced' => false,
            'release_notes' => 'Second release available',
        ]);
});

test('tenant update status endpoint reports no update when the current version matches the latest release', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'app_version' => 'v1.0.2',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    AppVersion::query()->create([
        'version' => '1.0.2',
        'release_notes' => 'Current release',
        'released_at' => now(),
        'is_forced' => false,
        'download_url' => 'https://example.com/releases/v1.0.2.zip',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->getJson(route('tenant.update.status'))
        ->assertOk()
        ->assertJson([
            'latest_version' => '1.0.2',
            'current_version' => '1.0.2',
            'update_available' => false,
            'download_url' => 'https://example.com/releases/v1.0.2.zip',
            'is_forced' => false,
            'release_notes' => 'Current release',
        ]);
});

test('tenant admin can apply the latest release update', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'app_version' => 'v1.0.0',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Admin',
        'username' => 'tenant.admin',
        'email' => 'admin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    AppVersion::query()->create([
        'version' => '1.0.3',
        'release_notes' => 'Apply flow release',
        'released_at' => now(),
        'is_forced' => false,
        'download_url' => 'https://example.com/releases/v1.0.3.zip',
    ]);

    Artisan::shouldReceive('call')
        ->once()
        ->with('app:update', ['--version' => '1.0.3'])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn("Update applied successfully.\n");

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->postJson(route('tenant.update.apply'), ['version' => '1.0.3'])
        ->assertOk()
        ->assertJson([
            'message' => 'Update applied successfully.',
            'version' => '1.0.3',
            'output' => 'Update applied successfully.',
        ]);

    expect($tenant->fresh()->app_version)->toBe('v1.0.3');
});

test('tenant workspace login redirects authenticated staff to their dashboard', function () {
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
        ->get('/login')
        ->assertRedirect(route($staff->dashboardRouteName()));
});

test('opening tenant login in a new tab does not log out the current workspace session', function () {
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
        ->get('/login')
        ->assertRedirect(route($admin->dashboardRouteName()));

    $this->withServerVariables(tenantHost())
        ->get('/admin')
        ->assertOk();
});

test('tenant admin can still open admin routes after visiting the tenant login page', function () {
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
        ->get('/login')
        ->assertRedirect(route($admin->dashboardRouteName()));

    $this->withServerVariables(tenantHost())
        ->get(route('admin.dashboard'))
        ->assertOk();

    $this->withServerVariables(tenantHost())
        ->get(route('admin.settings.edit'))
        ->assertOk();
});

test('tenant login route redirects authenticated admin and preserves session UI on admin pages', function () {
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
        ->get('/login')
        ->assertRedirect(route($admin->dashboardRouteName()));

    $this->withServerVariables(tenantHost())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-tenant-session-monitor-url', false);
});

test('tenant admin header navigation still works after another tab opens tenant login', function () {
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
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin settings');

    $this->withServerVariables(tenantHost())
        ->get('/login')
        ->assertRedirect(route($admin->dashboardRouteName()));

    $this->withServerVariables(tenantHost())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Admin settings')
        ->assertSee('Change password');

    $this->withServerVariables(tenantHost())
        ->get(route('admin.profile'))
        ->assertRedirect(route('admin.settings.edit'));
});

test('tenant home redirects authenticated workspace users back to the login page', function () {
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
        ->get('/tenant')
        ->assertRedirect(route('login'));
});

test('tenant workspace root redirects authenticated tenant admins to the login page', function () {
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
        ->get('/')
        ->assertRedirect(\App\Support\TenantUrl::login($tenant));
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
        ->assertRedirect(route('office.dashboard'));
});

test('office staff dashboard shows QR code access even when guest queue is disabled', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
        'settings' => [
            'theme' => [
                'guest_queue_enabled' => false,
            ],
        ],
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
        ->get(route('office.dashboard'))
        ->assertOk()
        ->assertSee('QR code');
});

test('office staff qr page includes the full office qr toolkit', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();

    $staff = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($staff)
        ->withServerVariables(tenantHost())
        ->get(route('office.qr'))
        ->assertOk()
        ->assertSee('Open public page')
        ->assertSee('Open QR image')
        ->assertSee('Download QR image')
        ->assertSee('/o/'.$office->slug.'/staff/'.$staff->id, false)
        ->assertSee('signature=', false);

    $this->actingAs($staff)
        ->withServerVariables(tenantHost())
        ->get(route('office.qr.image'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs($staff)
        ->withServerVariables(tenantHost())
        ->get(route('office.qr.image', ['download' => 1]))
        ->assertOk()
        ->assertHeader('Content-Disposition');
});

test('signed office staff qr route renders and assigns queue to that staff', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();

    $staff = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'approved_at' => now(),
    ]);

    $signedPath = URL::signedRoute('queue.office.staff', [
        'slug' => $office->slug,
        'userId' => $staff->id,
    ], null, false);

    $this->withServerVariables(tenantHost())
        ->get($signedPath)
        ->assertOk()
        ->assertSee('Assigned staff: '.$staff->name)
        ->assertSee('name="preferred_staff_user_id" value="'.$staff->id.'"', false);

    $this->withServerVariables(tenantHost())
        ->post(route('queue.get', ['slug' => $office->slug]), [
            'guest_name' => 'Queue Visitor',
            'guest_email' => 'visitor@example.test',
            'guest_phone' => '09123456789',
            'preferred_staff_user_id' => $staff->id,
        ])
        ->assertRedirect();

    $entry = \App\Models\QueueEntry::query()->latest('id')->first();
    expect($entry)->not->toBeNull();
    expect($entry->assigned_staff_user_id)->toBe($staff->id);
});

test('office staff call next skips queues assigned to other staff', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();

    $staffA = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'approved_at' => now(),
    ]);

    $staffB = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'approved_at' => now(),
    ]);

    \App\Models\QueueEntry::create([
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'user_id' => null,
        'assigned_staff_user_id' => $staffB->id,
        'guest_name' => 'For Staff B',
        'guest_email' => 'b@example.test',
        'queue_number' => 1,
        'queue_date' => today(),
        'status' => \App\Models\QueueEntry::STATUS_WAITING,
        'reference_code' => strtoupper(Str::random(8)),
    ]);

    $target = \App\Models\QueueEntry::create([
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'user_id' => null,
        'assigned_staff_user_id' => $staffA->id,
        'guest_name' => 'For Staff A',
        'guest_email' => 'a@example.test',
        'queue_number' => 2,
        'queue_date' => today(),
        'status' => \App\Models\QueueEntry::STATUS_WAITING,
        'reference_code' => strtoupper(Str::random(8)),
    ]);

    $this->actingAs($staffA)
        ->withServerVariables(tenantHost())
        ->post(route('office.call-next'))
        ->assertRedirect();

    expect($target->fresh()->status)->toBe(\App\Models\QueueEntry::STATUS_CALLED);
});

test('simple tenant rbac keeps office staff and admin in separate workspaces', function () {
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

    $officeId = \App\Models\Office::query()->value('id');

    $staff = User::factory()->create([
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'approved_at' => now(),
    ]);

    $this->actingAs($staff)
        ->withServerVariables(tenantHost())
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('office.dashboard'))
        ->assertForbidden();
});





test('simple rbac settings can block office staff reports without affecting admin pages', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
          'settings' => [
              'rbac' => [
                  'office_staff' => [
                      'office' => [
                          'dashboard' => true,
                          'qr' => true,
                          'queue' => ['manage' => true],
                          'activity' => ['view' => true],
                      ],
                      'reports' => ['view' => false],
                  ],
              ],
        ],
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

    $this->actingAs($staff)
        ->withServerVariables(tenantHost())
        ->get(route('office.reports'))
        ->assertForbidden();

      $this->actingAs($admin)
          ->withServerVariables(tenantHost())
          ->get(route('admin.dashboard'))
          ->assertOk();
  });

  test('simple rbac settings can disable queue operations while keeping dashboard access', function () {
      $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
      $tenant = Tenant::create([
          'name' => 'Acme Office',
          'slug' => 'acme-office',
          'plan_id' => $plan->id,
          'subdomain' => 'acme',
          'database_name' => 'tenant_'.Str::random(10),
          'is_active' => true,
          'settings' => [
              'rbac' => [
                  'office_staff' => [
                      'office' => [
                          'dashboard' => true,
                          'qr' => true,
                          'queue' => ['manage' => false],
                          'activity' => ['view' => true],
                      ],
                      'reports' => ['view' => true],
                  ],
              ],
          ],
      ]);

      app(TenantDatabaseManager::class)->provision($tenant, [
          'name' => 'Tenant Admin',
          'username' => 'tenant.admin',
          'email' => 'admin@acme.test',
          'phone' => '09123456789',
          'password' => 'Password123!',
      ]);

      app(TenantDatabaseManager::class)->activate($tenant);

      $officeId = \App\Models\Office::query()->value('id');
      $staff = User::factory()->create([
          'role' => User::ROLE_OFFICE_STAFF,
          'tenant_id' => $tenant->id,
          'office_id' => $officeId,
          'approved_at' => now(),
      ]);

      $queueEntry = \App\Models\QueueEntry::query()->create([
          'office_id' => $officeId,
          'queue_number' => 1,
          'display_name' => 'Test Guest',
          'queue_date' => today()->toDateString(),
          'status' => \App\Models\QueueEntry::STATUS_WAITING,
          'reference_code' => 'Q-1001',
      ]);

      $this->actingAs($staff)
          ->withServerVariables(tenantHost())
          ->get(route('office.dashboard'))
          ->assertOk();

      $this->actingAs($staff)
          ->withServerVariables(tenantHost())
          ->post(route('office.call-next'))
          ->assertForbidden();

      $this->actingAs($staff)
          ->withServerVariables(tenantHost())
          ->patch(route('office.queue.update', $queueEntry), [
              'status' => 'serving',
          ])
          ->assertForbidden();
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

test('public tracker can show queue references for the current tenant', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();

    $queueEntry = \App\Models\QueueEntry::create([
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'display_name' => 'Maria Santos',
        'guest_email' => 'maria@example.test',
        'queue_number' => 12,
        'status' => 'waiting',
        'queue_date' => today()->toDateString(),
        'reference_code' => 'QREF1234',
    ]);

    $this->withServerVariables(tenantHost())
        ->get('/t/'.$queueEntry->reference_code)
        ->assertOk()
        ->assertSee('Queue number')
        ->assertSee($queueEntry->reference_code)
        ->assertSee('#12')
        ->assertSee('waiting');
});

test('public office page includes a tracker form for existing references', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();

    $this->withServerVariables(tenantHost())
        ->get('/o/'.$office->slug)
        ->assertOk()
        ->assertSee('Track an existing reference')
        ->assertSee('Track now');
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
        ->assertRedirect(route('admin.dashboard'));
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
                ],
                'guest_queue' => false,
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
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Applications today')
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
            'show_service_type' => 1,
            'dashboard_profile' => 'cashier',
            'label_queue' => 'Queue',
            'label_office' => 'Office',
        ])
        ->assertRedirect(route('admin.customization.index'))
        ->assertSessionHas('success', 'Customization saved.');

    $tenant->refresh();

    expect($tenant->getSetting('dashboard.profile'))->toBe('cashier');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.dashboard'))
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
        ->assertSessionHas('success', 'Office staff approved successfully.');

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
        'features' => ['queue', 'email_notifications'],
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
        'features' => ['queue', 'email_notifications'],
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
        ->assertSee('Admin settings')
        ->assertSee('Workspace info')
        ->assertSee('Profile settings')
        ->assertSee('Change password');

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

test('tenant admin can view registered workspace profile details', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'address' => 'Main Campus, Building A',
        'email' => 'registrar@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Office Admin',
        'username' => 'registrar.admin',
        'email' => 'registrar@example.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->withServerVariables(['HTTP_HOST' => 'registrar.localhost'])
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Workspace info')
        ->assertSee('Registrar Office')
        ->assertSee('registrar@example.test')
        ->assertSee('09123456789')
        ->assertSee(\App\Support\TenantUrl::workspace($tenant), false)
        ->assertSee(\App\Support\TenantUrl::login($tenant), false)
        ->assertDontSee('Password123!');

    $this->actingAs($admin)
        ->withServerVariables(['HTTP_HOST' => 'registrar.localhost'])
        ->get(route('admin.profile'))
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHas('info', 'Workspace info is now included in Admin settings.');
});

test('tenant workspace settings page renders on the dedicated tenant domain', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
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
        ->get(route('tenant.settings.edit'))
        ->assertRedirect(route('admin.settings.edit'));
});

test('tenant user can update workspace settings on the dedicated tenant domain', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Admin',
        'username' => 'registrar.admin',
        'email' => 'admin@registrar.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    $staff = User::factory()->create([
        'name' => 'Registrar Staff',
        'username' => 'registrar.staff',
        'email' => 'staff@registrar.test',
        'phone' => '09123456780',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => now(),
        'password' => 'Password123!',
    ]);

    $this->actingAs($staff)
        ->withServerVariables(['HTTP_HOST' => 'registrar.localhost'])
        ->put(route('tenant.settings.update'), [
            'name' => 'Registrar Staff Updated',
            'email' => 'workspace@registrar.test',
            'phone' => '09998887777',
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('tenant.settings.edit'))
        ->assertSessionHas('success', 'Your tenant workspace settings have been updated.');

    $staff->refresh();

    expect($staff->name)->toBe('Registrar Staff Updated');
    expect($staff->email)->toBe('workspace@registrar.test');
    expect($staff->phone)->toBe('09998887777');
    expect(Hash::check('NewPassword123!', $staff->password))->toBeTrue();
});

test('tenant admin uses a separate admin workspace from the shared tenant dashboard', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Registrar Office',
        'slug' => 'registrar-office',
        'plan_id' => $plan->id,
        'subdomain' => 'registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
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
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin dashboard')
        ->assertSee('Admin settings')
        ->assertDontSee('Shared workspace dashboard');
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

test('tenant admin can search and filter office staff pages', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();
    $otherOffice = \App\Models\Office::create([
        'tenant_id' => $tenant->id,
        'name' => 'Records Office',
        'slug' => 'records-office',
    ]);

    User::factory()->create([
        'name' => 'Alice Searchable',
        'username' => 'alice.staff',
        'email' => 'alice@acme.test',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'approved_at' => now(),
    ]);

    User::factory()->create([
        'name' => 'Bob Hidden',
        'username' => 'bob.staff',
        'email' => 'bob@acme.test',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $otherOffice->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.users.index', ['search' => 'Alice', 'office_id' => $office->id]))
        ->assertOk()
        ->assertSee('Alice Searchable')
        ->assertDontSee('Bob Hidden')
        ->assertSee('Apply filters');
});

test('tenant admin office staff list paginates with summary details', function () {
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

    $office = \App\Models\Office::query()->firstOrFail();

    foreach (range(1, 12) as $index) {
        User::factory()->create([
            'name' => 'Paged Staff '.$index,
            'username' => 'paged.staff.'.$index,
            'email' => 'paged'.$index.'@acme.test',
            'role' => User::ROLE_OFFICE_STAFF,
            'tenant_id' => $tenant->id,
            'office_id' => $office->id,
            'approved_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Showing 1-10 of 12 approved office staff accounts.')
        ->assertSee('Paged Staff 1')
        ->assertSee('?page=2');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.users.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Showing 11-12 of 12 approved office staff accounts.')
        ->assertSee('Paged Staff');
});

test('tenant admin can download tenant reports in csv and print formats', function () {
    $plan = Plan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'is_active' => true,
        'features' => ['queue', 'reports'],
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

    app(TenantDatabaseManager::class)->activate($tenant);

    $office = \App\Models\Office::query()->firstOrFail();

    \App\Models\QueueEntry::create([
        'tenant_id' => $tenant->id,
        'office_id' => $office->id,
        'queue_number' => 1,
        'display_name' => 'Queue Guest',
        'service_type' => 'Enrollment',
        'reference_code' => 'Q-1001',
        'status' => 'completed',
        'queue_date' => today()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.reports.download', ['date' => today()->toDateString(), 'format' => 'csv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->actingAs($admin)
        ->withServerVariables(tenantHost())
        ->get(route('admin.reports.download', ['date' => today()->toDateString(), 'format' => 'print']))
        ->assertOk()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertSee('QueueLess')
        ->assertSee('All workspace offices')
        ->assertSee('Print / Save as PDF');
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

test('tenant provisioning rejects sysadmin because it is reserved for the central account', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);

    $tenant = Tenant::create([
        'name' => 'Acme Office',
        'slug' => 'acme-office',
        'plan_id' => $plan->id,
        'subdomain' => 'acme',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage(ReservedUsernames::tenantMessage());

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Tenant Sysadmin',
        'username' => 'sysadmin',
        'email' => 'sysadmin@acme.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);
});
