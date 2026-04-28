<?php

namespace App\Http\Controllers;

use App\Services\GitHubReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TenantUpdateController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $currentVersion = $request->input('version', config('app.version', '1.0.0'));
        $markerPath = storage_path('app/app-update.marker');
        $installedVersion = File::exists($markerPath) ? trim((string) File::get($markerPath)) : null;

        // Fetch latest release from GitHub directly
        $githubService = app(GitHubReleaseService::class);
        $latestRelease = $githubService->fetchLatestRelease();

        $latestVersion = null;
        $downloadUrl = null;
        $releaseNotes = null;
        $updateAvailable = false;

        if ($latestRelease) {
            $latestVersion = ltrim($latestRelease['tag_name'] ?? '', 'v');
            $downloadUrl = $latestRelease['html_url'] ?? null;
            $releaseNotes = $latestRelease['body'] ?? null;

            // Compare versions
            $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        }

        return response()->json([
            'latest_version' => $latestVersion,
            'installed_version' => $installedVersion,
            'current_version' => $currentVersion,
            'update_available' => $updateAvailable,
            'needs_install' => $latestVersion ? $installedVersion !== $latestVersion : false,
            'download_url' => $downloadUrl,
            'release_notes' => $releaseNotes,
            'is_forced' => false,
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:50'],
        ]);

        // Fetch latest release from GitHub
        $githubService = app(GitHubReleaseService::class);
        $latestRelease = $githubService->fetchLatestRelease();

        if (! $latestRelease) {
            return response()->json([
                'message' => 'No published application version was found on GitHub.',
            ], 404);
        }

        $targetVersion = $validated['version'] ?? ltrim($latestRelease['tag_name'] ?? '', 'v');

        // Download the release asset (zip file)
        $downloadUrl = null;
        foreach ($latestRelease['assets'] ?? [] as $asset) {
            if (str_ends_with($asset['name'] ?? '', '.zip')) {
                $downloadUrl = $asset['browser_download_url'];
                break;
            }
        }

        if (! $downloadUrl) {
            return response()->json([
                'message' => 'No downloadable asset found in the release.',
            ], 404);
        }

        // Download the zip file
        $zipPath = storage_path('app/update-'.$targetVersion.'.zip');
        $response = Http::withOptions(['verify' => app()->environment('local') ? false : true])
            ->get($downloadUrl);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to download the release.',
            ], 500);
        }

        File::put($zipPath, $response->body());

        // Extract and apply update
        try {
            $extractPath = storage_path('app/update-'.$targetVersion);
            File::ensureDirectoryExists($extractPath);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Failed to open zip file');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Copy files to project root (excluding certain directories)
            $this->copyUpdateFiles($extractPath, base_path());

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Clear caches
            Artisan::call('optimize:clear');
            Artisan::call('optimize');

            // Update marker
            File::put(storage_path('app/app-update.marker'), $targetVersion);

            // Cleanup
            File::delete($zipPath);
            File::deleteDirectory($extractPath);

            return response()->json([
                'message' => 'Update applied successfully.',
                'version' => $targetVersion,
            ]);
        } catch (\Throwable $e) {
            // Cleanup on error
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }
            if (isset($extractPath) && File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }

            return response()->json([
                'message' => 'Failed to apply update: '.$e->getMessage(),
            ], 500);
        }
    }

    private function copyUpdateFiles(string $source, string $destination): void
    {
        $excludeDirs = ['node_modules', 'vendor', '.git', 'storage', 'bootstrap/cache'];
        $excludeFiles = ['.env', '.gitignore'];

        $files = File::allFiles($source);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePath();

            // Skip excluded directories
            foreach ($excludeDirs as $exclude) {
                if (str_starts_with($relativePath, $exclude)) {
                    continue 2;
                }
            }

            // Skip excluded files
            if (in_array($file->getFilename(), $excludeFiles)) {
                continue;
            }

            $targetPath = $destination.'/'.$relativePath.'/'.$file->getFilename();
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
        }
    }
}