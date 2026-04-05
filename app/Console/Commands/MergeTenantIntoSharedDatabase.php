<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeTenantIntoSharedDatabase extends Command
{
    protected $signature = 'tenants:merge-shared {tenant? : Tenant ID or slug to migrate}';

    protected $description = 'Copy tenant data from legacy per-tenant databases into the shared application database';

    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantArg = $this->argument('tenant');
        $sharedConnection = config('database.default');

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

            if ($this->tenantDatabaseManager->usesSharedDatabase($tenant)) {
                $this->line('  Already using the shared database.');

                continue;
            }

            try {
                $this->tenantDatabaseManager->activate($tenant);

                if (! Schema::connection('tenant')->hasTable('users')) {
                    $this->warn('  Skipped: tenant source tables were not found.');

                    continue;
                }

                $this->ensureSharedSchema($tenant, $sharedConnection);
                $copiedCounts = $this->copyTenantRows($tenant, $sharedConnection);
            } catch (QueryException $e) {
                if (! $this->isMissingDatabaseException($e)) {
                    throw $e;
                }

                $this->warn('  Legacy tenant database was not found. Switching this tenant to shared mode.');

                $tenant->setSetting('database.mode', 'shared');
                $tenant->save();

                continue;
            }

            $tenant->setSetting('database.mode', 'shared');
            $tenant->save();

            $this->line(sprintf(
                '  Copied %d offices, %d users, %d schedules, %d queue entries, %d appointments, and %d activity logs into %s.',
                $copiedCounts['offices'],
                $copiedCounts['users'],
                $copiedCounts['office_schedules'],
                $copiedCounts['queue_entries'],
                $copiedCounts['appointments'],
                $copiedCounts['activity_logs'],
                (string) config("database.connections.{$sharedConnection}.database", 'shared database'),
            ));
        }

        $this->info('Tenant shared-database migration finished.');

        return self::SUCCESS;
    }

    private function ensureSharedSchema(Tenant $tenant, string $sharedConnection): void
    {
        if (! Schema::connection($sharedConnection)->hasTable('offices')) {
            Artisan::call('migrate', [
                '--database' => $sharedConnection,
                '--path' => database_path('migrations/tenants/_template'),
                '--realpath' => true,
                '--force' => true,
            ]);
        }

    }

    /**
     * @return array<string, int>
     */
    private function copyTenantRows(Tenant $tenant, string $sharedConnection): array
    {
        $counts = [];

        foreach (['offices', 'users', 'office_schedules', 'queue_entries', 'appointments', 'activity_logs'] as $table) {
            $rows = $this->rowsForTable($tenant, $table);
            $inserted = 0;

            foreach (array_chunk($rows, 200) as $chunk) {
                if ($chunk === []) {
                    continue;
                }

                DB::connection($sharedConnection)->table($table)->upsert(
                    $chunk,
                    ['id'],
                );

                $inserted += count($chunk);
            }

            $counts[$table] = $inserted;
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsForTable(Tenant $tenant, string $table): array
    {
        return match ($table) {
            'offices', 'users', 'queue_entries', 'appointments', 'activity_logs' => DB::connection('tenant')
                ->table($table)
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'office_schedules' => DB::connection('tenant')
                ->table('office_schedules')
                ->whereIn('office_id', function ($query) use ($tenant) {
                    $query->from('offices')->select('id')->where('tenant_id', $tenant->id);
                })
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            default => [],
        };
    }

    private function isMissingDatabaseException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unknown database')
            || str_contains($message, 'unable to open database file');
    }
}
