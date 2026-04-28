<?php

namespace App\Console\Commands;

use App\Models\AppVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // If we are in a tenant context, run migrations on the tenant connection too
        if (app()->bound('current_tenant')) {
            $this->components->info('Running tenant-specific migrations...');
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenants/_template',
                '--realpath' => true,
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }

        if (! $this->option('no-seed')) {

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
            ? AppVersion::where('version', ltrim($version, 'v'))->first()
            : AppVersion::latest()->first();

        if (! $appVersion) {
            $this->components->warn('No app version found. Skipping download.');
            return false;
        }

        $downloadUrl = $appVersion->download_url;

        if (! $downloadUrl) {
            $this->components->warn('No download URL found for version '.$appVersion->version.'. Skipping download.');
            return false;
        }

        $this->components->info("Downloading release {$appVersion->version} from GitHub...");

        $tempDir = storage_path('app/temp-update');
        $zipPath = $tempDir.'/release.zip';

        // Clean up temp directory if it exists
        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }

        File::ensureDirectoryExists($tempDir);

        try {
            // Download the zip file
            $response = Http::timeout(300)->withOptions([
                'verify' => app()->environment('local') ? false : true,
            ])->get($downloadUrl);

            if (! $response->successful()) {
                $this->components->error("Failed to download release: HTTP {$response->status()}");
                return false;
            }

            File::put($zipPath, $response->body());
            $this->components->info('Download completed successfully.');

            // Extract the zip file
            $this->components->info('Extracting release files...');
            
            $zip = new ZipArchive;
            $openResult = $zip->open($zipPath);

            if ($openResult !== true) {
                $this->components->error("Failed to open zip file: Error code {$openResult}");
                File::delete($zipPath);
                return false;
            }

            // Extract to a temporary location first
            $extractPath = $tempDir.'/extracted';
            File::ensureDirectoryExists($extractPath);

            if (! $zip->extractTo($extractPath)) {
                $this->components->error('Failed to extract zip file.');
                $zip->close();
                File::delete($zipPath);
                File::deleteDirectory($extractPath);
                return false;
            }

            $zip->close();
            $this->components->info('Extraction completed successfully.');

            // Copy files to project root
            $this->components->info('Installing files...');
            $projectRoot = base_path();

            // Find the root directory inside the extracted zip
            $extractedFiles = File::allFiles($extractPath);
            $firstFile = $extractedFiles[0] ?? null;
            
            if ($firstFile) {
                $relativePath = $firstFile->getRelativePath();
                $zipRoot = $relativePath ? $extractPath.'/'.$relativePath : $extractPath;
            } else {
                $zipRoot = $extractPath;
            }

            // Copy all files from the extracted directory to project root
            $this->copyDirectory($zipRoot, $projectRoot);

            // Cleanup
            File::delete($zipPath);
            File::deleteDirectory($tempDir);

            $this->components->info('Files installed successfully.');
            return true;

        } catch (\Throwable $e) {
            $this->components->error('Error during download/install: '.$e->getMessage());
            
            // Cleanup on error
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
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