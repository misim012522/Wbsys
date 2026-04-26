<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('central')->hasColumn('plans', 'max_users_per_tenant')) {
            Schema::connection('central')->table('plans', function (Blueprint $table) {
                $table->dropColumn('max_users_per_tenant');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            $table->integer('max_users_per_tenant')->nullable()->after('max_offices');
        });
    }
};
