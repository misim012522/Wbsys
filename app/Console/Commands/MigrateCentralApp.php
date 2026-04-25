<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateCentralApp extends Command
{
    protected $signature = 'central:migrate {--fresh : Drop central tables before migrating}';

    protected $description = 'Run only the central app migrations without replaying tenant/default migrations.';

    /**
     * @var array<int, string>
     */
    public function handle(): int
    {
        $this->components->info('Running central-only migrations...');

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', [
                '--database' => 'central',
                '--path' => database_path('migrations/central'),
                '--realpath' => true,
                '--force' => true,
            ]);
        } else {
            $this->runPathMigrations();
        }

        $this->components->info('Central migrations completed successfully.');

        return self::SUCCESS;
    }

    private function runPathMigrations(): void
    {
        $this->call('migrate', [
            '--database' => 'central',
            '--path' => database_path('migrations/central'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }
}
