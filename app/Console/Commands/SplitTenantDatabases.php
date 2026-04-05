<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SplitTenantDatabases extends Command
{
    protected $signature = 'tenants:split-databases {tenant? : Tenant ID or slug to migrate}';

    protected $description = 'Provision per-tenant databases for existing tenants and copy tenant-scoped data from the shared database';

    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantArg = $this->argument('tenant');

        $tenants = Tenant::query()
            ->when($tenantArg, function ($query) use ($tenantArg) {
                $query->where('id', $tenantArg)->orWhere('slug', $tenantArg);
            })
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found to migrate.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant {$tenant->id} ({$tenant->name})");

            if (! $tenant->database_name) {
                $this->warn('  Skipped: tenant has no database_name.');

                continue;
            }

            $this->tenantDatabaseManager->initializeSchema($tenant);

            $existingTenantData = DB::connection('tenant')->table('users')->count()
                + DB::connection('tenant')->table('offices')->count()
                + DB::connection('tenant')->table('queue_entries')->count()
                + DB::connection('tenant')->table('appointments')->count();

            if ($existingTenantData > 0) {
                $this->warn('  Skipped import: tenant database already has data.');

                continue;
            }

            $this->copyTenantRows($tenant);
        }

        $this->info('Tenant database split command finished.');

        return self::SUCCESS;
    }

    private function copyTenantRows(Tenant $tenant): void
    {
        if (! Schema::connection('central')->hasTable('offices')) {
            $this->warn('  Shared tenant tables were not found in the central database. Nothing to copy.');

            return;
        }

        $offices = DB::connection('central')->table('offices')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $users = DB::connection('central')->table('users')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $schedules = DB::connection('central')->table('office_schedules')
            ->whereIn('office_id', collect($offices)->pluck('id'))
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $queueEntries = DB::connection('central')->table('queue_entries')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $appointments = DB::connection('central')->table('appointments')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $activityLogs = DB::connection('central')->table('activity_logs')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        DB::connection('tenant')->transaction(function () use ($offices, $users, $schedules, $queueEntries, $appointments, $activityLogs): void {
            if (! empty($offices)) {
                DB::connection('tenant')->table('offices')->insert($offices);
            }

            if (! empty($users)) {
                DB::connection('tenant')->table('users')->insert($users);
            }

            if (! empty($schedules)) {
                DB::connection('tenant')->table('office_schedules')->insert($schedules);
            }

            if (! empty($queueEntries)) {
                DB::connection('tenant')->table('queue_entries')->insert($queueEntries);
            }

            if (! empty($appointments)) {
                DB::connection('tenant')->table('appointments')->insert($appointments);
            }

            if (! empty($activityLogs)) {
                DB::connection('tenant')->table('activity_logs')->insert($activityLogs);
            }
        });

        if (empty($offices)) {
            $this->tenantDatabaseManager->ensureDefaultOffice($tenant);
        }

        $this->line(sprintf(
            '  Copied %d offices, %d users, %d schedules, %d queue entries, %d appointments, and %d activity logs.',
            count($offices),
            count($users),
            count($schedules),
            count($queueEntries),
            count($appointments),
            count($activityLogs),
        ));
    }
}
