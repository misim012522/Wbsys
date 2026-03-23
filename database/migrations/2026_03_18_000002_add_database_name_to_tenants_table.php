<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('database_name')->nullable()->after('subdomain');
        });

        $tenants = DB::table('tenants')->select('id', 'slug', 'name')->get();
        $used = [];

        foreach ($tenants as $tenant) {
            $base = Str::snake($tenant->slug ?: Str::slug($tenant->name ?: 'tenant', '_'));
            $base = trim($base, '_') !== '' ? trim($base, '_') : 'tenant';
            $candidate = 'tenant_'.$base;
            $counter = 2;

            while (in_array($candidate, $used, true) || DB::table('tenants')->where('database_name', $candidate)->where('id', '!=', $tenant->id)->exists()) {
                $candidate = 'tenant_'.$base.'_'.$counter;
                $counter++;
            }

            DB::table('tenants')->where('id', $tenant->id)->update(['database_name' => $candidate]);
            $used[] = $candidate;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->unique('database_name');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['database_name']);
            $table->dropColumn('database_name');
        });
    }
};
