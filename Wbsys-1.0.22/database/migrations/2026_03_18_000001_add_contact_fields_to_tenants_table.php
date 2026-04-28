<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('address')->nullable()->after('subdomain');
            $table->string('email')->nullable()->after('address');
            $table->string('contact_name')->nullable()->after('email');
            $table->string('contact_number', 50)->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['address', 'email', 'contact_name', 'contact_number']);
        });
    }
};
