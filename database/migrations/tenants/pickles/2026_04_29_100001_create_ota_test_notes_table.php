<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTA Update Demo Migration
 * ─────────────────────────
 * This migration lives in the tenant _template folder.
 * When a tenant applies an OTA update, it is copied to their
 * own migration folder and run against their specific database only.
 *
 * Purpose: prove that the migration runs in isolation per-tenant.
 */
return new class extends Migration
{
    /**
     * Use the tenant connection so this always targets
     * the currently-active tenant database, never the central one.
     */
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('ota_test_notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('created_by')->nullable();  // author label for display
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('ota_test_notes');
    }
};
