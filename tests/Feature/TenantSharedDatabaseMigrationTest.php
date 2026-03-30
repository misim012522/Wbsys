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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('legacy tenant database can be merged into the shared application database', function () {
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_active' => true]);

    $tenant = Tenant::create([
        'name' => 'Legacy Registrar',
        'slug' => 'legacy-registrar',
        'plan_id' => $plan->id,
        'subdomain' => 'legacy-registrar',
        'database_name' => 'tenant_'.Str::random(10),
        'email' => 'legacy@example.test',
        'contact_number' => '09123456789',
        'is_active' => true,
    ]);

    $admin = app(TenantDatabaseManager::class)->provision($tenant, [
        'name' => 'Legacy Registrar Admin',
        'username' => 'legacy.admin',
        'email' => 'legacy@example.test',
        'phone' => '09123456789',
        'password' => 'Password123!',
    ]);

    $officeId = DB::connection('tenant')->table('offices')->value('id');

    DB::connection('tenant')->table('queue_entries')->insert([
        'tenant_id' => $tenant->id,
        'office_id' => $officeId,
        'user_id' => $admin->id,
        'queue_number' => 1,
        'guest_name' => 'Legacy Guest',
        'service_type' => 'Enrollment',
        'reference_code' => 'Q-LEGACY-1',
        'status' => 'waiting',
        'queue_date' => today()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Artisan::call('tenants:merge-shared', ['tenant' => $tenant->id]);

    $tenant->refresh();

    expect($tenant->getSetting('database.mode'))->toBe('shared');

    $sharedConnection = config('database.default');

    expect(DB::connection($sharedConnection)->table('users')
        ->where('id', $admin->id)
        ->where('tenant_id', $tenant->id)
        ->exists())->toBeTrue();

    expect(DB::connection($sharedConnection)->table('queue_entries')
        ->where('tenant_id', $tenant->id)
        ->where('reference_code', 'Q-LEGACY-1')
        ->exists())->toBeTrue();

    app(TenantDatabaseManager::class)->activate($tenant);

    expect(User::on('tenant')
        ->where('tenant_id', $tenant->id)
        ->where('username', 'legacy.admin')
        ->exists())->toBeTrue();
});
