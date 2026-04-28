<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection('central')->hasColumn('tenants', 'email_notification_sent_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->timestamp('email_notification_sent_at')->nullable()->after('approved_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('central')->hasColumn('tenants', 'email_notification_sent_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->dropColumn('email_notification_sent_at');
            });
        }
    }
};
