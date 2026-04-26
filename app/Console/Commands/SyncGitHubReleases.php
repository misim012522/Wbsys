<?php

namespace App\Console\Commands;

use App\Services\GitHubReleaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGitHubReleases extends Command
{
    protected $signature = 'github:sync-releases';

    protected $description = 'Sync GitHub releases to app_versions table';

    public function __construct(
        private GitHubReleaseService $githubService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $synced = $this->githubService->syncReleasesToAppVersions();
            
            if ($synced > 0) {
                $this->info("Synced {$synced} new release(s) from GitHub.");
            } else {
                $this->info('No new releases to sync.');
            }
            
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Failed to sync GitHub releases: '.$e->getMessage());
            $this->error('Failed to sync releases: '.$e->getMessage());
            
            return self::FAILURE;
        }
    }
}
