<?php

namespace Tests\Feature;

use App\Models\RegistrationPayment;
use App\Services\TenantDatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CentralStripeWebhookTest extends TestCase
{
    private string $webhookSecret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.stripe.webhook_secret', $this->webhookSecret);
        $this->withoutMiddleware();

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

    private function stripeSignatureFor(string $payload, int $timestamp): string
    {
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return 't='.$timestamp.',v1='.$signature;
    }

    public function test_webhook_checkout_completed_finalizes_registration(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'wh-plan',
            'price_monthly' => 100.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('registration_payments')->insert([
            'reference' => 'wh-ref-1',
            'tenant_id' => null,
            'plan_id' => $planId,
            'email' => 'webhook@test.local',
            'provider' => 'stripe',
            'provider_session_id' => null,
            'amount_cents' => 10000,
            'currency' => 'usd',
            'status' => RegistrationPayment::STATUS_PENDING,
            'payload' => json_encode([
                'tenant_name' => 'Webhook Tenant',
                'tenant_admin_username' => 'webhookadmin',
                'address' => 'Webhook Street',
                'email' => 'webhook@test.local',
                'contact_number' => '09999999999',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dbManager = Mockery::mock(TenantDatabaseManager::class);
        $dbManager->shouldReceive('provision')->once();
        $this->app->instance(TenantDatabaseManager::class, $dbManager);

        $eventPayload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_webhook_1',
                    'payment_status' => 'paid',
                    'client_reference_id' => 'wh-ref-1',
                    'metadata' => [
                        'payment_reference' => 'wh-ref-1',
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($eventPayload, JSON_THROW_ON_ERROR);
        $signature = $this->stripeSignatureFor($jsonPayload, time());

        $response = $this->call(
            'POST',
            route('central.payments.stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $jsonPayload
        );

        $response->assertOk();

        $this->assertDatabaseHas('registration_payments', [
            'reference' => 'wh-ref-1',
            'status' => RegistrationPayment::STATUS_PAID,
            'provider_session_id' => 'cs_webhook_1',
        ]);

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_subscriptions', 1);
    }

    public function test_duplicate_webhook_event_is_idempotent(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'wh-plan-idem',
            'price_monthly' => 100.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('registration_payments')->insert([
            'reference' => 'wh-ref-2',
            'tenant_id' => null,
            'plan_id' => $planId,
            'email' => 'idempotent@test.local',
            'provider' => 'stripe',
            'provider_session_id' => null,
            'amount_cents' => 10000,
            'currency' => 'usd',
            'status' => RegistrationPayment::STATUS_PENDING,
            'payload' => json_encode([
                'tenant_name' => 'Idempotent Tenant',
                'tenant_admin_username' => 'idemadmin',
                'address' => 'Idem Street',
                'email' => 'idempotent@test.local',
                'contact_number' => '09888888888',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dbManager = Mockery::mock(TenantDatabaseManager::class);
        $dbManager->shouldReceive('provision')->once();
        $this->app->instance(TenantDatabaseManager::class, $dbManager);

        $eventPayload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_webhook_2',
                    'payment_status' => 'paid',
                    'client_reference_id' => 'wh-ref-2',
                    'metadata' => [
                        'payment_reference' => 'wh-ref-2',
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($eventPayload, JSON_THROW_ON_ERROR);
        $signature = $this->stripeSignatureFor($jsonPayload, time());

        $this->call(
            'POST',
            route('central.payments.stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $jsonPayload
        )->assertOk();

        $this->call(
            'POST',
            route('central.payments.stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $jsonPayload
        )->assertOk();

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_subscriptions', 1);
    }
}
