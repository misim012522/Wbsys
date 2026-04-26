<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TenantDatabaseManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $defaultConnection = config('database.default');

        if (! Schema::connection($defaultConnection)->hasTable('plans')) {
            Schema::connection($defaultConnection)->create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('slug')->unique();
                $table->integer('max_offices')->nullable();
                $table->integer('qr_codes_per_office')->nullable();
                $table->integer('daily_service_limit')->nullable();
                $table->string('support_level')->nullable();
                $table->integer('sla_hours')->nullable();
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
    }

    public function test_apply_plan_settings_writes_limits_and_support()
    {
        $plan = Plan::create([
            'slug' => 'pro',
            'name' => 'Pro',
            'max_offices' => 5,
            'qr_codes_per_office' => 50,
            'daily_service_limit' => 200,
            'support_level' => 'priority',
            'sla_hours' => 24,
        ]);

        $tenant = Tenant::create([
            'name' => 'Acme',
            'slug' => 'acme',
            'plan_id' => $plan->id,
            'database_name' => 'tenant_acme',
        ]);

        $mgr = new TenantDatabaseManager();
        $mgr->applyPlanSettings($tenant);

        $this->assertSame(5, $tenant->getSetting('limits.max_offices'));
        $this->assertSame(50, $tenant->getSetting('limits.qr_codes_per_office'));
        $this->assertSame(200, $tenant->getSetting('limits.daily_service_limit'));
        $this->assertSame('priority', $tenant->getSetting('support.level'));
        $this->assertSame(24, $tenant->getSetting('support.sla_hours'));
    }
}
