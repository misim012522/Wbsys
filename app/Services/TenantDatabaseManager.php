<?php

namespace App\Services;

use App\Models\Office;
use App\Models\OfficeSchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TenantDatabaseManager
{
    public function activate(Tenant $tenant): void
    {
        $config = config('database.connections.tenant');
        $driver = $config['driver'] ?? 'mysql';

        $config['database'] = $driver === 'sqlite'
            ? $this->sqlitePath($tenant)
            : $tenant->database_name;

        config(['database.connections.tenant' => $config]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function provision(Tenant $tenant, array $adminAttributes): User
    {
        $this->initializeSchema($tenant);
        $this->ensureDefaultOffice($tenant);

        $user = new User([
            'name' => $adminAttributes['name'],
            'username' => $adminAttributes['username'] ?? 'admin',
            'email' => $adminAttributes['email'],
            'phone' => $adminAttributes['phone'] ?? null,
            'password' => $adminAttributes['password'],
            'role' => User::ROLE_ADMIN,
            'tenant_id' => $tenant->id,
            'approved_at' => now(),
        ]);
        $user->setConnection('tenant');
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    public function initializeSchema(Tenant $tenant): void
    {
        $this->createDatabase($tenant);
        $this->activate($tenant);
        $this->prepareMigrationFolder($tenant);
        $this->runTenantMigrations($tenant);
        $this->seedReferenceData();
    }

    public function deleteDatabase(Tenant $tenant): void
    {
        $config = config('database.connections.tenant');
        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $path = $this->sqlitePath($tenant);

            if (File::exists($path)) {
                File::delete($path);
            }

            return;
        }

        if (! $tenant->database_name) {
            return;
        }

        DB::connection('central')->statement('DROP DATABASE IF EXISTS `'.$tenant->database_name.'`');
        DB::purge('tenant');
    }

    public function deleteTenantArtifacts(Tenant $tenant): void
    {
        $this->deleteDatabase($tenant);

        $migrationPath = $this->tenantMigrationPath($tenant);

        if (File::isDirectory($migrationPath)) {
            File::deleteDirectory($migrationPath);
        }
    }

    private function createDatabase(Tenant $tenant): void
    {
        $config = config('database.connections.tenant');
        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $path = $this->sqlitePath($tenant);
            File::ensureDirectoryExists(dirname($path));

            if (! File::exists($path)) {
                File::put($path, '');
            }

            return;
        }

        if (! preg_match('/^[A-Za-z0-9_]+$/', (string) $tenant->database_name)) {
            throw new \RuntimeException('The generated tenant database name is invalid.');
        }

        DB::connection('central')->statement(
            'CREATE DATABASE IF NOT EXISTS `'.$tenant->database_name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    private function runTenantMigrations(Tenant $tenant): void
    {
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => $this->tenantMigrationPath($tenant),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    private function prepareMigrationFolder(Tenant $tenant): void
    {
        $targetPath = $this->tenantMigrationPath($tenant);
        File::ensureDirectoryExists($targetPath);

        $templatePath = database_path('migrations/tenants/_template');

        foreach (File::files($templatePath) as $file) {
            $destination = $targetPath.DIRECTORY_SEPARATOR.$file->getFilename();

            if (! File::exists($destination)) {
                File::copy($file->getPathname(), $destination);
            }
        }
    }

    private function seedReferenceData(): void
    {
        $permissions = [
            ['name' => 'Manage offices', 'slug' => 'offices.manage', 'module' => 'admin'],
            ['name' => 'Manage queue', 'slug' => 'queue.manage', 'module' => 'admin'],
            ['name' => 'Manage appointments', 'slug' => 'appointments.manage', 'module' => 'admin'],
            ['name' => 'View reports', 'slug' => 'reports.view', 'module' => 'admin'],
            ['name' => 'Manage users', 'slug' => 'users.manage', 'module' => 'admin'],
            ['name' => 'Serve office (call next, update status)', 'slug' => 'office.serve', 'module' => 'office'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $admin = Role::firstOrCreate(
            ['tenant_id' => null, 'slug' => User::ROLE_ADMIN],
            ['name' => 'Admin', 'description' => 'Full access']
        );

        $officeStaff = Role::firstOrCreate(
            ['tenant_id' => null, 'slug' => User::ROLE_OFFICE_STAFF],
            ['name' => 'Office Staff', 'description' => 'Serve queue and appointments']
        );

        $adminPerms = Permission::whereIn('slug', [
            'offices.manage',
            'queue.manage',
            'appointments.manage',
            'reports.view',
            'users.manage',
            'office.serve',
        ])->pluck('id');

        $officePerms = Permission::where('slug', 'office.serve')->pluck('id');

        $admin->permissions()->syncWithoutDetaching($adminPerms);
        $officeStaff->permissions()->syncWithoutDetaching($officePerms);
    }

    public function ensureDefaultOffice(Tenant $tenant): Office
    {
        $office = Office::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => $tenant->slug],
            [
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'description' => 'Default office created during tenant onboarding.',
                'location' => $tenant->address,
                'is_active' => true,
                'max_daily_queue' => 100,
                'serving_time_minutes' => 15,
            ]
        );

        for ($day = 1; $day <= 5; $day++) {
            OfficeSchedule::firstOrCreate(
                [
                    'office_id' => $office->id,
                    'day_of_week' => $day,
                ],
                [
                    'open_time' => '08:00:00',
                    'close_time' => '17:00:00',
                    'is_active' => true,
                ]
            );
        }

        return $office;
    }

    private function sqlitePath(Tenant $tenant): string
    {
        return database_path('tenants/'.$tenant->database_name.'.sqlite');
    }

    private function tenantMigrationPath(Tenant $tenant): string
    {
        return database_path('migrations/tenants/'.$tenant->slug);
    }
}
