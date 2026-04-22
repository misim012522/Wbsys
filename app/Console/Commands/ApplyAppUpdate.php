<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ApplyAppUpdate extends Command
{
    protected $signature = 'app:update
        {--version= : Expected version to apply}
        {--force : Apply even if the version marker is unchanged}
        {--no-seed : Skip database seeding after migrations}';

    protected $description = 'Apply a published app update on the current system by running migrations, refreshing caches, and optionally seeding.';

    public function handle(): int
    {
        $version = trim((string) $this->option('version'));
        $markerPath = storage_path('app/app-update.marker');
        $currentMarker = $version !== '' ? $version : 'latest';

        if (! $this->option('force') && File::exists($markerPath)) {
            $installedMarker = trim((string) File::get($markerPath));

            if ($installedMarker === $currentMarker) {
                $this->components->warn('The requested update is already applied.');

                return self::SUCCESS;
            }
        }

        $this->components->info('Running application migrations...');
        Artisan::call('migrate', [
            '--force' => true,
        ]);

        $migrationOutput = Artisan::output();
        if (trim($migrationOutput) !== '') {
            $this->line($migrationOutput);
        }

        if (! $this->option('no-seed')) {
            $this->components->info('Refreshing seed data...');
            Artisan::call('db:seed', [
                '--force' => true,
            ]);

            $seedOutput = Artisan::output();
            if (trim($seedOutput) !== '') {
                $this->line($seedOutput);
            }
        }

        Cache::flush();
        File::ensureDirectoryExists(dirname($markerPath));
        File::put($markerPath, $currentMarker."\n");

        $this->components->info('Application update applied successfully.');
        $this->line('Installed version marker: '.$currentMarker);

        return self::SUCCESS;
    }
}