<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class BackfillTenantVersions extends Command
{
    protected $signature = 'tenants:backfill-versions';
    protected $description = 'Backfill app_version for existing tenants with current config version';

    public function handle(): int
    {
        $currentVersion = config('app.version', 'v1.0.0');
        
        $tenantsWithoutVersion = Tenant::whereNull('app_version')->get();
        
        if ($tenantsWithoutVersion->isEmpty()) {
            $this->info('All tenants already have app_version set.');
            return self::SUCCESS;
        }
        
        $this->info("Found {$tenantsWithoutVersion->count()} tenants without app_version.");
        $this->info("Setting app_version to: {$currentVersion}");
        
        $updated = Tenant::whereNull('app_version')->update(['app_version' => $currentVersion]);
        $this->info("Updated {$updated} tenants.");
        return self::SUCCESS;
    }
}
