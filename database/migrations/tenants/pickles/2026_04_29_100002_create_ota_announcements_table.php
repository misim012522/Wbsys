<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTA Update Demo #2
 * ──────────────────
 * This migration simulates a new "Tenant Announcements" feature.
 * Created to verify that multiple migrations in a single update
 * are correctly propagated to the tenant database.
 */
return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('ota_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('content');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('ota_announcements');
    }
};
