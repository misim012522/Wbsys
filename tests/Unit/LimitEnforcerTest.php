<?php

namespace Tests\Unit;

use App\Models\Office;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\LimitEnforcer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class LimitEnforcerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Ensure the application is booted so Schema facade and DB connections exist
        $this->refreshApplication();

        // Create a minimal plans table on the central connection before test migrations run
        if (! Schema::connection('central')->hasTable('plans')) {
            Schema::connection('central')->create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        parent::setUp();
    }

    public function test_can_create_office_respects_max_offices()
    {
        // Ensure minimal plans table exists for test environment
        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->integer('max_offices')->nullable();
                $table->timestamps();
            });
        }

        $plan = Plan::create(['slug' => 'test-plan', 'name' => 'Test Plan', 'max_offices' => 2]);
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'plan_id' => $plan->id,
            'database_name' => 'tenant_acme_corp',
        ]);

        // Simulate provisioning that applies plan limits into tenant settings
        $tenant->update(['settings' => ['limits' => ['max_offices' => 2]]]);

        // Ensure minimal offices table exists on the tenant connection
        if (! Schema::connection('tenant')->hasTable('offices')) {
            Schema::connection('tenant')->create('offices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');
                $table->timestamps();
            });
        }

        Office::create(['tenant_id' => $tenant->id, 'name' => 'Main Office']);

        $enforcer = new LimitEnforcer();

        $this->assertTrue($enforcer->canCreateOffice($tenant));

        Office::create(['tenant_id' => $tenant->id, 'name' => 'Secondary Office']);

        $this->assertFalse($enforcer->canCreateOffice($tenant));
    }
}
