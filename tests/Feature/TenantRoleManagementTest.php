<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');
    return;
}

use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('tenant admin can create a custom role and assign it to staff', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Admissions Office',
        'slug' => 'admissions-office',
        'plan_id' => $plan->id,
        'subdomain' => 'admissions',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Admissions Admin',
        'username' => 'admissions.admin',
        'email' => 'admissions-admin@test.local',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    $officeId = \App\Models\Office::query()->value('id');

    $staff = User::on('tenant')->create([
        'name' => 'Admissions Staff',
        'username' => 'admissions.staff',
        'email' => 'admissions-staff@test.local',
        'phone' => '09123456788',
        'password' => 'Password123!',
        'role' => User::ROLE_OFFICE_STAFF,
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withHeader('Host', 'admissions.localhost')
        ->post('/admin/roles', [
            'name' => 'Report Viewer',
            'slug' => 'report_viewer',
            'description' => 'Can read reports only',
            'permissions' => \App\Models\Permission::on('tenant')->where('slug', 'reports.view')->pluck('id')->all(),
        ])->assertRedirect('/admin/roles');

    $role = Role::on('tenant')->where('slug', 'report_viewer')->first();

    expect($role)->not->toBeNull();
    expect($role->permissions()->where('slug', 'reports.view')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->withHeader('Host', 'admissions.localhost')
        ->patch('/admin/users/'.$staff->id.'/role', [
            'role' => 'report_viewer',
        ])->assertSessionHas('success');

    app(TenantDatabaseManager::class)->activate($tenant);
    expect(User::on('tenant')->find($staff->id)?->role)->toBe('report_viewer');
});

test('custom role with user-management permission can open admin user pages but not role management', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
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
        'email' => 'registrar-admin@test.local',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    $role = Role::on('tenant')->create([
        'tenant_id' => $tenant->id,
        'name' => 'Analytics Viewer',
        'slug' => 'analytics_viewer',
        'description' => 'Can manage users',
    ]);

    $role->permissions()->sync(
        \App\Models\Permission::on('tenant')->where('slug', 'users.manage')->pluck('id')
    );

    $officeId = \App\Models\Office::query()->value('id');

    $user = User::on('tenant')->create([
        'name' => 'Analytics User',
        'username' => 'analytics.user',
        'email' => 'analytics-user@test.local',
        'phone' => '09123456788',
        'password' => 'Password123!',
        'role' => 'analytics_viewer',
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->withHeader('Host', 'registrar.localhost')
        ->get('/admin/users')
        ->assertOk();

    $this->actingAs($user)
        ->withHeader('Host', 'registrar.localhost')
        ->get('/admin/roles')
        ->assertForbidden();
});

test('tenant admin can disable a custom role and the assigned user loses access', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Cashier Office',
        'slug' => 'cashier-office',
        'plan_id' => $plan->id,
        'subdomain' => 'cashier',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Cashier Admin',
        'username' => 'cashier.admin',
        'email' => 'cashier-admin@test.local',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    $role = Role::on('tenant')->create([
        'tenant_id' => $tenant->id,
        'name' => 'Desk Officer',
        'slug' => 'desk_officer',
        'description' => 'Can serve queue',
        'is_active' => true,
    ]);

    $role->permissions()->sync(
        \App\Models\Permission::on('tenant')->where('slug', 'office.serve')->pluck('id')
    );

    $officeId = \App\Models\Office::query()->value('id');

    $user = User::on('tenant')->create([
        'name' => 'Desk Officer',
        'username' => 'desk.officer',
        'email' => 'desk-officer@test.local',
        'phone' => '09123456788',
        'password' => 'Password123!',
        'role' => 'desk_officer',
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withHeader('Host', 'cashier.localhost')
        ->patch('/admin/roles/'.$role->id.'/status')
        ->assertSessionHas('success');

    app(TenantDatabaseManager::class)->activate($tenant);
    expect(Role::on('tenant')->find($role->id)?->is_active)->toBeFalse();

    $this->actingAs($user)
        ->withHeader('Host', 'cashier.localhost')
        ->get('/office')
        ->assertForbidden();
});

test('tenant admin cannot delete a custom role while users are still assigned', function () {
    $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true]);
    $tenant = Tenant::create([
        'name' => 'Library Office',
        'slug' => 'library-office',
        'plan_id' => $plan->id,
        'subdomain' => 'library',
        'database_name' => 'tenant_'.Str::random(10),
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Library Admin',
        'username' => 'library.admin',
        'email' => 'library-admin@test.local',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    app(TenantDatabaseManager::class)->activate($tenant);

    $role = Role::on('tenant')->create([
        'tenant_id' => $tenant->id,
        'name' => 'Reference Clerk',
        'slug' => 'reference_clerk',
        'description' => 'Handles reference desk',
        'is_active' => true,
    ]);

    $officeId = \App\Models\Office::query()->value('id');

    User::on('tenant')->create([
        'name' => 'Reference Staff',
        'username' => 'reference.staff',
        'email' => 'reference-staff@test.local',
        'phone' => '09123456788',
        'password' => 'Password123!',
        'role' => 'reference_clerk',
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'approved_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withHeader('Host', 'library.localhost')
        ->delete('/admin/roles/'.$role->id)
        ->assertSessionHasErrors('role');

    app(TenantDatabaseManager::class)->activate($tenant);
    expect(Role::on('tenant')->where('slug', 'reference_clerk')->exists())->toBeTrue();
});
