<?php

namespace App\Console\Commands;

use App\Models\AppVersion;
use App\Services\TenantDatabaseManager;
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
        {--app-version= : Expected version to apply}
        {--force : Apply even if the version marker is unchanged}
        {--no-seed : Skip database seeding after migrations}
        {--no-download : Skip downloading and extracting release files}';

    protected $description = 'Apply a published app update on the current system by downloading the release, running migrations, refreshing caches, and optionally seeding.';

    public function handle(): int
    {
        $version = trim((string) $this->option('app-version'));
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

        $isTenantContext = app()->bound('current_tenant');
        $tenant          = $isTenantContext ? app('current_tenant') : null;

        $this->components->info('Running application migrations...');

        if ($isTenantContext && $tenant) {
            // ---------------------------------------------------------------
            // TENANT CONTEXT: Only migrate THIS tenant's database.
            // Central migrations are NOT re-run here — they are handled by a
            // separate central admin action to avoid cross-tenant side-effects.
            // ---------------------------------------------------------------
            $dbName = config('database.connections.tenant.database', $tenant->database_name ?? $tenant->slug);
            Log::info("[Update] Running tenant-specific migrations on database: {$dbName} (tenant: {$tenant->slug})");
            $this->components->info("Running tenant-specific migrations on database: {$dbName}...");

            // Explicitly (re-)activate the tenant DB connection so the Artisan
            // sub-process picks up the correct database.
            $manager = app(TenantDatabaseManager::class);
            $manager->activate($tenant);

            // Sync any new migrations from _template into this tenant's folder only.
            $templatePath = database_path('migrations/tenants/_template');
            $tenantPath   = database_path("migrations/tenants/{$tenant->slug}");
            File::ensureDirectoryExists($tenantPath);

            if (File::exists($templatePath)) {
                foreach (File::files($templatePath) as $file) {
                    $destination = $tenantPath . DIRECTORY_SEPARATOR . $file->getFilename();
                    if (! File::exists($destination)) {
                        File::copy($file->getPathname(), $destination);
                        Log::info("[Update] Copied migration to tenant folder: {$file->getFilename()}");
                    }
                }
            }

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path'     => $tenantPath,
                '--realpath' => true,
                '--force'    => true,
            ]);
            $output = Artisan::output();
            Log::info("[Update] Tenant migration output: " . $output);
            $this->line($output);

            // The seeder only seeds central reference data (plans, announcements,
            // system settings). It must NOT run in tenant context — doing so would
            // duplicate central records and is not tenant-specific data.
            if (! $this->option('no-seed')) {
                Log::info('[Update] Skipping db:seed in tenant context (central-only seeder, not tenant-specific).');
                $this->components->warn('Skipping seeder: seeder targets central data only, not tenant databases.');
            }

            // Flush only this tenant's cache entries to avoid clearing other tenants.
            $tenantCacheKey = 'tenant_' . $tenant->id . '_';
            Cache::forget('app_current_version');
            Cache::forget($tenantCacheKey . 'version');
            Log::info("[Update] Cleared tenant cache for tenant: {$tenant->slug}");

        } else {
            // ---------------------------------------------------------------
            // CENTRAL (non-tenant) CONTEXT: Run central migrations + seeder.
            // ---------------------------------------------------------------
            Log::info('[Update] Running central migrations...');
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            $this->line(Artisan::output());

            if (! $this->option('no-seed')) {
                $this->components->info('Refreshing central seed data...');
                Artisan::call('db:seed', [
                    '--force' => true,
                ]);
                $this->line(Artisan::output());
            }

            // In central context, full cache flush is acceptable.
            Cache::flush();
            Log::warning('[Update] No current_tenant bound. Ran central migrations only; tenant databases untouched.');
        }

        File::ensureDirectoryExists(dirname($markerPath));
        File::put($markerPath, $currentMarker."\n");

        $this->components->info('Application update applied successfully.');
        $this->line('Installed version marker: '.$currentMarker);

        return self::SUCCESS;
    }

    /**
     * Download and extract the release files from GitHub.
     *
     * GitHub release zips wrap all files in a top-level folder, e.g.:
     *   Wbsys-main-1.0.2/app/...
     *   Wbsys-main-1.0.2/database/...
     *
     * We detect that prefix and strip it so every file lands directly in
     * base_path(), placing the new migration, model, controller, view, etc.
     * exactly where Laravel expects them.
     *
     * Returns true on success, false on failure.
     */
    protected function downloadAndExtractRelease(?string $version): bool
    {
        $appVersion = $version
            ? AppVersion::where('version', $version)->first()
            : AppVersion::latest()->first();

        if (! $appVersion || ! $appVersion->download_url) {
            Log::error("[Update] No download URL found for version: {$version}");
            $this->components->error('No download URL found for this version.');
            return false;
        }

        $url = $appVersion->download_url;
        $this->components->info("Downloading update {$appVersion->version}...");
        Log::info("[Update] Starting download from: {$url}");

        try {
            $response = Http::timeout(300)
                ->withOptions(['verify' => false])
                ->get($url);

            if (! $response->successful()) {
                Log::error('[Update] Download failed: HTTP ' . $response->status());
                $this->components->error("Download failed (HTTP {$response->status()})");
                return false;
            }

            $zipData = $response->body();
            Log::info('[Update] Downloaded ' . strlen($zipData) . ' bytes.');

            $tempZip = storage_path('app/temp-update.zip');
            File::put($tempZip, $zipData);

            $zip = new ZipArchive;
            if ($zip->open($tempZip) !== true) {
                Log::error('[Update] Failed to open the downloaded zip file.');
                $this->components->error('Downloaded file is not a valid zip.');
                return false;
            }

            // ── Detect the top-level prefix GitHub adds to every zip ──────────
            // e.g. "Wbsys-main-1.0.2/" — every entry starts with it.
            $topLevelPrefix = '';
            if ($zip->numFiles > 0) {
                $firstName = $zip->getNameIndex(0);
                // If the first entry is itself a directory (ends with /), treat it as the prefix.
                if (str_ends_with($firstName, '/')) {
                    $topLevelPrefix = $firstName;
                } else {
                    // Derive prefix from the first segment of the path.
                    $segments = explode('/', $firstName);
                    if (count($segments) > 1) {
                        $topLevelPrefix = $segments[0] . '/';
                    }
                }
            }

            Log::info("[Update] Zip top-level prefix detected: '{$topLevelPrefix}'");
            $this->components->info('Extracting files to project root...');

            $prefixLen = strlen($topLevelPrefix);
            $base      = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                // Strip the top-level prefix.
                $relative = $prefixLen > 0 ? substr($name, $prefixLen) : $name;

                // Skip the prefix directory entry itself and empty paths.
                if ($relative === '' || $relative === false) {
                    continue;
                }

                $destPath = $base . str_replace('/', DIRECTORY_SEPARATOR, $relative);

                // Directory entry — ensure it exists and move on.
                if (str_ends_with($name, '/')) {
                    File::ensureDirectoryExists($destPath);
                    continue;
                }

                // File entry — ensure parent dir and write content.
                File::ensureDirectoryExists(dirname($destPath));
                $content = $zip->getFromIndex($i);

                if ($content === false) {
                    Log::warning("[Update] Could not read zip entry: {$name}");
                    continue;
                }

                File::put($destPath, $content);
            }

            $zip->close();
            File::delete($tempZip);

            Log::info('[Update] Files extracted successfully to project root.');
            return true;

        } catch (\Exception $e) {
            Log::error('[Update] Error during update: ' . $e->getMessage());
            $this->components->error('Update error: ' . $e->getMessage());
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