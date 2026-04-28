<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conn = 'central';

        if (Schema::connection($conn)->hasTable('tenants')) {
            Schema::connection($conn)->table('tenants', function (Blueprint $table) use ($conn) {
                if (! Schema::connection($conn)->hasColumn('tenants', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        $conn = 'central';

        if (Schema::connection($conn)->hasTable('tenants')) {
            Schema::connection($conn)->table('tenants', function (Blueprint $table) use ($conn) {
                if (Schema::connection($conn)->hasColumn('tenants', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }
            });
        }
    }
};
