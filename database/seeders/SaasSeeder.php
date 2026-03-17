<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeds pricing (Basic, Pro, Ultimate), RBAC (roles + permissions), and optional default tenant.
 */
class SaasSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
        $this->seedDefaultTenant();
        $this->seedPermissions();
        $this->seedRolesAndAttachPermissions();
    }

    private function seedPlans(): void
    {
        $plans = [
            ['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 0, 'features' => ['queue', 'appointments'], 'max_offices' => 3, 'max_users_per_tenant' => 10],
            ['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 29, 'price_yearly' => 290, 'features' => ['queue', 'appointments', 'reports', 'customization'], 'max_offices' => null, 'max_users_per_tenant' => null],
            ['name' => 'Ultimate', 'slug' => 'ultimate', 'price_monthly' => 99, 'price_yearly' => 990, 'features' => ['queue', 'appointments', 'reports', 'customization', 'api', 'priority_support'], 'max_offices' => null, 'max_users_per_tenant' => null],
        ];
        foreach ($plans as $data) {
            Plan::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }
    }

    private function seedDefaultTenant(): void
    {
        $plan = Plan::where('slug', 'pro')->first();
        if (! $plan) {
            return;
        }
        Tenant::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default',
                'plan_id' => $plan->id,
                'subdomain' => 'default',
                'settings' => ['primary_color' => '#2563eb', 'logo_url' => null],
                'is_active' => true,
            ]
        );
    }

    private function seedPermissions(): void
    {
        $permissions = [
            ['name' => 'Manage offices', 'slug' => 'offices.manage', 'module' => 'admin'],
            ['name' => 'Manage queue', 'slug' => 'queue.manage', 'module' => 'admin'],
            ['name' => 'Manage appointments', 'slug' => 'appointments.manage', 'module' => 'admin'],
            ['name' => 'View reports', 'slug' => 'reports.view', 'module' => 'admin'],
            ['name' => 'Manage users', 'slug' => 'users.manage', 'module' => 'admin'],
            ['name' => 'Serve office (call next, update status)', 'slug' => 'office.serve', 'module' => 'office'],
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }

    private function seedRolesAndAttachPermissions(): void
    {
        $admin = Role::firstOrCreate(
            ['tenant_id' => null, 'slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Full access']
        );
        $officeStaff = Role::firstOrCreate(
            ['tenant_id' => null, 'slug' => 'office_staff'],
            ['name' => 'Office Staff', 'description' => 'Serve queue and appointments']
        );
        $student = Role::firstOrCreate(
            ['tenant_id' => null, 'slug' => 'student'],
            ['name' => 'Student', 'description' => 'Queue and book appointments']
        );

        $adminPerms = Permission::whereIn('slug', ['offices.manage', 'queue.manage', 'appointments.manage', 'reports.view', 'users.manage', 'office.serve'])->pluck('id');
        $admin->permissions()->syncWithoutDetaching($adminPerms);

        $staffPerms = Permission::where('slug', 'office.serve')->pluck('id');
        $officeStaff->permissions()->syncWithoutDetaching($staffPerms);
    }
}
