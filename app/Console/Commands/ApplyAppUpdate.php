<?php

namespace App\Console\Commands;

use App\Models\AppVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ApplyAppUpdate extends Command
{
    protected $signature = 'app:update
        {--version= : Expected version to apply}
        {--force : Apply even if the version marker is unchanged}
        {--no-seed : Skip database seeding after migrations}
        {--no-download : Skip downloading and extracting release files}';

    protected $description = 'Apply a published app update on the current system by downloading the release, running migrations, refreshing caches, and optionally seeding.';

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

        // Download and extract release files if not skipped
        if (! $this->option('no-download')) {
            if (! $this->downloadAndExtractRelease($version)) {
                $this->components->error('Download and extraction failed. Update aborted.');
                return self::FAILURE;
            }
        }

        $this->components->info('Running application migrations...');
        
        // Run migrations on the central/default database
        Log::info('[Update] Running central migrations...');
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // If we are in a tenant context, run migrations on the tenant connection too
        if (app()->bound('current_tenant')) {
            $dbName = config('database.connections.tenant.database');
            Log::info("[Update] Running tenant-specific migrations on database: {$dbName}");
            $this->components->info("Running tenant-specific migrations on database: {$dbName}...");
            
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenants/_template',
                '--realpath' => true,
                '--force' => true,
            ]);
            $output = Artisan::output();
            Log::info("[Update] Migration Output: " . $output);
            $this->line($output);
        } else {
            Log::warning('[Update] No current_tenant bound. Skipping tenant migrations.');
        }

        if (! $this->option('no-seed')) {
            $this->components->info('Refreshing seed data...');
            Artisan::call('db:seed', [
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }

        Cache::flush();
        File::ensureDirectoryExists(dirname($markerPath));
        File::put($markerPath, $currentMarker."\n");

        $this->components->info('Application update applied successfully.');
        $this->line('Installed version marker: '.$currentMarker);

        return self::SUCCESS;
    }

    /**
     * Download and extract the release files from GitHub
     * Returns true on success, false on failure
     */
    protected function downloadAndExtractRelease(?string $version): bool
    {
        $appVersion = $version 
            ? AppVersion::where('version', $version)->first()
            : AppVersion::latest()->first();

        if (!$appVersion || !$appVersion->download_url) {
            Log::error("[Update] No download URL found for version: {$version}");
            $this->components->error("No download URL found for this version.");
            return false;
        }

        $url = $appVersion->download_url;
        $this->components->info("Downloading update {$appVersion->version}...");
        Log::info("[Update] Starting download from: {$url}");

        try {
            // Use longer timeout and skip SSL verify on local if needed
            $response = Http::timeout(300)
                ->withOptions(['verify' => false]) // Bypass SSL for local dev simplicity
                ->get($url);

            if (!$response->successful()) {
                Log::error("[Update] Download failed: HTTP " . $response->status());
                $this->components->error("Download failed (HTTP {$response->status()})");
                return false;
            }

            $zipData = $response->body();
            Log::info("[Update] Downloaded " . strlen($zipData) . " bytes.");

            $tempZip = storage_path('app/temp-update.zip');
            File::put($tempZip, $zipData);

            $zip = new ZipArchive;
            if ($zip->open($tempZip) === true) {
                Log::info("[Update] Extracting files to: " . base_path());
                
                // On Windows, extracting to base_path() might have permission issues
                // But this is the required behavior for OTA updates.
                $zip->extractTo(base_path());
                $zip->close();
                
                File::delete($tempZip);
                Log::info("[Update] Files extracted successfully.");
                return true;
            } else {
                Log::error("[Update] Failed to open the downloaded zip file.");
                $this->components->error("Downloaded file is not a valid zip.");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("[Update] Error during update: " . $e->getMessage());
            $this->components->error("Update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Copy directory contents recursively
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (! File::exists($source)) {
            return;
        }

        $items = File::allFiles($source);

        foreach ($items as $item) {
            $relativePath = $item->getRelativePathname();
            $destPath = $destination.'/'.$relativePath;

            // Ensure destination directory exists
            $destDir = dirname($destPath);
            if (! File::exists($destDir)) {
                File::ensureDirectoryExists($destDir);
            }

            // Copy file (overwrite if exists)
            File::copy($item->getPathname(), $destPath);
        }
    }
}