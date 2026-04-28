<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->nullable();
            $table->string('subdomain')->nullable();
            $table->json('settings')->nullable();
            $table->string('support_url')->nullable();
            $table->string('app_version', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
