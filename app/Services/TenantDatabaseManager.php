<?php

namespace App\Services;

use App\Models\Office;
use App\Models\OfficeSchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ReservedUsernames;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TenantDatabaseManager
{
    public function activate(Tenant $tenant): void
    {
        $preserveInMemoryConnection = false;

        if ($this->usesSharedDatabase($tenant)) {
            $config = config('database.connections.'.config('database.default'))
                ?? config('database.connections.central')
                ?? config('database.connections.tenant');
            $preserveInMemoryConnection = ($config['driver'] ?? null) === 'sqlite'
                && ($config['database'] ?? null) === ':memory:';
        } else {
            $config = config('database.connections.tenant');
            $driver = $config['driver'] ?? 'mysql';
            $config['database'] = $driver === 'sqlite'
                ? $this->sqlitePath($tenant)
                : $tenant->database_name;
        }

        config(['database.connections.tenant' => $config]);

        if ($preserveInMemoryConnection && array_key_exists('tenant', DB::getConnections())) {
            return;
        }

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function provision(Tenant $tenant, array $adminAttributes): User
    {
        // Only register the tenant in the central DB during registration
        // Do NOT create tenant record or default office in the tenant DB here

        $username = $adminAttributes['username'] ?? 'admin';

        if (ReservedUsernames::isReservedForTenant($username)) {
            throw new \InvalidArgumentException(ReservedUsernames::tenantMessage());
        }

        // Create the admin user in the central DB (if needed)
        $user = new User([
            'name' => $adminAttributes['name'],
            'username' => $username,
            'email' => $adminAttributes['email'],
            'phone' => $adminAttributes['phone'] ?? null,
            'password' => $adminAttributes['password'],
            'role' => User::ROLE_TENANT_ADMIN,
            'tenant_id' => $tenant->id,
            'approved_at' => now(),
        ]);
        $user->setConnection('central');
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    /**
     * Ensure the tenant record exists in the tenant database.
     */
    private function syncTenantToTenantDatabase(Tenant $tenant): void
    {
        // Map only the fields that exist in the tenant DB's tenants table
        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'plan_id' => $tenant->plan_id,
            'domain' => $tenant->domain,
            'subdomain' => $tenant->subdomain,
            'settings' => $tenant->settings,
            'support_url' => $tenant->support_url ?? null,
            'app_version' => $tenant->app_version ?? null,
            'is_active' => $tenant->is_active ?? true,
            'created_at' => $tenant->created_at ?? now(),
            'updated_at' => $tenant->updated_at ?? now(),
        ];
        try {
            // Avoid duplicate insert if already exists
            $exists = \DB::connection('tenant')->table('tenants')->where('id', $tenant->id)->exists();
            if (!$exists) {
                \DB::connection('tenant')->table('tenants')->insert($tenantData);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to sync tenant to tenant DB', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'data' => $tenantData,
            ]);
            throw $e;
        }
    }

    public function initializeSchema(Tenant $tenant): void
    {
        $this->activate($tenant);

        if ($this->usesSharedDatabase($tenant)) {
            $this->ensureSharedSchema();
        } else {
            $this->createDatabase($tenant);
            $this->activate($tenant);
            $this->prepareMigrationFolder($tenant);
            $this->runTenantMigrations($tenant);
        }

        $this->seedReferenceData();
    }

    public function deleteDatabase(Tenant $tenant): void
    {
        if ($this->usesSharedDatabase($tenant)) {
            DB::purge('tenant');
            return;
        }

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

        if ($this->usesSharedDatabase($tenant)) {
            return;
        }

        $migrationPath = $this->tenantMigrationPath($tenant);

        if (File::isDirectory($migrationPath)) {
            File::deleteDirectory($migrationPath);
        }
    }

    private function createDatabase(Tenant $tenant): void
    {
        // Using shared DB: do not create per-tenant databases.
        return;
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

    private function ensureSharedSchema(): void
    {
        if ($this->tenantRuntimeTablesExist()) {
            return;
        }

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenants/_template'),
            '--realpath' => true,
            '--force' => true,
        ]);
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
            ['tenant_id' => null, 'slug' => User::ROLE_TENANT_ADMIN],
            ['name' => 'Tenant Admin', 'description' => 'Full access']
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

    public function ensureDefaultOffice(Tenant $tenant): ?Office
    {
        // Skip creating default offices when explicitly disabled (e.g., during central registration)
        if (config('tenant.skip_default_office_creation', false)) {
            return null;
        }
        // Ensure tenant record exists in tenant DB before creating office to avoid FK errors
        try {
            $schema = Schema::connection('tenant');
            if ($schema->hasTable('tenants')) {
                $exists = \DB::connection('tenant')->table('tenants')->where('id', $tenant->id)->exists();
                if (! $exists) {
                    $this->syncTenantToTenantDatabase($tenant);
                }
            } else {
                // tenant tables not prepared in tenant DB: skip office creation
                throw new \RuntimeException('Tenant runtime tables not present in tenant DB');
            }
        } catch (\Throwable $e) {
            // If tenant DB isn't ready, do not attempt to create offices here.
            throw $e;
        }

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

    private function tenantRuntimeTablesExist(): bool
    {
        $schema = Schema::connection('tenant');

        return $schema->hasTable('users')
            && $schema->hasTable('offices')
            && $schema->hasTable('permissions')
            && $schema->hasTable('roles')
            && $schema->hasTable('role_permission');
    }

    public function usesSharedDatabase(Tenant $tenant): bool
    {
        // Force shared DB mode: tenant data uses the shared tenant database (final_app)
        return true;
    }

    /**
     * Ensure the tenant admin user exists in the tenant database.
     * If not, copy from central DB.
     */
    public function syncTenantAdminToTenantDatabase(Tenant $tenant): void
    {
        // Find admin user in central DB
        $centralAdmin = \App\Models\User::on('central')
            ->where('tenant_id', $tenant->id)
            ->where('role', \App\Models\User::ROLE_TENANT_ADMIN)
            ->first();
        if (! $centralAdmin) return;

        // Check if already exists in tenant DB
        $tenantAdmin = \App\Models\User::on('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('role', \App\Models\User::ROLE_TENANT_ADMIN)
            ->first();
        if ($tenantAdmin) return;

        // Insert into tenant DB
        $userData = $centralAdmin->only([
            'name', 'username', 'email', 'phone', 'password', 'role', 'tenant_id', 'approved_at', 'email_verified_at', 'archived_at', 'student_id', 'office_id', 'remember_token',
        ]);
        $userData['created_at'] = now();
        $userData['updated_at'] = now();
        \App\Models\User::on('tenant')->create($userData);
    }
}
