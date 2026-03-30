<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role', 'admin')
                ->whereNull('tenant_id')
                ->update(['role' => 'system_admin']);

            DB::table('users')
                ->where('role', 'admin')
                ->whereNotNull('tenant_id')
                ->update(['role' => 'tenant_admin']);
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('slug', 'admin')
                ->update([
                    'slug' => 'tenant_admin',
                    'name' => 'Tenant Admin',
                    'description' => 'Full access',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role', 'system_admin')
                ->update(['role' => 'admin']);

            DB::table('users')
                ->where('role', 'tenant_admin')
                ->update(['role' => 'admin']);
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('slug', 'tenant_admin')
                ->update([
                    'slug' => 'admin',
                    'name' => 'Admin',
                    'description' => 'Full access',
                ]);
        }
    }
};
