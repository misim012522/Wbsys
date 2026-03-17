<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $planId = DB::table('plans')->where('slug', 'pro')->value('id');
        if (! $planId) {
            $planId = DB::table('plans')->insertGetId([
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 0,
                'price_yearly' => null,
                'features' => json_encode(['queue', 'appointments', 'reports', 'customization']),
                'max_offices' => null,
                'max_users_per_tenant' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tenantId = DB::table('tenants')->where('slug', 'default')->value('id');
        if (! $tenantId) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => 'Default',
                'slug' => 'default',
                'plan_id' => $planId,
                'domain' => null,
                'subdomain' => 'default',
                'settings' => json_encode(['primary_color' => '#2563eb', 'logo_url' => null]),
                'support_url' => null,
                'app_version' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('offices')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);

        foreach (DB::table('queue_entries')->whereNull('tenant_id')->get() as $row) {
            $office = DB::table('offices')->find($row->office_id);
            if ($office && $office->tenant_id) {
                DB::table('queue_entries')->where('id', $row->id)->update(['tenant_id' => $office->tenant_id]);
            }
        }
        foreach (DB::table('appointments')->whereNull('tenant_id')->get() as $row) {
            $office = DB::table('offices')->find($row->office_id);
            if ($office && $office->tenant_id) {
                DB::table('appointments')->where('id', $row->id)->update(['tenant_id' => $office->tenant_id]);
            }
        }
        foreach (DB::table('activity_logs')->whereNull('tenant_id')->get() as $row) {
            $office = DB::table('offices')->find($row->office_id);
            if ($office && $office->tenant_id) {
                DB::table('activity_logs')->where('id', $row->id)->update(['tenant_id' => $office->tenant_id]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
