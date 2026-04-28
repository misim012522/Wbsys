<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TenantUpdateController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $currentVersion = $request->input('version', null);
        $latest = AppVersion::latest()->first();
        $tenant = null;
        if (auth()->check() && auth()->user()->tenant_id) {
            $tenant = auth()->user()->tenant;
        }
        if (! $tenant && app()->bound('current_tenant')) {
            $tenant = app('current_tenant');
        }

        if ($tenant instanceof Tenant) {
            if (! $currentVersion) {
                $currentVersion = $tenant->app_version ?: config('app.version', '1.0.0');
            }
        }

        if (! $currentVersion) {
            $currentVersion = config('app.version', '1.0.0');
        }

        $normalizedCurrentVersion = AppVersion::normalizeVersion($currentVersion) ?? $currentVersion;

        if ($latest && $latest->download_url === null) {
            // Keep the response stable even if the release exists without assets.
        }

        return response()->json([
            'latest_version' => $latest?->version,
            'current_version' => $normalizedCurrentVersion,
            'update_available' => (bool) $latest && $latest->isNewerThan($normalizedCurrentVersion),
            'download_url' => $latest?->download_url,
            'is_forced' => (bool) ($latest?->is_forced ?? false),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:50'],
        ]);

        // Only allow tenant admins to apply updates
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only tenant administrators can apply updates.',
            ], 403);
        }

        $latest = AppVersion::latest()->first();

        if (! $latest) {
            return response()->json([
                'message' => 'No published application version was found.',
            ], 404);
        }

        // Check if download_url exists on the latest version
        if (! $latest->download_url) {
            return response()->json([
                'message' => 'No download URL available for the latest version. The release may not have been published with assets.',
            ], 400);
        }

        // Use the latest version, or the requested version if it matches the latest
        $version = $validated['version'] ?? $latest->version;
        $normalizedRequested = AppVersion::normalizeVersion($version);
        $normalizedLatest = AppVersion::normalizeVersion($latest->version);

        // Only allow updating to the latest version
        if ($normalizedRequested !== $normalizedLatest) {
            return response()->json([
                'message' => 'Can only update to the latest available version.',
            ], 400);
        }

        $exitCode = Artisan::call('app:update', [
            '--version' => $latest->version,
        ]);

        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return response()->json([
                'message' => 'Update failed. Please check the logs for details.',
                'output' => $output,
            ], 500);
        }

        $appliedVersion = $latest->version;
        $tenant = null;
        if (auth()->check() && auth()->user()->tenant_id) {
            $tenant = auth()->user()->tenant;
        }
        if (! $tenant && app()->bound('current_tenant')) {
            $tenant = app('current_tenant');
        }
        if ($tenant instanceof Tenant) {
            $tenant->update(['app_version' => 'v'.$appliedVersion]);
        }

        Cache::forget('app_current_version');

        return response()->json([
            'message' => 'Update applied successfully.',
            'version' => $appliedVersion,
            'output' => $output,
        ]);
    }
}