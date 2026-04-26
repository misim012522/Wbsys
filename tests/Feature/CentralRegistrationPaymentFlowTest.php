<?php

namespace Tests\Feature;

use App\Models\RegistrationPayment;
use App\Services\StripeCheckoutService;
use App\Services\TenantDatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CentralRegistrationPaymentFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config()->set('services.stripe.simulate', false);

        $this->ensureCentralSchema();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function ensureCentralSchema(): void
    {
        $connections = [config('database.default')];

        if (config('database.connections.central')) {
            $connections[] = 'central';
        }

        foreach (array_unique($connections) as $connection) {
            if (! Schema::connection($connection)->hasTable('plans')) {
                Schema::connection($connection)->create('plans', function (Blueprint $table) {
                    $table->id();
                    $table->string('name')->nullable();
                    $table->string('slug')->unique();
                    $table->decimal('price_monthly', 10, 2)->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                });
            }

            if (! Schema::connection($connection)->hasTable('tenants')) {
                Schema::connection($connection)->create('tenants', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('slug')->unique();
                    $table->unsignedBigInteger('plan_id')->nullable();
                    $table->string('domain')->nullable();
                    $table->string('subdomain')->nullable();
                    $table->string('database_name')->nullable();
                    $table->string('address')->nullable();
                    $table->string('email')->nullable();
                    $table->string('contact_number')->nullable();
                    $table->json('settings')->nullable();
                    $table->boolean('is_active')->default(false);
                    $table->timestamp('approved_at')->nullable();
                    $table->timestamps();
                });
            }

            if (! Schema::connection($connection)->hasTable('tenant_subscriptions')) {
                Schema::connection($connection)->create('tenant_subscriptions', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('tenant_id');
                    $table->unsignedBigInteger('plan_id');
                    $table->timestamp('starts_at')->nullable();
                    $table->timestamp('ends_at')->nullable();
                    $table->string('status');
                    $table->timestamps();
                });
            }

            if (! Schema::connection($connection)->hasTable('registration_payments')) {
                Schema::connection($connection)->create('registration_payments', function (Blueprint $table) {
                    $table->id();
                    $table->string('reference')->unique();
                    $table->unsignedBigInteger('tenant_id')->nullable();
                    $table->unsignedBigInteger('plan_id');
                    $table->string('email');
                    $table->string('provider')->default('stripe');
                    $table->string('provider_session_id')->nullable();
                    $table->unsignedInteger('amount_cents');
                    $table->string('currency', 3)->default('usd');
                    $table->string('status')->default('pending');
                    $table->json('payload');
                    $table->timestamp('paid_at')->nullable();
                    $table->timestamp('finalized_at')->nullable();
                    $table->timestamps();
                });
            }
        }
    }

    public function test_store_creates_pending_payment_and_redirects_to_checkout(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 999.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stripe = Mockery::mock(StripeCheckoutService::class);
        $stripe->shouldReceive('createCheckoutSession')->once()->andReturn([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/c/pay/test_session',
        ]);
        $this->app->instance(StripeCheckoutService::class, $stripe);

        $response = $this->post(route('central.register.store'), [
            'tenant_name' => 'Acme Office',
            'tenant_admin_username' => 'acmeadmin',
            'plan_id' => $planId,
            'address' => 'Acme Street',
            'email' => 'owner@acme.test',
            'contact_number' => '09171234567',
        ]);

        $response->assertRedirect('https://checkout.stripe.com/c/pay/test_session');

        $this->assertDatabaseHas('registration_payments', [
            'plan_id' => $planId,
            'email' => 'owner@acme.test',
            'status' => RegistrationPayment::STATUS_PENDING,
            'provider_session_id' => 'cs_test_123',
        ]);

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_store_redirects_to_fake_payment_page_when_simulation_is_enabled(): void
    {
        config()->set('services.stripe.simulate', true);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro-sim',
            'price_monthly' => 999.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('central.register.store'), [
            'tenant_name' => 'Sim Office',
            'tenant_admin_username' => 'simadmin',
            'plan_id' => $planId,
            'address' => 'Sim Street',
            'email' => 'sim@acme.test',
            'contact_number' => '09998887777',
        ]);

        $response->assertRedirectContains('/central/register/payment/fake?ref=');
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_fake_payment_process_finalizes_tenant_without_real_charge(): void
    {
        config()->set('services.stripe.simulate', true);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro-sim2',
            'price_monthly' => 500.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('registration_payments')->insert([
            'reference' => 'ref-fake-1',
            'tenant_id' => null,
            'plan_id' => $planId,
            'email' => 'fakepay@test.local',
            'provider' => 'stripe',
            'provider_session_id' => null,
            'amount_cents' => 50000,
            'currency' => 'usd',
            'status' => RegistrationPayment::STATUS_PENDING,
            'payload' => json_encode([
                'tenant_name' => 'Fake Pay Tenant',
                'tenant_admin_username' => 'fakeadmin',
                'address' => 'Fake Street',
                'email' => 'fakepay@test.local',
                'contact_number' => '09777777777',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dbManager = Mockery::mock(TenantDatabaseManager::class);
        $dbManager->shouldReceive('provision')->once();
        $this->app->instance(TenantDatabaseManager::class, $dbManager);

        $response = $this->post(route('central.register.payment.fake.process'), [
            'ref' => 'ref-fake-1',
            'card_name' => 'Any Name',
            'card_number' => '1111 2222 3333 4444',
            'expiry' => '12/34',
            'cvc' => '123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('registration_payments', [
            'reference' => 'ref-fake-1',
            'status' => RegistrationPayment::STATUS_PAID,
            'provider_session_id' => 'fake_ref-fake-1',
        ]);
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_subscriptions', 1);
    }

    public function test_payment_success_finalizes_tenant_creation(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro2',
            'price_monthly' => 499.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentId = DB::table('registration_payments')->insertGetId([
            'reference' => 'ref-success-1',
            'tenant_id' => null,
            'plan_id' => $planId,
            'email' => 'owner2@acme.test',
            'provider' => 'stripe',
            'provider_session_id' => null,
            'amount_cents' => 49900,
            'currency' => 'usd',
            'status' => RegistrationPayment::STATUS_PENDING,
            'payload' => json_encode([
                'tenant_name' => 'Bravo Office',
                'tenant_admin_username' => 'bravoadmin',
                'address' => 'Bravo Street',
                'email' => 'owner2@acme.test',
                'contact_number' => '09170000000',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stripe = Mockery::mock(StripeCheckoutService::class);
        $stripe->shouldReceive('retrieveCheckoutSession')->once()->andReturn([
            'id' => 'cs_paid_123',
            'payment_status' => 'paid',
        ]);
        $this->app->instance(StripeCheckoutService::class, $stripe);

        $dbManager = Mockery::mock(TenantDatabaseManager::class);
        $dbManager->shouldReceive('provision')->once();
        $this->app->instance(TenantDatabaseManager::class, $dbManager);

        $response = $this->get(route('central.register.payment.success', [
            'ref' => 'ref-success-1',
            'session_id' => 'cs_paid_123',
        ]));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('registration_payments', [
            'id' => $paymentId,
            'status' => RegistrationPayment::STATUS_PAID,
            'provider_session_id' => 'cs_paid_123',
        ]);

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_subscriptions', 1);
    }

    public function test_payment_cancel_marks_payment_cancelled_and_does_not_create_tenant(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro3',
            'price_monthly' => 299.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('registration_payments')->insert([
            'reference' => 'ref-cancel-1',
            'tenant_id' => null,
            'plan_id' => $planId,
            'email' => 'cancel@acme.test',
            'provider' => 'stripe',
            'provider_session_id' => null,
            'amount_cents' => 29900,
            'currency' => 'usd',
            'status' => RegistrationPayment::STATUS_PENDING,
            'payload' => json_encode(['tenant_name' => 'Cancel Co']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('central.register.payment.cancel', [
            'ref' => 'ref-cancel-1',
        ]));

        $response->assertRedirect(route('central.register'));

        $this->assertDatabaseHas('registration_payments', [
            'reference' => 'ref-cancel-1',
            'status' => RegistrationPayment::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseCount('tenants', 0);
    }
}
