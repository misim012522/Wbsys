<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class TenantUpdateController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $currentVersion = $request->input('version', config('app.version', '1.0.0'));
        $latest = AppVersion::latest()->first();
        $markerPath = storage_path('app/app-update.marker');
        $installedVersion = File::exists($markerPath) ? trim((string) File::get($markerPath)) : null;

        return response()->json([
            'latest_version' => $latest?->version,
            'installed_version' => $installedVersion,
            'current_version' => $currentVersion,
            'update_available' => (bool) $latest && $latest->isNewerThan($currentVersion),
            'needs_install' => $latest ? $installedVersion !== $latest->version : false,
            'download_url' => $latest?->download_url,
            'is_forced' => (bool) ($latest?->is_forced ?? false),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:50'],
        ]);

        $latest = AppVersion::latest()->first();

        if (! $latest) {
            return response()->json([
                'message' => 'No published application version was found.',
            ], 404);
        }

        Artisan::call('app:update', [
            '--version' => $validated['version'] ?? $latest->version,
        ]);

        return response()->json([
            'message' => 'Update applied successfully.',
            'version' => $latest->version,
            'output' => trim(Artisan::output()),
        ]);
    }
}