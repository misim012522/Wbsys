<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncCentralPlans extends Command
{
    protected $signature = 'central:sync-plans';

    protected $description = 'Sync the central pricing models into the central database.';

    public function handle(): int
    {
        $this->callSilent('db:seed', [
            '--class' => 'Database\\Seeders\\SaasSeeder',
            '--force' => true,
        ]);

        $this->components->info('Central pricing models synced successfully.');

        return self::SUCCESS;
    }
}
