<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('name');
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->unsignedBigInteger('office_id')->nullable();
                $table->string('student_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('price_monthly', 10, 2)->default(0);
                $table->decimal('price_yearly', 10, 2)->nullable();
                $table->json('features')->nullable();
                $table->unsignedSmallInteger('max_offices')->nullable();
                $table->unsignedInteger('max_users_per_tenant')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('domain')->nullable();
                $table->string('subdomain')->nullable();
                $table->string('database_name')->nullable();
                $table->string('address')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_number', 50)->nullable();
                $table->json('settings')->nullable();
                $table->string('support_url')->nullable();
                $table->string('app_version', 32)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('users');
    }
};
