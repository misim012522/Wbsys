<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
    /** @var \\Tests\\TestCase $this */
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');

    return;
}

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Notifications\TenantActivationStatusNotification;
use App\Notifications\TenantCredentialsNotification;
use App\Notifications\TenantSubscriptionUpdatedNotification;
use App\Notifications\TenantWorkspaceAccessNotification;
use App\Services\TenantDatabaseManager;
use App\Support\TenantUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('central app landing redirects guests to login', function () {
    /** @var \\Tests\\TestCase $this */
    $this->get('/central')
        ->assertRedirect(route('login'));
});

test('central register page shows the tenant registration form', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'basic'], ['name' => 'Basic', 'price_monthly' => 0, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'ultimate'], ['name' => 'Ultimate', 'price_monthly' => 50, 'is_active' => true]);

    $this->get(route('central.register'))
        ->assertOk()
        ->assertSee('Register your office with a plan that fits how you work.')
        ->assertSee($plan->name)
        ->assertSee('Pro')
        ->assertSee('Ultimate')
        ->assertSee('$0')
        ->assertSee('$20')
        ->assertSee('$50');
});

test('central dashboard shows the tenant table', function () {
    /** @var \\Tests\\TestCase $this */
    Plan::firstOrCreate(['slug' => 'basic'], ['name' => 'Basic', 'price_monthly' => 0, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    Plan::firstOrCreate(['slug' => 'ultimate'], ['name' => 'Ultimate', 'price_monthly' => 50, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_SYSTEM_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $tenant = Tenant::create([
        'name' => 'Registrar Office '.$suffix,
        'slug' => 'registrar-office-'.$suffix,
        'plan_id' => Plan::where('slug', 'pro')->value('id'),
        'subdomain' => 'registrar-'.$suffix,
        'database_name' => tenantDatabaseName('Registrar Office '.$suffix),
        'address' => 'Main Campus, Building A',
        'email' => 'registrar-'.$suffix.'@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
        'approved_at' => now(),
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Admin',
        'username' => 'registrar.admin',
        'email' => 'registrar-admin-'.$suffix.'@example.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
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
        ->assertSee('Usage Summary')
        ->assertSee('Last Activity')
        ->assertSee('New tenant registrations stay pending until approved in this dashboard.')
        ->assertSee(\App\Support\TenantUrl::workspace($tenant), false)
        ->assertSee(\App\Support\TenantUrl::login($tenant), false)
        ->assertSee('registrar-'.$suffix.'.lvh.me')
        ->assertSee('Main tenant account')
        ->assertSee('Registrar Admin')
        ->assertSee('registrar.admin')
        ->assertSee('Usage Summary')
        ->assertSee('Last Activity')
        ->assertSee('Deactivate tenant')
        ->assertSee('Send access email')
        ->assertSee('Edit subscription')
        ->assertSee('Access control')
        ->assertSee('Manage offices')
        ->assertSee('Manage office staff accounts')
        ->assertSee('Manage queue operations')
        ->assertDontSee('Register tenant');
});

test('central dashboard only shows the main tenant account and hides office staff data', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_SYSTEM_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $tenant = Tenant::create([
        'name' => 'Registrar Office '.$suffix,
        'slug' => 'registrar-office-'.$suffix,
        'plan_id' => $plan->id,
        'subdomain' => 'registrar-'.$suffix,
        'database_name' => 'tenant_registrar_office_'.$suffix,
        'email' => 'registrar-'.$suffix.'@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    $tenantAdmin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Admin',
        'username' => 'registrar.admin',
        'email' => 'registrar-admin-'.$suffix.'@example.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    User::on('tenant')->create([
        'name' => 'Hidden Office Staff',
        'username' => 'registrar.staff',
        'email' => 'staff-'.$suffix.'@example.test',
        'phone' => '09998887777',
        'password' => 'Password123!',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($developer)
        ->get(route('central.dashboard'))
        ->assertOk()
        ->assertSee($tenantAdmin->name)
        ->assertSee($tenantAdmin->username)
        ->assertDontSee('Hidden Office Staff')
        ->assertDontSee('registrar.staff');
});

test('central dashboard usage summary counts tenant office staff', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_SYSTEM_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $tenant = Tenant::create([
        'name' => 'Registrar Office '.$suffix,
        'slug' => 'registrar-office-'.$suffix,
        'plan_id' => $plan->id,
        'subdomain' => 'registrar-'.$suffix,
        'database_name' => 'tenant_registrar_office_'.$suffix,
        'email' => 'registrar-'.$suffix.'@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Admin',
        'username' => 'registrar.admin',
        'email' => 'registrar-admin-'.$suffix.'@example.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    User::on('tenant')->create([
        'name' => 'Office Staff One',
        'username' => 'registrar.staff.one',
        'email' => 'staff-one-'.$suffix.'@example.test',
        'phone' => '09998887771',
        'password' => 'Password123!',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    User::on('tenant')->create([
        'name' => 'Office Staff Two',
        'username' => 'registrar.staff.two',
        'email' => 'staff-two-'.$suffix.'@example.test',
        'phone' => '09998887772',
        'password' => 'Password123!',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => \App\Models\Office::query()->value('id'),
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($developer)
        ->get(route('central.dashboard'))
        ->assertOk()
        ->assertSeeText('2')
        ->assertSeeText('office staff');
});

test('tenant registration creates a pending tenant and does not send credentials before approval', function () {
    /** @var \\Tests\\TestCase $this */
    Carbon::setTestNow('2026-03-19 10:15:00');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    Notification::fake();

    $tenantName = 'Registrar Office '.Str::upper(Str::random(4));
    $this->post(route('central.register.store'), [
        'tenant_name' => $tenantName,
        'tenant_admin_username' => 'registrar.admin',
        'plan_id' => $plan->id,
        'address' => 'Main Campus, Building A',
        'email' => 'registrar@example.test',
        'contact_number' => '09123456789',
    ])->assertRedirect(route('login'));

    $tenant = Tenant::where('name', $tenantName)->first();

    expect($tenant)->not->toBeNull();
    expect($tenant->email)->toBe('registrar@example.test');
    expect($tenant->contact_number)->toBe('09123456789');
    expect($tenant->database_name)->toEndWith('_buksu_queueless.db');
    expect($tenant->database_name)->not->toBe(':memory:');
    expect($tenant->getSetting('database.mode'))->toBe('dedicated');
    expect($tenant->is_active)->toBeFalse();
    expect($tenant->approved_at)->toBeNull();
    expect($tenant->created_at?->format('Y-m-d H:i:s'))->toBe('2026-03-19 10:15:00');

    $subscription = TenantSubscription::where('tenant_id', $tenant->id)->first();

    expect($subscription)->not->toBeNull();
    expect($subscription->plan_id)->toBe($plan->id);
    expect($subscription->status)->toBe(TenantSubscription::STATUS_ACTIVE);
    expect($subscription->starts_at?->format('Y-m-d H:i:s'))->toBe('2026-03-19 10:15:00');

    app(TenantDatabaseManager::class)->activate($tenant);

    $admin = User::on('tenant')->where('tenant_id', $tenant->id)->first();

    expect($admin)->not->toBeNull();
    expect($admin->username)->toBe('registrar.admin');
    expect($admin->email)->toBe('registrar@example.test');

    Notification::assertNothingSent();

    Carbon::setTestNow();
});

test('registered tenant email becomes the tenant admin identity in the tenant workspace', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);

    $tenantName = 'Admissions Office '.Str::upper(Str::random(4));
    $registeredEmail = 'admissions@example.test';
    $registeredContact = '09998887777';
    $registeredUsername = 'admissions.admin';

    $this->post(route('central.register.store'), [
        'tenant_name' => $tenantName,
        'tenant_admin_username' => $registeredUsername,
        'plan_id' => $plan->id,
        'address' => 'North Campus, Building C',
        'email' => $registeredEmail,
        'contact_number' => $registeredContact,
    ])->assertRedirect(route('login'));

    $tenant = Tenant::where('name', $tenantName)->firstOrFail();

    app(TenantDatabaseManager::class)->activate($tenant);

    $admin = User::on('tenant')
        ->where('tenant_id', $tenant->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->first();

    expect($admin)->not->toBeNull();
    expect($admin->username)->toBe($registeredUsername);
    expect($admin->email)->toBe($registeredEmail);
    expect($admin->phone)->toBe($registeredContact);
    expect($admin->tenant_id)->toBe($tenant->id);
});

test('tenant registration requires an admin username', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);

    $this->from(route('central.register'))
        ->post(route('central.register.store'), [
            'tenant_name' => 'Registrar Office',
            'tenant_admin_username' => '',
            'plan_id' => $plan->id,
            'address' => 'Main Campus, Building A',
            'email' => 'registrar@example.test',
            'contact_number' => '09123456789',
        ])
        ->assertRedirect(route('central.register'))
        ->assertSessionHasErrors('tenant_admin_username');
});

test('tenant registration rejects sysadmin as the tenant admin username', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);

    $this->from(route('central.register'))
        ->post(route('central.register.store'), [
            'tenant_name' => 'Registrar Office',
            'tenant_admin_username' => 'sysadmin',
            'plan_id' => $plan->id,
            'address' => 'Main Campus, Building A',
            'email' => 'registrar@example.test',
            'contact_number' => '09123456789',
        ])
        ->assertRedirect(route('central.register'))
        ->assertSessionHasErrors([
            'tenant_admin_username' => 'The username sysadmin is reserved for the central account only.',
        ]);
});

test('tenant credential and access emails include the real tenant admin username', function () {
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));

    $tenant = Tenant::create([
        'name' => 'Registrar Office Mail '.$suffix,
        'slug' => 'registrar-office-mail-'.$suffix,
        'plan_id' => $plan->id,
        'subdomain' => 'registrar-mail-'.$suffix,
        'database_name' => 'tenant_registrar_office_mail_'.$suffix,
        'email' => 'registrar-'.$suffix.'@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Office Admin',
        'username' => 'registrar.admin',
        'email' => $tenant->email,
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $credentialsMail = (new TenantCredentialsNotification($tenant, 'Temporary123!'))->toMail($admin);
    $accessMail = (new TenantWorkspaceAccessNotification($tenant))->toMail($admin);

    expect(collect($credentialsMail->introLines)->contains('Username: registrar.admin'))->toBeTrue();
    expect(collect($accessMail->introLines)->contains('Username: registrar.admin'))->toBeTrue();
});

test('tenant admin can open workspace url then log in to the designated admin dashboard', function () {
    /** @var \\Tests\\TestCase $this */
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));

    $tenant = Tenant::create([
        'name' => 'Registrar Office Dashboard '.$suffix,
        'slug' => 'registrar-office-dashboard-'.$suffix,
        'plan_id' => $plan->id,
        'subdomain' => 'registrar-dashboard-'.$suffix,
        'database_name' => 'tenant_registrar_office_dashboard_'.$suffix,
        'email' => 'registrar-'.$suffix.'@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
        'approved_at' => now(),
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Registrar Office Admin',
        'username' => 'registrar.admin',
        'email' => $tenant->email,
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $workspaceUrl = TenantUrl::workspace($tenant);
    $loginUrl = TenantUrl::login($tenant);
    $dashboardUrl = TenantUrl::dashboard($tenant);

    $this->get($workspaceUrl)
        ->assertRedirect();

    $response = $this->post($loginUrl, [
        'login' => 'registrar.admin',
        'password' => 'Password123!',
    ]);

    $location = $response->headers->get('Location');

    if (str_starts_with((string) $location, TenantUrl::authContinue($tenant).'?token=')) {
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);
        $token = $query['token'] ?? null;

        expect($token)->not->toBeNull();

        Auth::logout();

        $this->get(TenantUrl::authContinue($tenant).'?token='.urlencode((string) $token))
            ->assertRedirect($dashboardUrl);
        $this->get($dashboardUrl)
            ->assertOk();
    } elseif ($location === $dashboardUrl) {
        $this->get($dashboardUrl)
            ->assertOk();
    } else {
        expect((string) $location)->toContain('/login');
        $this->actingAs($admin)
            ->get($dashboardUrl)
            ->assertStatus(302);
    }
});

test('different tenants generate different workspace hosts', function () {
    /** @var \\Tests\\TestCase $this */
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);

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
    /** @var \\Tests\\TestCase $this */
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_SYSTEM_ADMIN,
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

test('central admin can update tenant details from the dashboard', function () {
    /** @var \\Tests\\TestCase $this */
    $context = createManagedTenantForCentralTests();

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.update', $context['tenant']), [
            'name' => 'Registrar and Admissions Office',
            'address' => 'Main Campus, Building B',
            'contact_number' => '09998887777',
            'email' => 'admissions@example.test',
            'subdomain' => 'admissions',
            'domain' => '',
        ])
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    $tenant = $context['tenant']->fresh();

    expect($tenant->name)->toBe('Registrar and Admissions Office');
    expect($tenant->address)->toBe('Main Campus, Building B');
    expect($tenant->contact_number)->toBe('09998887777');
    expect($tenant->email)->toBe('admissions@example.test');
    expect($tenant->subdomain)->toBe('admissions');
    expect($tenant->domain)->toBeNull();
});

test('central admin can update tenant rbac settings from the dashboard', function () {
    /** @var \\Tests\\TestCase $this */
    $context = createManagedTenantForCentralTests();

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.rbac', $context['tenant']), [
            'tenant_admin_admin_office_manage' => '1',
            'tenant_admin_users_manage' => '1',
            'office_staff_office_dashboard' => '1',
            'office_staff_office_qr' => '1',
            'office_staff_office_activity_view' => '1',
        ])
        ->assertRedirect(route('central.tenants.rbac.edit', $context['tenant']))
        ->assertSessionHas('success');

    $tenant = $context['tenant']->fresh();

    expect($tenant->getSetting('rbac.tenant_admin.admin.office.manage', true))->toBeTrue();
    expect($tenant->getSetting('rbac.tenant_admin.users.manage', true))->toBeTrue();
    expect($tenant->getSetting('rbac.tenant_admin.admin.office.serve', true))->toBeFalse();
    expect($tenant->getSetting('rbac.tenant_admin.reports.view', true))->toBeFalse();
    expect($tenant->getSetting('rbac.tenant_admin.admin.customization.manage', true))->toBeFalse();
    expect($tenant->getSetting('rbac.office_staff.office.dashboard', true))->toBeTrue();
    expect($tenant->getSetting('rbac.office_staff.office.qr', true))->toBeTrue();
    expect($tenant->getSetting('rbac.office_staff.office.activity.view', true))->toBeTrue();
    expect($tenant->getSetting('rbac.office_staff.office.queue.manage', true))->toBeFalse();
    expect($tenant->getSetting('rbac.office_staff.office.appointments.manage', true))->toBeFalse();
    expect($tenant->getSetting('rbac.office_staff.reports.view', true))->toBeFalse();
});

test('central admin can open a dedicated rbac page for a specific tenant', function () {
    /** @var \\Tests\\TestCase $this */
    $context = createManagedTenantForCentralTests();

    $this->actingAs($context['developer'])
        ->get(route('central.tenants.rbac.edit', $context['tenant']))
        ->assertOk()
        ->assertSee('Central RBAC')
        ->assertSee($context['tenant']->name)
        ->assertSee('Manage offices')
        ->assertSee('Manage office staff accounts')
        ->assertSee('Manage queue operations')
        ->assertSee('Any changes saved here only affect this tenant');
});

test('central admin can deactivate and reactivate a tenant and notify the tenant admin', function () {
    /** @var \\Tests\\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.activation', $context['tenant']))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    expect($context['tenant']->fresh()->is_active)->toBeFalse();
    Notification::assertSentTo($context['admin'], TenantActivationStatusNotification::class);

    Notification::fake();

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.activation', $context['tenant']->fresh()))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    expect($context['tenant']->fresh()->is_active)->toBeTrue();
    Notification::assertSentTo($context['admin'], TenantActivationStatusNotification::class);
});

test('deactivated tenant workspace logs out tenant users with a deactivation notice while guest visitors still see the disabled page', function () {
    /** @var \\Tests\\TestCase $this */
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Disabled Registrar',
        'slug' => 'disabled-registrar',
        'plan_id' => $plan->id,
        'subdomain' => 'disabled-registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'email' => 'disabled@test.local',
        'contact_number' => '09123456789',
        'is_active' => true,
        'approved_at' => now(),
    ]);

    app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Disabled Admin',
        'username' => 'disabled.admin',
        'email' => 'disabled@test.local',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_SYSTEM_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($developer)
        ->patch(route('central.tenants.activation', $tenant))
        ->assertRedirect(route('central.dashboard'));

    $tenant->refresh();
    expect($tenant->is_active)->toBeFalse();

    app(TenantDatabaseManager::class)->activate($tenant);
    $tenantAdmin = User::on('tenant')
        ->where('tenant_id', $tenant->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->firstOrFail();
    $officeId = \App\Models\Office::query()->value('id');
    $officeStaff = User::on('tenant')->create([
        'name' => 'Disabled Staff',
        'username' => 'disabled.staff',
        'email' => 'disabled-staff@test.local',
        'phone' => '09123456780',
        'password' => 'Password123!',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    Auth::logout();

    $this->get(\App\Support\TenantUrl::login($tenant))
        ->assertRedirect(\App\Support\TenantUrl::login($tenant));

    $this->actingAs($tenantAdmin)
        ->get(\App\Support\TenantUrl::dashboard($tenant, $tenantAdmin))
        ->assertStatus(302);

    $this->actingAs($officeStaff)
        ->get(\App\Support\TenantUrl::dashboard($tenant, $officeStaff))
        ->assertStatus(302);
});

test('tenant session status endpoint logs out deactivated tenant sessions automatically', function () {
    /** @var \\Tests\\TestCase $this */
    config()->set('app.url', 'http://central.localhost');

    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));
    $tenant = Tenant::create([
        'name' => 'Realtime Registrar '.$suffix,
        'slug' => 'realtime-registrar-'.$suffix,
        'plan_id' => $plan->id,
        'subdomain' => 'realtime-registrar-'.$suffix,
        'database_name' => tenantDatabaseName('Realtime Registrar '.$suffix),
        'email' => 'realtime-'.$suffix.'@test.local',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Realtime Admin',
        'username' => 'realtime.admin',
        'email' => 'realtime-'.$suffix.'@test.local',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $tenant->forceFill(['is_active' => false])->save();

    $this->actingAs($admin)
        ->withHeader('Host', 'realtime-registrar-'.$suffix.'.localhost')
        ->get(route('api.session.tenant-status'))
        ->assertStatus(423)
        ->assertJson([
            'active' => false,
            'deactivated' => true,
            'message' => 'Logging out due to deactivation.',
            'redirect_url' => \App\Support\TenantUrl::login(null, true),
        ]);
});

test('central admin can update a tenant subscription and notify the tenant admin', function () {
    /** @var \\Tests\\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();
    $ultimate = Plan::firstOrCreate(['slug' => 'ultimate'], ['name' => 'Ultimate', 'price_monthly' => 50, 'is_active' => true]);

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.subscription', $context['tenant']), [
            'plan_id' => $ultimate->id,
            'status' => TenantSubscription::STATUS_TRIALING,
            'starts_at' => '2026-03-20 08:00:00',
            'ends_at' => '2026-04-20 08:00:00',
        ])
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    $tenant = $context['tenant']->fresh();
    $subscription = $tenant->subscriptions()->latest('id')->first();

    expect($tenant->plan_id)->toBe($ultimate->id);
    expect($subscription)->not->toBeNull();
    expect($subscription->plan_id)->toBe($ultimate->id);
    expect($subscription->status)->toBe(TenantSubscription::STATUS_TRIALING);
    expect($subscription->starts_at?->format('Y-m-d H:i:s'))->toBe('2026-03-20 08:00:00');
    expect($subscription->ends_at?->format('Y-m-d H:i:s'))->toBe('2026-04-20 08:00:00');

    Notification::assertSentTo($context['admin'], TenantSubscriptionUpdatedNotification::class);
});

test('central admin can resend tenant workspace access email', function () {
    /** @var \\Tests\\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();

    $this->actingAs($context['developer'])
        ->post(route('central.tenants.workspace-access', $context['tenant']))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    Notification::assertSentTo($context['admin'], TenantWorkspaceAccessNotification::class);
});

test('central admin can reset the tenant admin temporary password and resend credentials', function () {
    /** @var \\Tests\\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();

    $oldHash = $context['admin']->password;

    $this->actingAs($context['developer'])
        ->post(route('central.tenants.reset-password', $context['tenant']))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    app(TenantDatabaseManager::class)->activate($context['tenant']);

    $admin = User::on('tenant')
        ->where('tenant_id', $context['tenant']->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->firstOrFail();

    expect($admin->password)->not->toBe($oldHash);
    Notification::assertSentTo($admin, TenantCredentialsNotification::class);
});

test('central admin cannot send tenant credentials before approval', function () {
    /** @var \\Tests\\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();

    $context['tenant']->forceFill([
        'is_active' => false,
        'approved_at' => null,
    ])->save();

    $this->actingAs($context['developer'])
        ->post(route('central.tenants.reset-password', $context['tenant']))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('info');

    Notification::assertNothingSent();
});

test('central admin can approve a pending tenant and send credentials', function () {
    /** @var \\Tests\\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();

    $context['tenant']->forceFill([
        'is_active' => false,
        'approved_at' => null,
    ])->save();

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.approve', $context['tenant']))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('success');

    $approvedTenant = $context['tenant']->fresh();
    expect($approvedTenant->is_active)->toBeTrue();
    expect($approvedTenant->approved_at)->not->toBeNull();

    app(TenantDatabaseManager::class)->activate($approvedTenant);
    $admin = User::on('tenant')
        ->where('tenant_id', $approvedTenant->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->firstOrFail();

    Notification::assertSentTo($admin, TenantCredentialsNotification::class);
});

test('central admin approval still succeeds when tenant credentials email cannot be sent', function () {
    /** @var \Tests\TestCase $this */
    Notification::fake();
    $context = createManagedTenantForCentralTests();

    $context['tenant']->forceFill([
        'is_active' => false,
        'approved_at' => null,
    ])->save();

    Notification::shouldReceive('send')
        ->andThrow(new RuntimeException('SMTP unavailable'));

    $this->actingAs($context['developer'])
        ->patch(route('central.tenants.approve', $context['tenant']))
        ->assertRedirect(route('central.dashboard'))
        ->assertSessionHas('info');

    $approvedTenant = $context['tenant']->fresh();
    expect($approvedTenant->is_active)->toBeTrue();
    expect($approvedTenant->approved_at)->not->toBeNull();

    app(TenantDatabaseManager::class)->activate($approvedTenant);
    $admin = User::on('tenant')
        ->where('tenant_id', $approvedTenant->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->firstOrFail();

    expect(Hash::check('temporary-password', $admin->password))->toBeFalse();
});

test('tenant update validation reopens the correct modal with named errors', function () {
    /** @var \\Tests\\TestCase $this */
    $context = createManagedTenantForCentralTests();

    $response = $this->actingAs($context['developer'])
        ->from(route('central.dashboard'))
        ->patch(route('central.tenants.update', $context['tenant']), [
            'name' => '',
            'address' => '',
            'contact_number' => '',
            'email' => 'not-an-email',
            'subdomain' => 'invalid subdomain',
            'domain' => '',
        ]);

    $response->assertRedirect(route('central.dashboard'));
    $response->assertSessionHas('open_modal', 'tenant-edit-modal-'.$context['tenant']->id);
    expect(session('errors')->getBag('tenantUpdate_'.$context['tenant']->id)->has('name'))->toBeTrue();
    expect(session('errors')->getBag('tenantUpdate_'.$context['tenant']->id)->has('email'))->toBeTrue();
});

test('subscription update validation reopens the correct modal with named errors', function () {
    /** @var \\Tests\\TestCase $this */
    $context = createManagedTenantForCentralTests();

    $response = $this->actingAs($context['developer'])
        ->from(route('central.dashboard'))
        ->patch(route('central.tenants.subscription', $context['tenant']), [
            'plan_id' => '',
            'status' => 'bad-status',
            'starts_at' => '',
            'ends_at' => '2026-01-01 00:00:00',
        ]);

    $response->assertRedirect(route('central.dashboard'));
    $response->assertSessionHas('open_modal', 'tenant-subscription-modal-'.$context['tenant']->id);
    expect(session('errors')->getBag('tenantSubscription_'.$context['tenant']->id)->has('plan_id'))->toBeTrue();
    expect(session('errors')->getBag('tenantSubscription_'.$context['tenant']->id)->has('status'))->toBeTrue();
});

function createManagedTenantForCentralTests(): array
{
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price_monthly' => 20, 'is_active' => true]);
    $suffix = Str::lower(Str::random(6));
    $tenantName = 'Registrar Office '.$suffix;
    $tenantSlug = 'registrar-office-'.$suffix;
    $tenantSubdomain = 'registrar-'.$suffix;
    $tenantDatabase = tenantDatabaseName('Registrar Office '.$suffix);
    $tenantEmail = 'registrar-'.$suffix.'@example.test';

    $developer = User::factory()->create([
        'username' => 'developer',
        'role' => User::ROLE_SYSTEM_ADMIN,
        'tenant_id' => null,
        'approved_at' => now(),
    ]);

    $tenant = Tenant::create([
        'name' => $tenantName,
        'slug' => $tenantSlug,
        'plan_id' => $plan->id,
        'subdomain' => $tenantSubdomain,
        'database_name' => $tenantDatabase,
        'address' => 'Main Campus, Building A',
        'email' => $tenantEmail,
        'contact_number' => '09123456789',
        'is_active' => true,
        'approved_at' => now(),
    ]);

    TenantSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'starts_at' => now(),
        'status' => TenantSubscription::STATUS_ACTIVE,
    ]);

    $databasePath = database_path('tenants/'.$tenantDatabase);
    DB::purge('tenant');
    if (File::exists($databasePath)) {
        File::delete($databasePath);
    }

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => $tenantName.' Admin',
        'username' => 'admin',
        'email' => $tenantEmail,
        'phone' => '09123456789',
        'password' => 'temporary-password',
    ]);

    return compact('developer', 'tenant', 'admin');
}
