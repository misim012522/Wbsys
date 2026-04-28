<?php

namespace App\Console\Commands;

use App\Models\AppVersion;
use Illuminate\Console\Command;

class SyncCurrentVersion extends Command
{
    protected $signature = 'app:sync-version';

    protected $description = 'Sync current app version from config to app_versions table';

    public function handle(): int
    {
        $currentVersion = config('app.version', '1.0.0');
        
        // Normalize version: remove 'v' prefix and '-dirty' suffix
        $normalized = preg_replace('/^v/', '', $currentVersion);
        $normalized = preg_replace('/-dirty$/', '', $normalized);
        
        $this->info("Current version from config: {$currentVersion}");
        $this->info("Normalized version: {$normalized}");
        
        // Update or create in app_versions table
        $appVersion = AppVersion::query()->updateOrCreate(
            ['version' => $normalized],
            [
                'release_notes' => 'Current deployed version',
                'released_at' => now(),
                'is_forced' => false,
                'download_url' => null,
            ]
        );
        
        if ($appVersion->wasRecentlyCreated) {
            $this->info("Created new version record: {$normalized}");
        } else {
            $this->info("Updated existing version record: {$normalized}");
        }
        
        return self::SUCCESS;
    }
}
