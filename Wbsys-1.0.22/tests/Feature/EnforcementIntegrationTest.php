<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicController;
use App\Models\Office;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class EnforcementIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // In testing, UsesCentralConnection maps to database.default (sqlite here)
        $defaultConnection = config('database.default');

        if (! Schema::connection($defaultConnection)->hasTable('plans')) {
            Schema::connection($defaultConnection)->create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::connection($defaultConnection)->hasTable('tenants')) {
            Schema::connection($defaultConnection)->create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('database_name')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('tenant')->hasTable('offices')) {
            Schema::connection('tenant')->create('offices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');
                $table->string('slug')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('max_daily_queue')->default(1000);
                $table->timestamps();
            });
        }

        if (! Schema::connection('tenant')->hasTable('queue_entries')) {
            Schema::connection('tenant')->create('queue_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('office_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('guest_name')->nullable();
                $table->string('guest_email')->nullable();
                $table->string('guest_phone')->nullable();
                $table->string('service_type')->nullable();
                $table->integer('queue_number');
                $table->date('queue_date');
                $table->string('status');
                $table->string('reference_code')->unique();
                $table->timestamp('called_at')->nullable();
                $table->timestamp('served_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('tenant')->hasTable('activity_logs')) {
            Schema::connection('tenant')->create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('office_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->text('description');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->text('properties')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_public_queue_blocked_when_daily_service_limit_reached()
    {
        $defaultConnection = config('database.default');

        $planId = DB::connection($defaultConnection)->table('plans')->insertGetId([
            'slug' => 'basic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // We don't need a Plan model instance here; use the central-inserted id for tenant.plan_id
        $tenant = Tenant::create([
            'name' => 'Acme',
            'slug' => 'acme',
            'plan_id' => $planId,
            'database_name' => 'tenant_acme',
        ]);

        $office = Office::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'slug' => 'main', 'is_active' => true]);

        // apply a zero daily service limit
        $tenant->update(['settings' => ['limits' => ['daily_service_limit' => 0]]]);

        $controller = new PublicController();

        $request = Request::create('/queue/'.$office->slug, 'POST', [
            'guest_name' => 'Test Guest',
            'guest_email' => 'guest@example.com',
        ]);

        $response = $controller->getQueue($request, $office->slug);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals('Daily service limit reached for this workspace.', session('error'));
    }

    public function test_public_queue_allows_creation_when_daily_limit_not_reached()
    {
        $defaultConnection = config('database.default');

        $planId = DB::connection($defaultConnection)->table('plans')->insertGetId([
            'slug' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant = Tenant::create([
            'name' => 'Bravo',
            'slug' => 'bravo',
            'plan_id' => $planId,
            'database_name' => 'tenant_bravo',
        ]);

        $office = Office::create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk',
            'slug' => 'front-desk',
            'is_active' => true,
            'max_daily_queue' => 100,
        ]);

        // Allow at least one queue entry for the day
        $tenant->update(['settings' => ['limits' => ['daily_service_limit' => 1]]]);

        $controller = new PublicController();

        $request = Request::create('/queue/'.$office->slug, 'POST', [
            'guest_name' => 'Allowed Guest',
            'guest_email' => 'allowed@example.com',
        ]);

        $response = $controller->getQueue($request, $office->slug);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertNull(session('error'));
        $this->assertDatabaseCount('queue_entries', 1, 'tenant');
    }
}
